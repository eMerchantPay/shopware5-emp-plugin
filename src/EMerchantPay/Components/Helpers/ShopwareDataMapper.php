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

namespace EMerchantPay\Components\Helpers;

use EMerchantPay\Components\Models\PaymentData;
use EMerchantPay\Components\Models\ShopwareData;

/**
 * Transfer Shopware Store data to the payment Data Object
 *
 * Class ShopwareDataMapper
 * @package EMerchantPay\Components\Helpers
 */
class ShopwareDataMapper
{
    /**
     * @var ShopwareDataMapper
     */
    private $shopwareData;

    public function __construct(ShopwareData $shopwareData)
    {
        $this->shopwareData = $shopwareData;
    }

    /**
     * Maps the payment data
     *
     * @return PaymentData
     */
    public function getPaymentData()
    {
        $paymentData = new PaymentData();
        $paymentData->setAmount($this->shopwareData->getAmount());
        $paymentData->setCurrency($this->shopwareData->getCurrencyShortName());
        $paymentData->setEmail($this->shopwareData->getUser()['email']);
        $paymentData->setPhone($this->shopwareData->getBillingAddress()['phone']);

        // Billing
        $paymentData->setBillingFirstName($this->shopwareData->getBillingAddress()['firstname']);
        $paymentData->setBillingLastName($this->shopwareData->getBillingAddress()['lastname']);
        $paymentData->setBillingAddress($this->shopwareData->getBillingAddress()['street']);
        $paymentData->setBillingZipcode($this->shopwareData->getBillingAddress()['zipcode']);
        $paymentData->setBillingCity($this->shopwareData->getBillingAddress()['city']);
        $paymentData->setBillingState($this->shopwareData->getBillingAddress()['State']);
        $paymentData->setBillingCountry($this->shopwareData->getCountry()['countryiso']);

        // As Default Shipping Address is same as the billing
        $paymentData->setShippingFirstName($this->shopwareData->getBillingAddress()['firstname']);
        $paymentData->setShippingLastName($this->shopwareData->getBillingAddress()['lastname']);
        $paymentData->setShippingAddress($this->shopwareData->getBillingAddress()['street']);
        $paymentData->setShippingZipcode($this->shopwareData->getBillingAddress()['zipcode']);
        $paymentData->setShippingCity($this->shopwareData->getBillingAddress()['city']);
        $paymentData->setShippingState($this->shopwareData->getBillingAddress()['State']);
        $paymentData->setShippingCountry($this->shopwareData->getCountry()['countryiso']);

        // Shipping
        if (!empty($this->shopwareData->getShippingAddress())) {
            // Shipping Address differs the Billing Address
            $paymentData->setShippingFirstName($this->shopwareData->getShippingAddress()['firstname']);
            $paymentData->setShippingLastName($this->shopwareData->getShippingAddress()['lastname']);
            $paymentData->setShippingAddress($this->shopwareData->getShippingAddress()['street']);
            $paymentData->setShippingZipcode($this->shopwareData->getShippingAddress()['zipcode']);
            $paymentData->setShippingCity($this->shopwareData->getShippingAddress()['city']);
            $paymentData->setShippingState($this->shopwareData->getShippingAddress()['state']);
            $paymentData->setShippingCountry(
                (empty($this->shopwareData->getCountryShipping())) ?
                    $this->shopwareData->getCountry()['countryiso'] :
                    $this->shopwareData->getCountryShipping()['countryiso']
            );
        }

        $paymentData->setNotificationUrl($this->shopwareData->getNotificationUrl());
        $paymentData->setSuccessUrl($this->shopwareData->getSuccessUrl());
        $paymentData->setCancelUrl($this->shopwareData->getCancelUrl());
        $paymentData->setFailureUrl($this->shopwareData->getFailureUrl());

        return $paymentData;
    }
}
