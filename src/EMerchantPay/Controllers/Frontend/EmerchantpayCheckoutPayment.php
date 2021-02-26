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

use EMerchantPay\Components\Constants\EmerchantpayPaymentAttributes;
use EMerchantPay\Components\Constants\SdkSettingKeys;
use EMerchantPay\Components\Interfaces\EmerchantpayTokenValidation;
use EMerchantPay\Components\Models\PaymentData;
use EMerchantPay\Controllers\Base\FrontendPaymentAction;
use Shopware\Models\Order\Status;

/**
 * Class Shopware_Controllers_Frontend_EmerchantpayCheckoutPayment
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
//@codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_EmerchantpayCheckoutPayment extends FrontendPaymentAction implements EmerchantpayTokenValidation
//@codingStandardsIgnoreEnd
{
    /**
     * Endpoints that will be validated against the token
     *
     * @return array
     */
    public function getTokenProtectedActions()
    {
        return [
            'pay'
        ];
    }

    /**
     * @throws Exception
     */
    public function payAction()
    {
        // Process request
        try {
            // Load the Payment Data
            $paymentData = new PaymentData($this->Request()->getParams());
            $paymentData->setOrderBasket($this->getBasket());

            // Log the Payment Data
            $this->logger->info('Payment Data', $this->getPaymentShortName(), $paymentData->toArray());

            /** @var \EMerchantPay\Components\Methods\CheckoutService $sdkService */
            $sdkService = $this->container->get('emerchantpay.genesis_checkout_service');
            $sdkService->loadShopwareShop($this->container->get('shop'));
            $sdkService->setShopwareUserId($this->getShopwareUserId());
            $sdkService->setShopwareCustomerNumber($this->getShopwareCustomerNumber());

            $genesis = $sdkService->setGenesisRequestProperties($paymentData);
            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            $this->logger->debug('Genesis Response', $this->getPaymentShortName(), (array)$response);

            if ($response->status != \Genesis\API\Constants\Transaction\States::NEW_STATUS) {
                $this->logger->error(
                    $response->message,
                    $this->getPaymentShortName(),
                    (array)$response
                );

                return $this->displayError(['message' => $response->message]);
            }

            // Save the Order
            $orderId = $this->saveOrder(
                $response->transaction_id,
                $response->unique_id,
                Status::PAYMENT_STATE_OPEN
            );

            if (!$orderId) {
                throw new Exception('Error during saving the Order. Order Id can not be set!');
            }

            // Insert Into the DB
            $transactionModel = new EMerchantPay\Models\Transaction\Transaction();
            $transactionModel->setTransactionId($response->transaction_id);
            $transactionModel->setUniqueId($response->unique_id);
            $transactionModel->setReferenceId(null);
            $transactionModel->setTerminalToken(null);
            $transactionModel->setPaymentMethod($sdkService->getMethod());
            $transactionModel->setPaymentToken($sdkService->generatePaymentToken($response));
            $transactionModel->setAuthorizationToken(
                $this->Request()->getParam(EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN)
            );
            $transactionModel->setStatus($response->status);
            $transactionModel->setOrderId($orderId);
            $transactionModel->setShopId($sdkService->getShopId());
            $transactionModel->setTransactionType($response->transaction_type);
            $transactionModel->setAmount(
                \Genesis\Utils\Currency::amountToExponent(
                    $paymentData->getAmount(),
                    $paymentData->getCurrency()
                )
            );
            $transactionModel->setCurrency($paymentData->getCurrency());

            $mode = $sdkService->getConfig()[SdkSettingKeys::MODE] ?
                EmerchantpayPaymentAttributes::PAYMENT_MODE_TEST : EmerchantpayPaymentAttributes::PAYMENT_MODE_LIVE;

            $transactionModel->setMode($mode);
            $transactionModel->setMessage(isset($response->message) ? $response->message : '');
            $transactionModel->setTechnicalMessage(
                isset($response->technical_message) ? $response->technical_message : ''
            );
            $transactionModel->setRequest(serialize([0=>$paymentData->toArray()]));
            $transactionModel->setResponse(serialize([0=>(array) $response]));
            $transactionModel->setCreatedAt(new DateTime());
            $transactionModel->setUpdatedAt(new DateTime());

            // Doctrine Model Service
            /** @var \Shopware\Components\Model\ModelManager $modelManager */
            $modelManager = $this->container->get('models');
            $modelManager->persist($transactionModel);
            $modelManager->flush();

            $this->redirect($response->redirect_url);
        } catch (\Genesis\Exceptions\Exception $e) {
            $this->logger->error(
                'Error during transaction execution -> ' . $e->getMessage(),
                $this->getPaymentShortName(),
                $e->getTrace()
            );

            return $this->displayError(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->debug(
                'Error with the payment data processing -> ' . $e->getMessage(),
                $this->getPaymentShortName(),
                $e->getTrace()
            );

            return $this->displayError(['message' => $e->getMessage()]);
        }
    }
}
