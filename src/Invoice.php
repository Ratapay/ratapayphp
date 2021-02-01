<?php

namespace ratapay\ratapayphp;

class Invoice
{
    /**
     * Invoice Note
     * @var String
     */
    protected $note;

    /**
     * Payer Email Address
     * @var String
     */
    protected $email;
    
    /**
     * Payer Name
     * @var String
     */
    protected $name;

    /**
     * Merchant Invoice ID
     * @var String
     */
    protected $invoice_id;

    /**
     * Invoice First Total Amount
     * @var Integer
     */
    protected $amount;
    
    /**
     * Invoice Second Total Amount
     * @var Integer
     */
    protected $second_amount;

    /**
     * Period before the next rebill invoice
     * with format %d%s e.g. 7D
     * @var String
     */
    protected $first_period;

    /**
     * period frequency of the concurrent rebill invoice
     * with format %d%s e.g. 7D
     * @var String
     */
    protected $second_period;

    /**
     * how many times should rebill invoice generated
     * @var Integer
     */
    protected $rebill_times;

    /**
     * Wether Invoice is refundable
     * @var Boolean
     */
    protected $refundable;
    
    /**
     * Period within the invoice can be refunded
     * with format %d%s e.g. 7D
     * @var String
     */
    protected $refund_threshold;
    
    /**
     * merchant URL where ratapay will send callback to
     * @var String
     */
    protected $url_callback;
    
    /**
     * URL where ratapay will redirect payer after successful payment
     * @var String
     */
    protected $url_success;
    
    /**
     * URL where ratapay will redirect payer after failed payment
     * @var String
     */
    protected $url_failed;
    
    /**
     * An array of invoice items
     * @var Item[]
     */
    protected $items = [];
    
    /**
     * An array of invoice item IDs
     * @var Int[]
     */
    protected $item_ids = [];
    
    /**
     * Paysystem specified for the Invoice
     * @var String
     */
    protected $paysystem;
    
    /**
     * An array of invoice beneficiaries
     * @var BeneficiaryClass[]
     */
    protected $beneficiaries = [];

    /**
     * Construct an Invoice Instance
     * Sanitizing and validating all parameters
     */
    public function __construct($data)
    {
        $note = isset($data['note']) ? $data['note'] : null;
        $email = isset($data['email']) ? $data['email'] : null;
        $invoice_id = isset($data['invoice_id']) ? $data['invoice_id'] : null;
        $amount = isset($data['amount']) ? $data['amount'] : null;

        if (is_null($note)) {
            throw new \Exception('Invoice Note is Required');
        } elseif (is_null($email)) {
            throw new \Exception('Invoice Email is Required');
        } elseif (is_null($invoice_id)) {
            throw new \Exception('Invoice ID is Required');
        } elseif (is_null($amount)) {
            throw new \Exception('Invoice Amount is Required');
        }

        $second_amount = isset($data['second_amount']) ? $data['second_amount'] : 0;
        $first_period = isset($data['first_period']) ? $data['first_period'] : null;
        $second_period = isset($data['second_period']) ? $data['second_period'] : null;
        $rebill_times = isset($data['rebill_times']) ? $data['rebill_times'] : null;
        $refundable = isset($data['refundable']) ? $data['refundable'] : false;
        $refund_threshold = isset($data['refund_threshold']) ? $data['refund_threshold'] : null;
        $url_callback = isset($data['url_callback']) ? $data['url_callback'] : null;
        $url_success = isset($data['url_success']) ? $data['url_success'] : null;
        $url_failed = isset($data['url_failed']) ? $data['url_failed'] : null;
        $paysystem = isset($data['paysystem']) ? $data['paysystem'] : null;
        
        $invalids = [];
        // Sanitize Note
        $note = filter_var($note, FILTER_SANITIZE_STRING);
        if (!$note) {
            $invalids['note'] = 'Invalid Invoice Note Value';
        }
        
        // Sanitize & Validate Email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (empty($email)) {
            $invalids['email'] = 'Invalid Invoice Email Value';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalids['email'] = 'Invalid Invoice Email Value';
        }
        
