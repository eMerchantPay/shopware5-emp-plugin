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

/**
 * Service that Helps with transforming the data all over the Plugin
 *
 * Class TransformCreditCardData
 * @package EMerchantPay\Components\Services
 */
class TransformCreditCardData
{
    /**
     * Apply all transformations
     *
     * @param array $data
     * @return array
     */
    public function call($data)
    {
        $data = $this->creditCardNumber($data);
        $data = $this->creditCardExpiry($data);
        $data = $this->creditCardCvv($data);

        return $data;
    }

    /**
     * Transform Credit Card number
     *
     * @param array $data
     * @return array
     */
    protected function creditCardNumber($data)
    {
        $keys = [
            'cc_number'
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $charLen    = strlen($data[$key]);
                $data[$key] = substr($data[$key], 0, 4) .
                    str_repeat('*', $charLen - 4);
            }
        }

        return $data;
    }

    /**
     * Transform Credit Card Expiry
     *
     * @param array $data
     * @return array
     */
    protected function creditCardExpiry($data)
    {
        $keys = [
            'cc_expiry'
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '** / **';
            }
        }

        return $data;
    }

    /**
     * Transform Credit Card CVV
     *
     * @param array $data
     * @return array
     */
    protected function creditCardCvv($data)
    {
        $keys = [
            'cc_cvv'
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
