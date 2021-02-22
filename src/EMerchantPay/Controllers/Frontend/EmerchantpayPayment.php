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

use EMerchantPay\Components\Constants\EmerchantpayPaymentAttributes;
use EMerchantPay\Controllers\Base\FrontendPaymentAction;

/**
 * Class Shopware_Controllers_Frontend_EmerchantpayPayment
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
//@codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_EmerchantpayPayment extends FrontendPaymentAction
//@codingStandardsIgnoreEnd
{
    /**
     * Check if one of the payment methods is selected. Else return to default controller.
     *
     * Forwards to the correct action.
     */
    public function indexAction()
    {
        switch ($this->getPaymentShortName()) {
            case 'emerchantpay_checkout':
                // Checkout action method. Delivers WPF Functionality
                return $this->redirect($this->getCheckoutUrl() . $this->getUrlParameters());
            case 'emerchantpay_direct':
                // Direct action method. Delivers Processing Transactions Functionality
                return $this->redirect($this->getDirectUrl() . $this->getUrlParameters());
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    /**
     * Creates the url parameters
     */
    private function getUrlParameters()
    {
        $user         = $this->getUser();
        $shopwareData = (new \EMerchantPay\Components\Models\ShopwareData())
            ->setAmount($this->getAmount())
            ->setCurrencyShortName($this->getCurrencyShortName())
            ->setBillingAddress($user['billingaddress'])
            ->setShippingAddress($user['shippingaddress'])
            ->setPayment($user['additional']['payment'])
            ->setState($user['additional']['state'])
            ->setStateShipping($user['additional']['stateshipping'])
            ->setCountry($user['additional']['country'])
            ->setCountryShipping($user['additional']['countryshipping'])
            ->setUser($user['additional']['user'])
            ->setNotificationUrl($this->getNotificationUrl());

        $shopwareData
            ->setSuccessUrl(
                $this->getReturnUrl(
                    EmerchantpayPaymentAttributes::RETURN_ACTION_STATUS_SUCCESS,
                    $shopwareData->getToken()
                )
            )
            ->setCancelUrl(
                $this->getReturnUrl(
                    EmerchantpayPaymentAttributes::RETURN_ACTION_STATUS_CANCEL,
                    $shopwareData->getToken()
                )
            )
            ->setFailureUrl(
                $this->getReturnUrl(
                    EmerchantpayPaymentAttributes::RETURN_ACTION_STATUS_FAILURE,
                    $shopwareData->getToken()
                )
            );

        $this->logger->info('Shopware Data', $this->getPaymentShortName(), $shopwareData->toArray());

        $mapper      = new \EMerchantPay\Components\Helpers\ShopwareDataMapper($shopwareData);
        $paymentData = $mapper->getPaymentData();

        return '?' . $paymentData->toHttpQuery() . '&token=' . $shopwareData->getToken();
    }

    /**
     * Return the URL for Checkout Payment Controller
     *
     * @return mixed
     * @throws Exception
     */
    protected function getCheckoutUrl()
    {
        return $this->Front()->Router()->assemble(
            [
                'controller'  => 'EmerchantpayCheckoutPayment',
                'action'      => 'pay',
                'forceSecure' => true
            ]
        );
    }

    /**
     * Return the URL for Direct Payment Controller
     *
     * @return mixed
     * @throws Exception
     */
    protected function getDirectUrl()
    {
        return $this->Front()->Router()->assemble(
            [
                'controller'  => 'EmerchantpayDirectPayment',
                'action'      => 'credit_card',
                'forceSecure' => true
            ]
        );
    }

    /**
     * Returns the IPN endpoint
     *
     * @return string
     * @throws Exception
     */
    protected function getNotificationUrl()
    {
        return $this->Front()->Router()->assemble(
            [
                'controller'  => 'EmerchantpayReturnPayment',
                'action'      => 'notification',
                'forceSecure' => true
            ]
        );
    }

    /**
     * Generate Return URL for Cancel/Failure/Success endpoint
     *
     * @param string $status
     * @param string $token
     * @return string
     * @throws Exception
     */
    protected function getReturnUrl($status, $token)
    {
        $paramsQuery = http_build_query(
            [
                EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_STATUS  => $status,
                EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN   => $token
            ]
        );
        $endpoint    = $this->Front()->Router()->assemble(
            [
                'controller'  => 'EmerchantpayReturnPayment',
                'action'      => 'return',
                'forceSecure' => true
            ]
        );

        return $endpoint . '?' . $paramsQuery;
    }
}
