<?php
/*
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

use Shopware\Components\Logger;

/**
 * Class EmerchantpayLogger
 * @package EMerchantPay\Components\Services
 *
 * @codingStandardsIgnoreStart
 * @method debug(string $message, string $method = '', array $context = [])     Record Debug Message. Developer Mode Required.
 * @method info(string $message, string $method = '', array $context = [])      Record Info Message. Developer Mode Required.
 * @method error(string $message, string $method = '', array $context = [])     Record Error Message.
 * @method notice(string $message, string $method = '', array $context = [])    Record Notice Message. Developer Mode Required.
 * @method warning(string $message, string $method = '', array $context = [])   Record Notice Message. Developer Mode Required.
 * @method critical(string $message, string $method = '', array $context = [])  Record Notice Message.
 * @method alert(string $message, string $method = '', array $context = [])     Record Notice Message. Developer Mode Required.
 * @method emergency(string $message, string $method = '', array $context = []) Record Notice Message
 * @codingStandardsIgnoreEnd
 * @SuppressWarnings(PHPMD.LongVariable)
 */
final class EmerchantpayLogger
{
    /**
     * @var Logger $shopwareLogger
     */
    private $shopwareLogger;

    /**
     * EmerchantpayLogger constructor.
     * @param Logger $pluginLogger
     */
    public function __construct(Logger $pluginLogger)
    {
        $this->shopwareLogger = $pluginLogger;
    }

    /**
     * Magic Accessor for the methods
     * Available methods:
     *     DEBUG
     *     INFO
     *     NOTICE
     *     WARNING
     *     ERROR
     *     CRITICAL
     *     ALERT
     *     EMERGENCY
     * Arguments
     *      $message ([0]) string
     *      $method  ([1]) string
     *      $context ([2]) array
     *
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (!array_key_exists(0, $arguments)) {
            throw new \Exception('The Message parameter is required for logging.');
        }
        $message = $arguments[0];

        if (array_key_exists(1, $arguments)) {
            $message = !empty($arguments[1]) ? 'Method ' . ucfirst($arguments[1]) . ': ' . $arguments[0] : $message;
        }

        if (!array_key_exists(2, $arguments)) {
            $arguments[2] = [];
        }

        $this->shopwareLogger->{$name}($message, $arguments[2]);
    }

    /**
     * Get the Shopware Logger Instance
     *
     * @return Logger
     */
    public function getLoggerInstance()
    {
        return $this->shopwareLogger;
    }
}
