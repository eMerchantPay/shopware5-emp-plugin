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

use EMerchantPay\Controllers\Base\FrontendAction;

class Shopware_Controllers_Frontend_EmerchantpayPaymentError extends FrontendAction
{
    /**
     * Default Payment Title
     *
     * @var string $title
     */
    protected $title = 'Payment Error';

    /**
     * Default State of the error (error, warning, info etc...)
     *
     * @var string $state
     */
    protected $state = 'ERROR';

    public function indexAction()
    {
        $params = $this->Request()->getParams();

        $this->view->assign(
            [
                'message' => array_key_exists('message', $params) ? $params['message'] : 'Undefined Error',
                'state'   => array_key_exists('state', $params) ? $params['state'] : $this->state,
                'title'   => array_key_exists('title', $params) ? $params['title'] : $this->title
            ]
        );
    }
}
