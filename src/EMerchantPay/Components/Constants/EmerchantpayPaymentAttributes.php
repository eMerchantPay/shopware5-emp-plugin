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

namespace EMerchantPay\Components\Constants;

/**
 * Class EmerchantpayPaymentValues
 */
class EmerchantpayPaymentAttributes
{
    /**
     * Status for Action with Success Payment redirected from Genesis
     */
    const RETURN_ACTION_STATUS_SUCCESS       = 'success';

    /**
     * Status for Action with Failure Payment redirected from Genesis
     */
    const RETURN_ACTION_STATUS_FAILURE       = 'failure';

    /**
     * Status for Action with Cancel Payment redirected from Genesis
     */
    const RETURN_ACTION_STATUS_CANCEL        = 'cancel';

    /**
     * Return action param used for status param
     */
    const RETURN_ACTION_PARAM_STATUS         = 'status';

    /**
     * Return Action params used for saving the Order
     */
    const RETURN_ACTION_PARAM_TRANSACTION_ID = 'transaction_id';

    /**
     * Param name used for validating the authenticity of every request
     */
    const RETURN_ACTION_PARAM_TOKEN          = 'token';

    /**
     * Plugin Payment Mode Live
     */
    const PAYMENT_MODE_LIVE                  = 'live';

    /**
     * Plugin Payment Mode TEST
     */
    const PAYMENT_MODE_TEST                  = 'test';
}
