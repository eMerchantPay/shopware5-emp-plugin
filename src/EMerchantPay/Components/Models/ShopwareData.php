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
use EMerchantPay\Components\Constants\EmerchantpayPaymentAttributes;
use EMerchantPay\Components\Services\ShopwareHelper;

/**
 * Class ShopwareData
 *
 * @package EMerchantPay\Components\Models
 *
 * Setters
 *
 * @method $this setAmount($value)            Set the amount of the Transaction
 * @method $this setCurrencyShortName($value) Set the Currency in ISO format
 * @method $this setBillingAddress($value)    Set the Customer Billing
 * @method $this setShippingAddress($value)   Set the Customer Shipping
 * @method $this setPayment($value)           Set the Plugin
 * @method $this setState($value)             Set the Billing Country state
 * @method $this setStateShipping($value)     Set the Shipping Country State
 * @method $this setCountry($value)           Set the Billing Country
 * @method $this setCountryShipping($value)   Set the Shipping Country
 * @method $this setUser($value)              Set the Shopware User info
 * @method $this setNotificationUrl($value)   Set the Notification Url
 * @method $this setSuccessUrl($value)        Set the Success Url
 * @method $this setCancelUrl($value)         Set the Cancel Url
 * @method $this setFailureUrl($value)        Set the Failure Url
 * @method $this setToken($value)             Set the Token
 *
 * Getters
 *
 * @method string getAmount()                 Get the amount of the Transaction
 * @method string getCurrencyShortName()      Get the Currency in ISO format
 * @method array  getBillingAddress()         Get the Customer Billing
 * @method array  getShippingAddress()        Get the Customer Shipping
 * @method array  getPayment()                Get the Plugin
 * @method array  getState()                  Get the Billing Country state
 * @method array  getStateShipping()          Get the Shipping Country State
 * @method array  getCountry()                Get the Billing Country
 * @method array  getCountryShipping()        Get the Shipping Country
 * @method array  getUser()                   Get the Shopware User info
 * @method string getNotificationUrl()        Get the Notification Url
 * @method string getSuccessUrl()             Get the Success Url
 * @method string getCancelUrl()              Get the Cancel Url
 * @method string getFailureUrl()             Get the Failure Url
 */
class ShopwareData extends DataAdapter
{

    private $fields = [
        'amount',
        'currency_short_name',
        'billing_address',
        'shipping_address',
        'payment',
        'state',
        'state_shipping',
        'country',
        'country_shipping',
        'user',
        'notification_url',
        'success_url',
        'cancel_url',
        'failure_url',
        'token'
    ];

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get the Token used for verification of the transferred payment data
     * If the payment token is not set manually will be generated
     *
     * @return string
     */
    public function getToken()
    {
        $data = $this->getData();

        if (!array_key_exists(EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN, $data)) {
            $shopwareService = new ShopwareHelper();

            $token = $shopwareService->createPaymentToken(
                $this->getAmount(),
                $this->getUser()['customernumber']
            );
            $this->setToken($token);

            return $token;
        }

        return $data['token'];
    }
}
