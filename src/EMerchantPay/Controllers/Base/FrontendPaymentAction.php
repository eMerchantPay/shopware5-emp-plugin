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

namespace EMerchantPay\Controllers\Base;

use EMerchantPay\Components\Services\EmerchantpayLogger;
use EMerchantPay\Components\Services\ShopwareHelper;
use EMerchantPay\Components\Interfaces\EmerchantpayTokenValidation;
use EMerchantPay\Components\Traits\TokenValidate;
use Shopware\Components\Plugin;
use Shopware_Controllers_Frontend_Payment;

class FrontendPaymentAction extends Shopware_Controllers_Frontend_Payment
{
    use TokenValidate;

    /**
     * @var Plugin $plugin
     */
    protected $plugin;

    /**
     * LoggerInstance
     *
     * @var EmerchantpayLogger
     */
    protected $logger;

    /**
     * Shopware Helper Service Instance
     *
     * @var ShopwareHelper
     */
    protected $shopwareService;

    public function preDispatch()
    {
        $this->plugin          = $this->get('kernel')->getPlugins()['EMerchantPay'];
        $this->logger          = $this->get('emerchantpay.plugin_logger_service');
        $this->shopwareService = $this->get('emerchantpay.shopware_helper_service');

        if ($this instanceof EmerchantpayTokenValidation
            && in_array($this->getAction(), $this->getTokenProtectedActions())
        ) {
            $redirect = $this->getRedirectOnInvalidToken();

            if (!is_null($redirect)) {
                // Redirects to the Error Controller
                return $redirect;
            }
        }

        $this->get('template')->addTemplateDir($this->plugin->getPath() . '/Resources/views/');
    }

    /**
     * Redirect to the Error Page and display the error message to the customer
     *
     * @param array $parameters
     * @throws \Exception
     */
    protected function displayError($parameters)
    {
        $errorPageUrl = $this->buildErrorEndpoint($parameters);

        $this->redirect($errorPageUrl);

        // Be sure to stop executing rest of the code
        return;
    }

    /**
     * Build the Error Page
     *
     * @param $parameters
     * @return string
     */
    protected function buildErrorEndpoint($parameters)
    {
        $errorPageUrl = $this->Front()->Router()->assemble(
            [
                'controller'  => 'EmerchantpayPaymentError',
                'action'      => 'index',
                'forceSecure' => true
            ]
        );

        return $errorPageUrl . '?' . http_build_query($parameters);
    }

    /**
     * Extract the Current Controller Action
     *
     * @return mixed|null
     */
    protected function getAction()
    {
        return $this->Request()->getParam('action');
    }
}
