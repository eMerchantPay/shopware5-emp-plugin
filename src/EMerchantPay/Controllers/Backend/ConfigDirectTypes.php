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

use Genesis\API\Constants\Transaction\Names;
use Genesis\API\Constants\Transaction\Types as TransactionTypes;

/**
 * Class Shopware_Controllers_Backend_ConfigDirectTypes
 */
class Shopware_Controllers_Backend_ConfigDirectTypes extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Endpoint for retrieving the available Direct transaction types via Ajax
     */
    public function listTypesAction()
    {
        $data = [
            [
                'value'  => TransactionTypes::AUTHORIZE,
                'option' => Names::getName(TransactionTypes::AUTHORIZE),
            ],
            [
                'value'  => TransactionTypes::AUTHORIZE_3D,
                'option' => Names::getName(TransactionTypes::AUTHORIZE_3D),
            ],
            [
                'value'  => TransactionTypes::SALE,
                'option' => Names::getName(TransactionTypes::SALE),
            ],
            [
                'value'  => TransactionTypes::SALE_3D,
                'option' => Names::getName(TransactionTypes::SALE_3D),
            ]
        ];

        $this->view->assign(
            [
                'data' => $data
            ]
        );
    }
}
