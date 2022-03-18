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

namespace EMerchantPay\Components\Services;

use EMerchantPay\Components\Constants\SdkSettingKeys;
use EMerchantPay\Models\Config\Methods;
use EMerchantPay\Models\Config\Repository;
use Shopware\Components\Model\ModelManager;

/**
 * Provides the settings for the emerchantpay Payment Methods
 *
 * Class Config
 * @package EMerchantPay\Components\Services
 */
class EmerchantpayConfig
{
    /**
     * PPRO transaction suffix
     */
    const PPRO_TRANSACTION_SUFFIX = '_ppro';

    /**
     * Google Pay Transaction constants
     */
    const GOOGLE_PAY_TRANSACTION_PREFIX     = 'google_pay_';
    const GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE = 'authorize';
    const GOOGLE_PAY_PAYMENT_TYPE_SALE      = 'sale';

    /**
     * PayPal Transaction constants
     */
    const PAYPAL_TRANSACTION_PREFIX         = 'pay_pal_';
    const PAYPAL_PAYMENT_TYPE_AUTHORIZE     = 'authorize';
    const PAYPAL_PAYMENT_TYPE_SALE          = 'sale';
    const PAYPAL_PAYMENT_TYPE_EXPRESS       = 'express';

    /**
     * Apple Pay Transaction contstants
     */
    const APPLE_PAY_TRANSACTION_PREFIX      = 'apple_pay_';
    const APPLE_PAY_TYPE_AUTHORIZE          = 'authorize';
    const APPLE_PAY_TYPE_SALE               = 'sale';

    /**
     * The name of the Plugin
     *
     * @var string $pluginName
     */
    protected $pluginName;

    /**
     * @var EmerchantpayLogger
     */
    protected $pluginLogger;

    /**
     * @var ModelManager $modelsManager
     */
    protected $modelsManager;

    /**
     * Config constructor.
     * @param string              $pluginName    The name of the Plugin
     * @param EmerchantpayLogger $pluginLogger  The plugin Logger Service
     * @param ModelManager        $modelsManager The models manager Shopware Service
     */
    public function __construct($pluginName, $pluginLogger, $modelsManager)
    {
        $this->pluginName    = $pluginName;
        $this->pluginLogger  = $pluginLogger;
        $this->modelsManager = $modelsManager;
    }

    /**
     * Retrieve the config for given method
     *
     * @param string $method
     * @return array
     */
    public function getConfigByMethod($method)
    {
        $data = [];

        try {
            /** @var Repository $configRepository */
            $configRepository = $this->modelsManager->getRepository(Methods::class);
            $methodConfigs    = $configRepository->getAllByMethod($method);

            if (empty($methodConfigs)) {
                return $data;
            }

            /** @var Methods $config */
            foreach ($methodConfigs as $config) {
                $data[$config->getOption()] = $this->parseOptionValue(
                    $config->getOption(),
                    $config->getOptionValue()
                );
            }
        } catch (\Exception $e) {
            $this->pluginLogger->error(
                $e->getMessage(),
                $this->pluginName,
                $e->getTrace()
            );
        }

        return $data;
    }

    /**
     * Parse option values
     *
     * @param string $option
     * @param string $value
     * @return mixed
     */
    private function parseOptionValue($option, $value)
    {
        switch ($option) {
            case SdkSettingKeys::MODE:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
        }

        return $value;
    }
}