        // Sanitize Invoice ID
        $invoice_id = filter_var($invoice_id, FILTER_SANITIZE_STRING);
        if (!$invoice_id) {
            $invalids['invoice_id'] = 'Invalid Invoice ID Value';
        }
        
        // Validate Amount
        $amount = filter_var($amount, FILTER_VALIDATE_INT);
        if (!$amount) {
            $invalids['amount'] = 'Invalid Invoice Amount Value';
        }
        
        // Validate Second Amount
        if (!is_null($second_amount)) {
            $second_amount = filter_var($second_amount, FILTER_VALIDATE_INT);
            if (!is_numeric($second_amount)) {
                $invalids['second_amount'] = 'Invalid Invoice Second Amount Value';
            }
        }

        // Validate First Period
        if (!is_null($first_period)) {
            if (!Validator::validatePeriod($first_period)) {
                $invalids['first_period'] = 'Invalid Invoice First Period Value';
            }
        }

        // Validate Second Period
        if (!is_null($second_period)) {
            if (!Validator::validatePeriod($second_period)) {
                $invalids['second_period'] = 'Invalid Invoice Second Period Value';
            }
        }

        // Validate Rebill Times
        if (!is_null($rebill_times)) {
            $rebill_times = filter_var($rebill_times, FILTER_VALIDATE_INT);
            if (!$rebill_times) {
                $invalids['rebill_times'] = 'Invalid Invoice Rebill Times Value';
            }
        }

        // Validate Refundable
        $refundable = filter_var($refundable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($refundable)) {
            $invalids['refundable'] = 'Invalid Invoice Refundable Value';
        }

        // Validate Refund Threshold
        if (!is_null($refund_threshold)) {
            if (!Validator::validatePeriod($refund_threshold)) {
                $invalids['refund_threshold'] = 'Invalid Invoice Refund Threshold Value';
            }
        }

        // Validate Callback URL
        if (!is_null($url_callback)) {
            $url_callback = filter_var($url_callback, FILTER_VALIDATE_URL);
            if (!$url_callback) {
                $invalids['url_callback'] = 'Invalid Invoice URL Callback Value';
            }
        }

        // Validate Success URL
        if (!is_null($url_success)) {
            $url_success = filter_var($url_success, FILTER_VALIDATE_URL);
            if (!$url_success) {
                $invalids['url_success'] = 'Invalid Invoice URL Success Value';
            }
        }

        // Validate Failed URL
        if (!is_null($url_failed)) {
            $url_failed = filter_var($url_failed, FILTER_VALIDATE_URL);
            if (!$url_failed) {
                $invalids['$url_failed'] = 'Invalid Invoice URL Failed Value';
            }
        }

        // Sanitize Paysystem
        $paysystem = filter_var($paysystem, FILTER_SANITIZE_STRING);
        if (!$paysystem) {
            $invalids['paysystem'] = 'Invalid Invoice ID Value';
        }
        
        if (!empty($invalids)) {
            $errorArray = [];
            foreach ($invalids as $field => $message) {
                $errorArray[] = $field . ': ' . $message;
            }
            $errorMessage = implode(', ', $errorArray);
            throw new \Exception($errorMessage);
        }

