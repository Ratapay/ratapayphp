<?php

namespace ratapay\ratapayphp;

class Beneficiary
{
    /**
     * Beneficiary Email
     * @var String
     */
    protected $email;
    
    /**
     * Beneficiary Name
     * @var String
     */
    protected $name;
    
    /**
     * Beneficiary Username
     * @var String
     */
    protected $username;

    /**
     * Beneficiary Share Amount
     * @var Int
     */
    protected $share_amount;

    /**
     * Beneficiary Rebill Share Amount
     * @var Int
     */
    protected $rebill_share_amount;
    
    /**
     * Beneficiary Share Item ID
     * @var Int
     */
    protected $share_item_id;
    
    /**
     * Beneficiary Share Item ID
     * @var Int
     */
    protected $tier;

    /**
     * Beneficiary Is Valid
     * @var Boolean
     */
    protected $is_valid;

    /**
     * Construct an Invoice Instance
     * @param Array $data
     * Sanitizing and validating all parameters
     */
    public function __construct($data)
    {
        $email = isset($data['email']) ? $data['email'] : null;
        $name = isset($data['name']) ? $data['name'] : null;
        $username = isset($data['username']) ? $data['username'] : null;
        $share_amount = isset($data['share_amount']) ? $data['share_amount'] : null;
        $rebill_share_amount = isset($data['rebill_share_amount']) ? $data['rebill_share_amount'] : null;
        $share_item_id = isset($data['share_item_id']) ? $data['share_item_id'] : null;
        $tier = isset($data['tier']) ? $data['tier'] : 1;

        if (is_null($email)) {
            throw new \Exception('Beneficiary Email is Required');
        } elseif (is_null($share_amount)) {
            throw new \Exception('Beneficiary Share Amount is Required');
        }

        $invalids = [];
        // Sanitize & Validate Email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (empty($email)) {
            $invalids['email'] = 'Invalid Benficiary Email Value';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalids['email'] = 'Invalid Benficiary Email Value';
        }

        // Sanitize Name
        if (!is_null($name)) {
            $name = filter_var($name, FILTER_SANITIZE_STRING);
            if (!$name) {
                $invalids['name'] = 'Invalid Beneficiary Name Value';
            } elseif (strlen($name) > 64) {
                $invalids['name'] = 'Invalid Beneficiary Name Value, Too Long, Max 64';
            }
        }

        // Sanitize Username
        if (!is_null($username)) {
            $username = filter_var($username, FILTER_SANITIZE_STRING);
            if (!$username) {
                $invalids['username'] = 'Invalid Beneficiary Username Value';
            } elseif (strlen($username) > 64) {
                $invalids['username'] = 'Invalid Beneficiary Username Value, Too Long, Max 64';
            }
        }

        // Validate Share Amount
        $share_amount = filter_var($share_amount, FILTER_VALIDATE_INT);
        if ($share_amount === false) {
            $invalids['share_amount'] = 'Invalid Benficiary Share Amount Value';
        }

        // Validate Subtotal
        if (!is_null($rebill_share_amount)) {
            $rebill_share_amount = filter_var($rebill_share_amount, FILTER_VALIDATE_INT);
            if (!is_numeric($rebill_share_amount)) {
                $invalids['rebill_share_amount'] = 'Invalid Benficiary Rebill Share Amount Value';
            }
        }

        // Validate Share Item ID
        if (!is_null($share_item_id)) {
            $share_item_id = filter_var($share_item_id, FILTER_SANITIZE_STRING);
            if (!$share_item_id) {
                $invalids['share_item_id'] = 'Invalid Benficiary Share Item ID Value';
            }
        }
        
        // Validate Beneficiary Tier
        if (!is_null($tier)) {
            $tier = filter_var($tier, FILTER_VALIDATE_INT);
            if ($tier === false) {
                $invalids['tier'] = 'Invalid Benficiary Tier Value';
            }
            $this->tier = $tier;
        } else {
            $this->tier = $tier;
        }

        if (!empty($invalids)) {
            $this->is_valid = false;
            $errorArray = [];
            foreach ($invalids as $field => $message) {
                $errorArray[] = $field . ': ' . $message;
            }
            $errorMessage = implode(', ', $errorArray);
            throw new \Exception($errorMessage);
        }

        $this->email = $email;
        $this->name = $name;
        $this->username = $username;
        $this->share_amount = $share_amount;
        $this->rebill_share_amount = $rebill_share_amount;
        $this->share_item_id = $share_item_id;
        $this->is_valid = true;
    }

    /**
     * Return validity state of the instance
     * @return Boolean
     */

    public function isValid()
    {
        return $this->is_valid;
    }

    /**
     * Return Beneficiary Item ID
     * @return Boolean
     */

    public function getItemId()
    {
        return $this->share_item_id;
    }

    /**
     * Return Beneficiary Tier
     * @return Boolean
     */

    public function getTier()
    {
        return $this->tier;
    }

    /**
     * Return Beneficiary Variables
     * @return Boolean
     */

    public function payload()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (empty($value)) {
                unset($vars[$key]);
            } elseif ($value === true) {
                $vars[$key] = 1;
            } elseif ($value === false) {
                $vars[$key] = 0;
            }
        }
        return $vars;
    }
}
