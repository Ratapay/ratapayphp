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

        // adjust base url according to sandbox flag
        $this->base_url = 'https://dev.ratapay.co.id/v2';
        if (!$sandbox) {
            $this->base_url = 'https://api.ratapay.co.id/v2';
        }

        // get token data from cache file, if already declared and still valid
        if ($this->sandbox) {
            $filename = dirname(__FILE__).'/../data/.tokensandbox';
        } else {
            $filename = dirname(__FILE__).'/../data/.token';
        }
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
                    'data' => $responseData->invoice_data,
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

    /**
     * Get Invoice List from Ratapay
     * Validate Parameters
     *
     * @param String $reference invoice reference
     *
     * @param String $invoice_id merchant invoice id 
     *
     * @param Array $creation_time range of invoice creation time
     *
     * @param Array $paid_time range of invoice paid time
     *
     * @param Int $offset listing offset
     *
     * @param Int $limit listing limit max 30
     *
     * @return Object Invoice Listing
     */

    public function listTransaction($reference = '', $invoice_id = '', $creation_time = [], $paid_time = [], $offset = 0, $limit = 5)
    {
        // max limit 30
        $limit = min($limit, 30);
        
        $params = [
            'offset' => $offset,
            'limit' => $limit
        ];

        $invalids = [];

        $conditions = [];
        if (!empty($reference)) {
            $reference = filter_var($reference, FILTER_SANITIZE_STRING);
            if (!$reference) {
                $invalids['reference'] = 'Invalid Reference Value';
            } else {
                $conditions['ref'] = $reference;
            }
        }

        if (!empty($invoice_id)) {
            $invoice_id = filter_var($invoice_id, FILTER_SANITIZE_STRING);
            if (!$invoice_id) {
                $invalids['invoice_id'] = 'Invalid Invoice ID Value';
            } else {
                $conditions['source_invoice_id'] = $invoice_id;
            }
        }
        
        if (!empty($creation_time)) {
            if (count($creation_time) < 2) {
                $invalids['creation_time'] = 'Creation time parameters must be an array of start and end time';
            } else {
                $date_time = $creation_time[0];
                $date_time_end = $creation_time[1];
                $date_time = filter_var($date_time, FILTER_VALIDATE_INT);
                if (!$date_time) {
                    $invalids['creation_time[0]'] = 'Invalid Creation Time Start Value';
                }
                $date_time_end = filter_var($date_time_end, FILTER_VALIDATE_INT);
                if (!$date_time_end) {
                    $invalids['creation_time[1]'] = 'Invalid Creation Time End Value';
                }
                $conditions['date_time'] = date('Y-m-d H:i:s', $date_time);
                $conditions['date_time_end'] = date('Y-m-d H:i:s', $date_time_end);
            }
        }
        
        if (!empty($paid_time)) {
            if (count($paid_time) < 2) {
                $invalids['paid_time'] = 'Creation time parameters must be an array of start and end time';
            } else {
                $finished_date_time = $paid_time[0];
                $finished_date_time_end = $paid_time[1];
                $finished_date_time = filter_var($finished_date_time, FILTER_VALIDATE_INT);
                if (!$finished_date_time) {
                    $invalids['paid_time[0]'] = 'Invalid Creation Time Start Value';
                }
                $finished_date_time_end = filter_var($finished_date_time_end, FILTER_VALIDATE_INT);
                if (!$finished_date_time_end) {
                    $invalids['paid_time[1]'] = 'Invalid Creation Time End Value';
                }
                $conditions['finished_date_time'] = date('Y-m-d H:i:s', $finished_date_time);
                $conditions['finished_date_time_end'] = date('Y-m-d H:i:s', $finished_date_time_end);
            }
        }

        if (!empty($invalids)) {
            $errorArray = [];
            foreach ($invalids as $field => $message) {
                $errorArray[] = $field . ': ' . $message;
            }
            $errorMessage = implode(', ', $errorArray);
            throw new \Exception($errorMessage);
        }

        // data preparation
        $conditionString = '';
        if (!empty($conditions)) {
            ksort($conditions);
            $conditionString = http_build_query(['condition' => $conditions]);
        }
        $endpoint = "/transaction?". $conditionString ."&limit=$limit&offset=$offset";
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = null;
        $signature = Signature::generate('GET', $endpoint, $payload, $this->api_token, $this->api_secret, $iso_time);
        $client = new guzzleClient();
        try {
            $response = $client->get($this->base_url . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'X-RATAPAY-SIGN' => $signature,
                    'X-RATAPAY-TS' => $iso_time,
                    'X-RATAPAY-KEY' => $this->api_key
                ]
            ]);
            
            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);

            if (!empty($responseData) && $responseData->success) {
                $unsetAttributes = ['is_settled', 'acc_balance', 'id', 'currency', 'source_ip', 'paylink_id', 'parent_id', 'is_settled', 'account_number', 'email', 'user_note', 'type'];
                $renameAttributes = ['date_time' => 'creation_time', 'finished_date_time' => 'paid_time', 'processing_date_time' => 'split_time'];
                $responseData->status = 'success';
                unset($responseData->success);
                foreach ($responseData->list as $idx => $val) {
                    foreach ($unsetAttributes as $attr) {
                        unset($responseData->list[$idx]->$attr);
                    }
                    foreach ($renameAttributes as $src => $tgt) {
                        $responseData->list[$idx]->$tgt = $responseData->list[$idx]->$src;
                        unset($responseData->list[$idx]->$src);
                    }
                }
                return $responseData;
            } elseif (!empty($responseData) && !$responseData->success) {
                $responseData->status = 'failed';
                unset($responseData->success);
                return $responseData;
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

    /**
     * Register New Ratapay Account
     *
     * @param Array Params for account creation details
     *
     * @return Object Account details result
     */

    public function registerAccount($params = [])
    {
        $endpoint = '/account';
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $signature = Signature::generate('POST', $endpoint, $params, $this->api_token, $this->api_secret, $iso_time);
        $client = new guzzleClient();
        try {
            $response = $client->post($this->base_url . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'X-RATAPAY-SIGN' => $signature,
                    'X-RATAPAY-TS' => $iso_time,
                    'X-RATAPAY-KEY' => $this->api_key
                ],
                'form_params' => $params
            ]);
             
            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);
 
            if (!empty($responseData) && $responseData->success) {
                unset($responseData->success);
                $responseData->status = 'success';
                return $responseData;
            } elseif (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'error' => isset($responseData->error) ? $responseData->error : 'error',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Account Creation Failed'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'error' => 'empty',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'error' => 'unknown',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        } catch (RequestException | ClientException $e) {
            $response = $e->getResponse();

            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);
            
            if (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'error' => isset($responseData->error) ? $responseData->error : 'error',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Account Creation Failed'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'error' => 'empty',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'error' => 'unknown',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        }
    }

    /**
     * Get Account Details
     *
     * @param Array Query params to get account details
     *
     * @return Object Account details result
     */

    public function getAccount($params = [])
    {
         // data preparation
        $endpoint = '/account';
        if (count($params) > 0) {
            $iter = 0;
            foreach ($params as $key => $value) {
                if ($iter == 0) {
                    $endpoint .= sprintf('?%s=%s', $key, $value);
                    $iter++;
                } else {
                    $endpoint .= sprintf('&%s=%s', $key, $value);
                }
            }
        }
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = null;
        $signature = Signature::generate('GET', $endpoint, $payload, $this->api_token, $this->api_secret, $iso_time);
        $client = new guzzleClient();
        try {
            $response = $client->get($this->base_url . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'X-RATAPAY-SIGN' => $signature,
                    'X-RATAPAY-TS' => $iso_time,
                    'X-RATAPAY-KEY' => $this->api_key
                ]
            ]);
             
            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);
 
            if (!empty($responseData) && $responseData->success) {
                unset($responseData->success);
                $responseData->status = 'success';
                return $responseData;
            } elseif (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'error' => isset($responseData->error) ? $responseData->error : 'error',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Account Details Fetching Failed'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'error' => 'empty',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'error' => 'unknown',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        } catch (RequestException | ClientException $e) {
            $response = $e->getResponse();

            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);
            
            if (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'error' => isset($responseData->error) ? $responseData->error : 'error',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Account Details Fetching Failed'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'error' => 'empty',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'error' => 'unknown',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        }
    }

    /**
     * Link Account to Merchant
     *
     * @param String account email to be linked
     *
     * @param String username of the related account email
     *
     * @return Object Account details result
     */

    public function linkAccount($email, $username = null)
    {
        // data preparation
        if (is_null($username)) {
            $username = str_replace('.', '_', str_replace('@', '_', $email));
        }
        $endpoint = '/account/link';
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = [
            'email' => $email,
            'username' => $username
        ];
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
                unset($responseData->success);
                $responseData->status = 'success';
                return $responseData;
            } elseif (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'error' => isset($responseData->error) ? $responseData->error : 'error',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Failed to Generate Account Link URL'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'error' => 'empty',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'error' => 'unknown',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        } catch (RequestException | ClientException $e) {
            $response = $e->getResponse();

            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody);

            if (!empty($responseData) && !$responseData->success) {
                return (object)[
                    'status' => 'failed',
                    'error' => isset($responseData->error) ? $responseData->error : 'error',
                    'message' => isset($responseData->msg) ? $responseData->msg : 'Failed to Generate Account Link URL'
                ];
            } elseif (empty($responseData)) {
                return (object)[
                    'status' => 'failed',
                    'error' => 'empty',
                    'message' => 'Empty Response'
                ];
            } else {
                return (object)[
                    'status' => 'failed',
                    'error' => 'unknown',
                    'message' => 'Unknown Error Occcurred'
                ];
            }
        }
    }
    
    /**
     * Send Split Process Request
     *
     * @param $ref invoice reference to be processed
     * @param $item_ids specific item id to be processed
     *
     * @return Object Split Process Response Details
     */

    public function confirmSplit($ref, $item_ids = null)
    {
        // data preparation
        $endpoint = '/transaction/process';
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = [
            'ref' => $ref,
        ];

        if (!is_null($item_ids) && is_array($item_ids)) {
            $payload['item_ids'] = $item_ids;
        }

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
                return (object)[
                    'status' => 'success',
                    'data' => $responseData
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
    
    /**
     * Send Extend Refund Period Request
     *
     * @param $ref invoice reference to have the refund threshold extended
     * @param $period how long is the extension
     * @param $item_ids specific item id to be processed
     *
     * @return Object Extend Refund Response Details
     */

    public function extendRefund($ref, $period, $item_ids = null)
    {
        // data preparation
        $endpoint = '/transaction/refund/change';
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = [
            'ref' => $ref,
            'period' => $period,
        ];

        if (!is_null($item_ids) && is_array($item_ids)) {
            $payload['item_ids'] = $item_ids;
        }

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
                return (object)[
                    'status' => 'success',
                    'data' => $responseData
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
    
    /**
     * Send Confirm Refund Period Request
     *
     * @param $ref invoice reference to be refunded
     * @param $params define how will the refund should be processed
     *
     * @return Object Confirm Refund Response Details
     */

    public function confirmRefund($ref, $params = null)
    {
        // data preparation
        $endpoint = '/transaction/refund';
        $fmt = date('Y-m-d\TH:i:s');
        $iso_time = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));
        $payload = [
            'ref' => $ref,
        ];

        if (!is_null($params) && is_array($params)) {
            $payload['params'] = $params;
        }

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
                return (object)[
                    'status' => 'success',
                    'data' => $responseData
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
