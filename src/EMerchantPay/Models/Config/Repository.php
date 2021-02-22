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

namespace EMerchantPay\Models\Config;

use Shopware\Components\Model\ModelRepository;

/**
 * Class EmerchantpayTransactionRepository
 * @package EMerchantPay\Models
 */
class Repository extends ModelRepository
{
    /**
     * Get all config options for given method
     *
     * @param string $method
     * @param string|null $store
     * @return array
     */
    public function getAllByMethod($method, $store = null)
    {
        $params            = [];
        $params['methods'] = $method;

        if (!is_null($store)) {
            $params['stores'] = $store;
        }

        return $this->findBy($params);
    }

    /**
     * Load one option from the database for the given method
     *
     * @param string $option
     * @param string $method
     * @return object|null
     */
    public function loadOption($option, $method)
    {
        return $this->findOneBy(
            [
                'options' => $option,
                'methods' => $method
            ]
        );
    }
}
