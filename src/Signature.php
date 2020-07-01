<?php

namespace ratapay\ratapayphp;

class Signature
{
    public function __construct($data)
    {
    }

    /**
     * Generate signature
     *
     * @param String $method HTTP verb
     * @param String $endpoint request endpoint
     * @param String $payload request payload
     * @param String $api_token API Token
     * @param String $api_secret API Secret
     * @param String $iso_time time in ISO format
     *
     * @return none
     */
    public static function generate($method, $endpoint, &$payload, $api_token, $api_secret, $iso_time)
    {
        $hash = null;
        if (is_array($payload) && !empty($payload)) {
            ksort($payload);
            
            $encoderData = json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
            $encoderData = preg_replace('/\s/', '', $encoderData);
            $hash        = hash("sha256", $encoderData);
        } else {
            $hash = hash("sha256", "");
        }
        
        $stringToSign   = $method .":". $endpoint .":". $api_token . ":" . $hash . ":" . $iso_time;
        $stringToSign = preg_replace('/\s/', '', $stringToSign);
        
        $signature = hash_hmac('sha256', $stringToSign, $api_secret);

        return $signature;
    }
}
