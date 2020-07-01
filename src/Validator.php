<?php

namespace ratapay\ratapayphp;

class Validator
{
    /**
     * Validate period value
     *
     * @param String period value to be validated
     *
     * @return Boolean true for valid period and false for invalid value
     */
    public static function validatePeriod($value)
    {
        $value = filter_var($value, FILTER_SANITIZE_STRING);
        if (empty($value)) {
            return false;
        }

        $value = strtoupper($value);
        $number = null;
        preg_match("/\d+/", $value, $number);
        if (!isset($number[0]) || !is_numeric($number[0])) {
            return false;
        }

        $period = preg_replace("/[0-9]/", '', $value);
        switch ($period) {
            case 'D':
                return true;
                break;
            case 'M':
                return true;
                break;
            case 'Y':
                return true;
                break;
            default:
                return false;
                break;
        }
    }
}
