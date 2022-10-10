<?php

namespace ratapay\ratapayphp;

class Item
{
    /**
     * Item ID
     * @var Int
     */
    protected $id;

    /**
     * Item Qty
     * @var Int
     */
    protected $qty;

    /**
     * Item Subtotal
     * @var Int
     */
    protected $subtotal;

    /**
     * Item Name
     * @var String
     */
    protected $name;

    /**
     * Item Type
     * @var String
     */
    protected $type;

    /**
     * Item Category
     * @var String
     */
    protected $category;

    /**
     * Item Brand
     * @var String
     */
    protected $brand;

    /**
     * Item Refundable
     * @var Boolean
     */
    protected $refundable;

    /**
     * Item Refund Threshold
     * @var String
     */
    protected $refund_threshold;

    /**
     * Item Is Valid
     * @var Boolean
     */
    protected $is_valid;

    /**
     * Construct an Invoice Instance
     * Sanitizing and validating all parameters
     */
    public function __construct($data)
    {
        $id = isset($data['id']) ? $data['id'] : null;
        $qty = isset($data['qty']) ? $data['qty'] : 1;
        $subtotal = isset($data['subtotal']) ? $data['subtotal'] : null;
        $name = isset($data['name']) ? $data['name'] : null;
        $type = isset($data['type']) ? $data['type'] : null;
        $category = isset($data['category']) ? $data['category'] : null;
        $brand = isset($data['brand']) ? $data['brand'] : null;
        $refundable = isset($data['refundable']) ? $data['refundable'] : false;
        $refund_threshold = isset($data['refund_threshold']) ? $data['refund_threshold'] : null;

        if (is_null($id)) {
            throw new \Exception('Item ID is Required');
        } elseif (is_null($subtotal)) {
            throw new \Exception('Item subtotal is Required');
        } elseif (is_null($name)) {
            throw new \Exception('Item Name is Required');
        }

        $invalids = [];

        // Sanitize ID
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        if (!$id) {
            $invalids['id'] = 'Invalid Item ID Value';
        } elseif (strlen($id) > 32) {
            $invalids['id'] = 'Invalid Item ID Value, Too Long, Max 32';
        }

        // Validate Qty
        $qty = filter_var($qty, FILTER_VALIDATE_INT);
        if (!$qty) {
            $invalids['qty'] = 'Invalid Item Qty Value';
        }

        // Validate Subtotal
        $subtotal = filter_var($subtotal, FILTER_VALIDATE_INT);
        if ($subtotal === false) {
            $invalids['subtotal'] = 'Invalid Item Subtotal Value';
        }

        // Sanitize Name
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if (!$name) {
            $invalids['name'] = 'Invalid Item Name Value';
        } elseif (strlen($name) > 128) {
            $invalids['name'] = 'Invalid Item Name Value, Too Long, Max 128';
        }

        // Sanitize Type
        if (!is_null($type)) {
            $type = filter_var($type, FILTER_SANITIZE_STRING);
            if (!$type) {
                $invalids['type'] = 'Invalid Item Type Value';
            } elseif (strlen($type) > 64) {
                $invalids['type'] = 'Invalid Item Type Value, Too Long, Max 64';
            }
        }

        // Sanitize Category
        if (!is_null($category)) {
            $category = filter_var($category, FILTER_SANITIZE_STRING);
            if (!$category) {
                $invalids['category'] = 'Invalid Item Category Value';
            } elseif (strlen($category) > 64) {
                $invalids['category'] = 'Invalid Item Category Value, Too Long, Max 64';
            }
        }

        // Sanitize Brand
        if (!is_null($brand)) {
            $brand = filter_var($brand, FILTER_SANITIZE_STRING);
            if (!$brand) {
                $invalids['brand'] = 'Invalid Item Brand Value';
            } elseif (strlen($brand) > 64) {
                $invalids['brand'] = 'Invalid Item Brand Value, Too Long, Max 64';
            }
        }

        // Validate Refundable
        $refundable = filter_var($refundable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($refundable)) {
            $invalids['refundable'] = 'Invalid Item Refundable Value';
        }

        // Validate Refund Threshold
        if (!is_null($refund_threshold)) {
            if (!Validator::validatePeriod($refund_threshold)) {
                $invalids['refund_threshold'] = 'Invalid Item Refund Threshold Value';
            }
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

        $this->id = $id;
        $this->qty = $qty;
        $this->subtotal = $subtotal;
        $this->name = $name;
        $this->type = $type;
        $this->category = $category;
        $this->brand = $brand;
        $this->refundable = $refundable;
        $this->refund_threshold = $refund_threshold;
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
     * Return Item ID
     * @return Boolean
     */

    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Return Beneficiary Variables
     * @return Boolean
     */

    public function payload()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ($value === 0) {
                $vars[$key] = 0;
            } elseif ($value === true) {
                $vars[$key] = 1;
            } elseif ($value === false) {
                $vars[$key] = 0;
            } elseif (empty($value)) {
                unset($vars[$key]);
            }
        }
        return $vars;
    }
}
