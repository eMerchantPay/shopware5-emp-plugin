<?php
/**
 * Copyright (C) 2021 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay
 * @copyright   2021 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Components\Models;

use EMerchantPay\Components\Base\DataAdapter;
use Genesis\Exceptions\Exception;

/**
 * Payment Transaction Data
 *
 * Class PaymentRequest
 *
 * @package EMerchantPay\Components\EmerchantpayPayment
 *
 * Setters
 *
 * @method $this setAmount($value)            Payment Amount
 * @method $this setCurrency($value)          Payment Currency
 * @method $this setEmail($value)             Customer Payment info - email
 * @method $this setPhone($value)             Customer Payment info - phone
 * @method $this setBillingFirstName($value)  Customer Payment info - First Name
 * @method $this setBillingLastName($value)   Customer Payment info - Last Name
 * @method $this setBillingAddress($value)    Customer Payment info - Address
 * @method $this setBillingZipcode($value)    Customer Payment info - ZipCode
 * @method $this setBillingCity($value)       Customer Payment info - City
 * @method $this setBillingState($value)      Customer Payment info - State
 * @method $this setBillingCountry($value)    Customer Payment info - Country
 * @method $this setShippingFirstName($value) Customer Shipping info - First Name
 * @method $this setShippingLastName($value)  Customer Shipping info - Last Name
 * @method $this setShippingAddress($value)   Customer Shipping info - Address
 * @method $this setShippingZipcode($value)   Customer Shipping info - ZipCode
 * @method $this setShippingCity($value)      Customer Shipping info - City
 * @method $this setShippingState($value)     Customer Shipping info - State
 * @method $this setShippingCountry($value)   Customer Shipping info - Country
 * @method $this setNotificationUrl($value)   Payment Notification Controller
 * @method $this setSuccessUrl($value)        Payment Success Controller
 * @method $this setCancelUrl($value)         Payment Cancel Url
 * @method $this setFailureUrl($value)        Payment Failure Url
 * @method $this setCcNumber($value)          Credit Card Number
 * @method $this setCcFullName($value)        Credit Card Full Name
 * @method $this setCcCvv($value)             Credit Cart CVV
 * @method $this setOrderBasket($value)       Shopware Basket
 * @method $this setUser($value)              Current user
 *
 * Getters
 *
 * @method string getAmount()                 Payment Amount
 * @method string getCurrency()               Payment Currency
 * @method string getEmail()                  Customer Payment info - email
 * @method string getPhone()                  Customer Payment info - phone
 * @method string getBillingFirstName()       Customer Payment info - First Name
 * @method string getBillingLastName()        Customer Payment info - Last Name
 * @method string getBillingAddress()         Customer Payment info - Address
 * @method string getBillingZipcode()         Customer Payment info - ZipCode
 * @method string getBillingCity()            Customer Payment info - City
 * @method string getBillingState()           Customer Payment info - State
 * @method string getBillingCountry()         Customer Payment info - Country
 * @method string getShippingFirstName()      Customer Shipping info - First Name
 * @method string getShippingLastName()       Customer Shipping info - Last Name
 * @method string getShippingAddress()        Customer Shipping info - Address
 * @method string getShippingZipcode()        Customer Shipping info - ZipCode
 * @method string getShippingCity()           Customer Shipping info - City
 * @method string getShippingState()          Customer Shipping info - State
 * @method string getShippingCountry()        Customer Shipping info - Country
 * @method string getNotificationUrl()        Payment Notification Controller
 * @method string getSuccessUrl()             Payment Success Controller
 * @method string getCancelUrl()              Payment Cancel Url
 * @method string getFailureUrl()             Payment Failure Url
 * @method string getCcNumber()               Credit Card Number
 * @method string getCcFullName()             Credit Card Full Name
 * @method string getCcExpiry()               Credit Card Expiry
 * @method string getCcCvv()                  Credit Cart CVV
 * @method array getOrderBasket()             Shopware Basket
 * @method array getUser()                    Current user
 */
class PaymentData extends DataAdapter
{
    /**
     * Fields used for Payment Request
     *
     * @var array $fields
     */
    private $fields = [
        'amount',
        'currency',
        'email',
        'phone',
        'billing_first_name',
        'billing_last_name',
        'billing_address',
        'billing_zipcode',
        'billing_city',
        'billing_state',
        'billing_country',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address',
        'shipping_zipcode',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'notification_url',
        'success_url',
        'cancel_url',
        'failure_url',
        'cc_number',
        'cc_full_name',
        'cc_expiry',
        'cc_cvv',
        'order_basket',
        'user'
    ];

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $value
     *
     * @return PaymentData
     * @throws \Exception
     */
    public function setCcExpiry($value)
    {
        if (!preg_match('/^([\d]{2})(|\s)\/(|\s)([\d]{2})$/', $value)) {
            // Format (MM / y)
            throw new Exception('Invalid Credit Card Expiration date');
        }

        return $this->setProperty('CcExpiry', $value);
    }

    /**
     * Extract the Credit Card Expiration Month(dateFormat: MM) from the cc_expiry(dateFormat MM / y)
     */
    public function getCcExpiryMonth()
    {
        $trimmedString = $this->deepTrim($this->getCcExpiry());

        return explode('/', $trimmedString)[0];
    }

    /**
     * Extract the Credit Card Expiration Year(dateFormat: YY) from the cc_expiry(dateFormat: MM / y)
     *
     * @return string
     */
    public function getCcExpiryYear()
    {
        $trimmedString = $this->deepTrim($this->getCcExpiry());
        $yearNow       = substr(date('Y'), 0, 2);
        $expiryYear    = substr(explode('/', $trimmedString)[1], -2);

        return $yearNow . $expiryYear;
    }

    /**
     * Build Description Information for the Transaction
     *
     * @param string $lineSeparator
     * @return string
     */
    public function buildOrderDescriptionText($lineSeparator = PHP_EOL)
    {
        $orderDescriptionText = '';

        $orderItems = [];
        if (is_array($this->getOrderBasket()) && array_key_exists('content', $this->getOrderBasket())) {
            $orderItems = $this->getOrderBasket()['content'];
        }

        foreach ($orderItems as $item) {
            $separator = ($item == end($orderItems)) ? '' : $lineSeparator;

            $orderDescriptionText .=
                $item['quantity'] .
                ' x ' .
                $item['articlename'] .
                $separator;
        }

        return $orderDescriptionText;
    }

    /**
     * @param  string $value
     * @return string
     */
    private function deepTrim($value)
    {
        return preg_replace('/[\s\t]*/', '', $value);
    }

    /**
     * Gets items of current order
     *
     * @return array|mixed
    */
    public function getOrderItems()
    {

        $orderItems = [];
        if (is_array($this->getOrderBasket()) &&
            array_key_exists('content', $this->getOrderBasket())
        ) {
            $orderItems = $this->getOrderBasket()['content'];
        }

        return $orderItems;
    }
}
