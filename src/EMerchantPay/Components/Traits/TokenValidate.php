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

namespace EMerchantPay\Components\Traits;

use EMerchantPay\Components\Constants\EmerchantpayPaymentAttributes;
use EMerchantPay\Components\Models\ShopwareData;

/**
 * Trait TokenValidate
 * @package EMerchantPay\Components\Traits
 */
trait TokenValidate
{
    /**
     * Validate the Payment Token
     *
     * @return mixed
     */
    protected function getRedirectOnInvalidToken()
    {
        if (!$this->isValidToken()) {
            $this->logger->debug(
                'Invalid Token in the given Request data',
                $this->getPaymentShortName(),
                $this->Request()->getParams()
            );

            return $this->displayError(
                [
                    'message' => 'Error during Payment page authentication.'
                ]
            );
        }

        return null;
    }

    /**
     * Check if the Given token into the Request is valid
     *
     * @return boolean
     */
    protected function isValidToken()
    {
        $shopwareData = (new ShopwareData())
            ->setAmount($this->getTokenAmount())
            ->setUser($this->getTokenUserData());

        return $this->shopwareService->isValidToken(
            $shopwareData,
            $this->Request()->getParam(EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN)
        );
    }

    /**
     * Get the Current amount from the basket
     *
     * @return float
     */
    protected function getTokenAmount()
    {
        return $this->getAmount();
    }

    /**
     * Get the current User or Recreate the User array with empty customernumber
     *
     * @return array|null
     */
    protected function getTokenUserData()
    {
        $user         = [];
        $user         = (method_exists($this, 'getUser')) ?
            $this->getUser() : $user['additional']['user']['customernumber'] = null;

        return $user['additional']['user'];
    }
}
