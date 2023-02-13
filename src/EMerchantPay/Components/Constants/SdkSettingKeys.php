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

use Genesis\Utils\Common as CommonUtils;

/**
 * Contains constants used for Plugin Settings
 *
 * Class SdkSettingKeys
 * @package EMerchantPay\Components\Constants
 */
class SdkSettingKeys
{
    /**
     * Method Mode
     *      Test: true
     *      Live: false
     */
    const MODE              = 'test_mode';

    /**
     * Genesis Username
     *      string
     */
    const USERNAME          = 'username';

    /**
     * Genesis Password
     *      string
     */
    const PASSWORD          = 'password';

    /**
     * Genesis Token
     *      string
     */
    const TOKEN             = 'token';

    /**
     * Genesis Transaction Types
     * Checkout - array of transaction types
     */
    const TRANSACTION_TYPES = 'transaction_types';

    /**
     * Genesis WPF Checkout Language
     */
    const CHECKOUT_LANGUAGE = 'checkout_language';

    /**
     * Genesis WPF Tokenization
     */
    const WPF_TOKENIZATION = 'wpf_tokenization';

    /**
     * Payment methods for Online banking transaction type
     */
    const BANK_CODES = 'bank_codes';

    /**
     * Genesis 3DSv2 option
     */
    const THREEDS_OPTION = 'threeds_option';

    /**
     * Genesis challenge indicator option
     */
    const CHALLENGE_INDICATOR = 'challenge_indicator';

    /**
     * Genesis SCA exemption option
     */
    const SCA_EXEMPTION_OPTION = 'sca_exemption_option';

    /**
     *  Genesis SCA exemption amount
     */
    const SCA_EXEMPTION_AMOUNT = 'sca_exemption_amount';

    /**
     * Get All available setting keys
     *
     * @return array
     */
    public static function getAll()
    {
        return CommonUtils::getClassConstants(self::class);
    }
}
