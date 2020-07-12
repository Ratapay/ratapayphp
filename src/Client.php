<?php

namespace ratapay\ratapayphp;

use GuzzleHttp\Client as guzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /**
     * Client Merchant ID
     * @var String
     */

    protected $merchant_id;
    
    /**
     * Client Merchant Secret
     * @var String
     */

    protected $merchant_secret;

    /**
     * Client API Key
     * @var String
     */

    protected $api_key;

    /**
     * Client API Secret
     * @var String
     */

    protected $api_secret;

    /**
     * Client Token
     * @var String
     */

    protected $api_token;

    /**
     * Sandbox Flag
     * @var String
     */

    protected $sandbox;

    /**
     * Base URL
     * @var String
     */

    protected $base_url;

    public function __construct(
        $merchant_id = null,
        $merchant_secret = null,
        $api_key = null,
        $api_secret = null,
        $sandbox = true
    ) {
        $this->merchant_id = $merchant_id;
        $this->merchant_secret = $merchant_secret;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->sandbox = $sandbox;

        // adjust base url accoirding to sandbox flag
        $this->base_url = 'https://dev.ratapay.co.id/v2';
        if (!$sandbox) {
            $this->base_url = 'https://api.ratapay.co.id/v2';
        }

        // get token data from cache file, if already declared and stil valid
        $filename = dirname(__FILE__).'/../data/.token';
        $token_data_raw = file_get_contents($filename);
        $token_data = json_decode($token_data_raw);

        if (empty($token_data_raw) || empty($token_data) || time() > $token_data->expires_at) {
            // request new token
            $client = new guzzleClient();
            try {
                $response = $client->post($this->base_url . '/oauth/token', [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $merchant_id,
                        'client_secret' => $merchant_secret,
                        'scope' => '*'
                        ]
                        ]);
            } catch (RequestException | ClientException $e) {
                $response = $e->getResponse();
                if (empty($response)) {
                    throw new \Exception('Empty Response');
                } else {
                    throw new \Exception((string)$response->getBody());
                }
            }

            $token_data = json_decode((string)$response->getBody());
            $token_data->expires_at = time() + $token_data->expires_in - 600;

            file_put_contents($filename, json_encode($token_data));
            $this->api_token = $token_data->access_token;
        } else {
            $this->api_token = $token_data->access_token;
        }
    }

    /**
     * Send Invoice Creation Request to Ratapay
     * Validate Amount and Share Data
     *
     * @param Invoice Invoice to be Created
     *
     * @return Object Invoice Creation Response Details
     */

    public function createInvoice(Invoice $invoice)
    {
        $this->createTransaction($invoice);
    }

    /**
     * Send Invoice Creation Request to Ratapay
     * Validate Amount and Share Data
     *
     * @param Invoice Invoice to be Created
     *
     * @return Object Invoice Creation Response Details
     */

    public function createTransaction(Invoice $invoice)
    {
        // validate invoice
        $validate = $invoice->validate();
        if (!$validate->valid) {
            throw new \Exception($validate->message);
        }

        // data preparation
        $endpoint = '/transaction';
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = $invoice->payload();
        $payload['merchant_id'] = $this->merchant_id;
        $signature = Signature::generate('POST', $endpoint, $payload, $this->api_token, $this->api_secret, $iso_time);

        $client = new guzzleClient();
        try {
            $response = $client->post($this->base_url . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'X-RATAPAY-SIGN' => $signature,
                    'X-RATAPAY-TS' => $iso_time,
                    'X-RATAPAY-KEY' => $this->api_key
                ],
                'form_params' => $payload
            ]);
            
            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);

            if (!empty($responseData) && $responseData->success) {
                $payment_url_base = $this->sandbox ? 'https://appdev.ratapay.co.id' : 'https://app.ratapay.co.id';
                return (object)[
                    'status' => 'success',
                    'data' => [
                        'invoice_id' => $responseData->invoice_data->source_invoice_id,
                        'note' => $responseData->invoice_data->note,
                        'ref' => $responseData->invoice_data->ref,
                    ],
                    'payment_url' => $payment_url_base . '/payment/' . $responseData->invoice_data->ref
                ];
            } elseif (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Invoice Creation Failed'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        } catch (RequestException | ClientException $e) {
            $response = $e->getResponse();
            if (empty($response)) {
                throw new \Exception('Empty Response');
            } else {
                throw new \Exception((string)$response->getBody());
            }
        }
    }
}