        $this->note = $note;
        $this->email = $email;
        $this->invoice_id = $invoice_id;
        $this->amount = $amount;
        $this->second_amount = $second_amount;
        $this->first_period = $first_period;
        $this->second_period = $second_period;
        $this->rebill_times = $rebill_times;
        $this->refundable = $refundable;
        $this->refund_threshold = $refund_threshold;
        $this->url_callback = $url_callback;
        $this->url_success = $url_success;
        $this->url_failed = $url_failed;
        $this->paysystem = $paysystem;
    }

    /**
     * Add One Item Object to Invoice
     *
     * @param Item $item Item to be added
     *
     * @return none
     */
    public function addItem(Item $item)
    {
        if (count($this->beneficiaries) > 0) {
            return false;
        }
        if (!$item->isValid()) {
            return false;
        }
        $this->items[] = $item;
        $this->item_ids[] = $item->getId();
        return true;
    }
    
    /**
     * Clear Invoice Items
     *
     * @return none
     */
    public function clearItem()
    {
        $this->items = [];
        return true;
    }

    /**
     * Add One Beneficiary Object to Invoice
     *
     * @param Beneficiary $beneficiary Benficiary to be added
     *
     * @return none
     */
    public function addBeneficiary(Beneficiary $beneficiary)
    {
        if (!$beneficiary->isValid()) {
            return false;
        }
        $item_id = $beneficiary->getItemId();
        if (count($this->items) > 0 &&
            ($item_id == null || !in_array($item_id, $this->item_ids))) {
            return false;
        }
        $this->beneficiaries[] = $beneficiary;
        return true;
    }
    
    /**
     * Clear Invoice Beneficiaries
     *
     * @return none
     */
    public function clearBeneficiary()
    {
        $this->beneficiaries = [];
        return true;
    }
    
    /**
     * get Amount
     *
     * @return Int
     */
    public function getAmount($type = 'first')
    {
        if ($type == 'first') {
            return $this->amount;
        } else {
            return $this->second_amount;
        }
    }
    
    /**
     * get Beneficiaries
     *
     * @return Beneficiary[]
     */
    public function getBeneficiaries()
    {
        return $this->beneficiaries;
    }
    
    /**
     * get Items
     *
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * get Item IDs
     *
     * @return Array
     */
    public function getItemIds()
    {
        return $this->item_ids;
    }

    public function validate()
    {
        if (!empty($this->items) && array_sum(array_column($this->items, 'subtotal')) > $this->amount) {
            return (object)['valid' => false, 'message' => 'Items Total Amount Exceeds Invoice Amount'];
        }
        if (!empty($this->beneficiaries)
            && array_sum(array_column($this->beneficiaries, 'share_amount')) > $this->amount) {
            return (object)['valid' => false, 'message' => 'Beneficiaries Total Share Amount Exceeds Invoice Amount'];
        }
        
        // validate second amount
        if (!empty($this->beneficiaries)
            && array_sum(array_column($this->beneficiaries, 'rebill_share_amount')) > $this->second_amount) {
            return (object)[
                'valid' => false,
                'message' => 'Beneficiaries Total Rebill Share Amount Exceeds Invoice Amount'
            ];
        }
        
        // check all item is validated
        foreach ($this->items as $item) {
            if (!$item->isValid()) {
                return (object)['valid' => false, 'message' => 'Item has Not been Validated ' . $item->id];
            }
        }
        
        // check all beneficiary is validated and group it
        foreach ($this->beneficiaries as $beneficiary) {
            if (!$beneficiary->isValid()) {
                return (object)[
                    'valid' => false,
                    'message' => 'Beneficiary has Not been Validated ' . $beneficiary->email
                ];
            } elseif (count($this->item_ids) > 0 && !in_array($beneficiary->getItemId(), $this->item_ids)) {
                return (object)[
                    'valid' => false,
                    'message' => 'Beneficiary not Associated to Any Item ' . $beneficiary->email
                ];
            }
        }
        return (object)['valid' => true, 'message' => 'Valid Invoice'];
    }

    public function payload()
    {
        $vars = get_object_vars($this);
        // clean vars
        unset($vars['item_ids']);
        foreach ($vars as $key => $value) {
            if (empty($value)) {
                unset($vars[$key]);
            } elseif ($value === true) {
                $vars[$key] = 1;
            } elseif ($value === false) {
                $vars[$key] = 0;
            }
        }

        // legacy params
        $vars['source_invoice_id'] = $vars['invoice_id'];

        // process items
        if (isset($vars['items'])) {
            foreach ($vars['items'] as $key => $item) {
                $vars['items'][$key] = $item->payload();
            }
        }

        // process beneficiaries
        if (isset($vars['beneficiaries'])) {
            $vendors = [];
            $affs = [];
            foreach ($vars['beneficiaries'] as $beneficiary) {
                if ($beneficiary->getTier() == 1) {
                    $vendors[] = $beneficiary->payload();
                } else {
                    $affs[] = $beneficiary->payload();
                }
            }
    
            if (!empty($vendors)) {
                $vars['vendor_share'] = $vendors;
            }
    
            if (!empty($affs)) {
                $vars['aff_share'] = $affs;
            }
            unset($vars['beneficiaries']);
        }

        return $vars;
    }
}
