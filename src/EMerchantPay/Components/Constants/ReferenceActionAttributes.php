<?php
/**
 * Copyright (C) 2018 emerchantpay Ltd.
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
 * @copyright   2020 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Components\Constants;

/**
 * Class ReferenceActionAttributes
 * @package EMerchantPay\Components\Constants
 */
class ReferenceActionAttributes
{
    /**
     * Reference Action Capture
     */
    const ACTION_CAPTURE     = 'capture';

    /**
     * Reference Action Refund
     */
    const ACTION_REFUND      = 'refund';

    /**
     * Reference Action Void
     */
    const ACTION_VOID        = 'void';

    /**
     * Reference Key Order Id
     */
    const KEY_ORDER_ID       = 'order_id';

    /**
     * Reference Key Payment Token
     */
    const KEY_PAYMENT_TOKEN  = 'payment_token';

    /**
     * Reference Key Action
     */
    const KEY_ACTION         = 'action';

    /**
     * Reference Key Method
     */
    const KEY_METHOD         = 'method';

    /**
     * Reference Key Transaction Id
     */
    const KEY_TRANSACTION_ID = 'transaction_id';
}
