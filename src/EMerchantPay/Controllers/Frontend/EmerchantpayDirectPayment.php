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
use Genesis\API\Constants\Transaction\States;
use Genesis\API\Constants\Transaction\Types;
use Genesis\Utils\Currency;
use Shopware\Models\Order\Status;

/**
 * Class Shopware_Controllers_Frontend_EmerchantpayDirectPayment
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
//@codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_EmerchantpayDirectPayment extends FrontendPaymentAction implements EmerchantpayTokenValidation
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
            'pay',
            'credit_card'
        ];
    }

    /**
     * Endpoint for executing the Direct Processing Transactions
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     * @throws Exception
     */
    public function payAction()
    {
        try {
            // Load the Payment Data
            $paymentData = new PaymentData($this->Request()->getParams());

            // Log the Payment Data
            $this->logger->info('Payment Data', $this->getPaymentShortName(), $paymentData->toArray());

            /** @var \EMerchantPay\Components\Methods\DirectService $sdkService */
            $sdkService  = $this->container->get('emerchantpay.genesis_direct_service');
            $sdkService->loadShopwareShop($this->container->get('shop'));

            $genesis = $sdkService->setGenesisRequestProperties($paymentData);
            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            $this->logger->debug('Genesis Response', $this->getPaymentShortName(), (array) $response);

            $redirectUrl = $this->appendTransactionId(
                $paymentData->getSuccessUrl(),
                $genesis->request()->getTransactionId()
            );

            switch ($response->status) {
                case States::PENDING_ASYNC:
                    // TODO Add Order Comment
                    if (isset($response->threeds_method_url)) {
                        throw new \Exception(
                            'Currently, there is no support for 3DSv2 Credit Card authentication. ' .
                            'Consider to use different Credit Card'
                        );
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

                    if (isset($response->redirect_url)) {
                        $redirectUrl = $response->redirect_url;
                    }
                    break;
                case States::APPROVED:
                    // Save the Order
                    // TODO Add Order Comment

                    $status = Status::PAYMENT_STATE_COMPLETELY_INVOICED;
                    if (Types::isAuthorize($response->transaction_type)) {
                        $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
                    }
                    // Save the Order
                    $orderId = $this->saveOrder(
                        $response->transaction_id,
                        $response->unique_id,
                        $status
                    );

                    if (!$orderId) {
                        throw new Exception('Error during saving the Order. Order Id can not be set!');
                    }
                    break;
                case States::DECLINED:
                case States::ERROR: // Genesis SDK throw Error for Sync Request with Error status
                    // TODO Add Order Comment
                    $this->logger->error(
                        $response->message,
                        $this->getPaymentShortName(),
                        (array)$response
                    );

                    throw new \Exception($response->message);
                    break;
            }

            /** @var \EMerchantPay\Components\Helpers\TransformCreditCardData $creditCardDataTransformer */
            $creditCardDataTransformer = $this->container->get('emerchantpay.transform_credit_card_data');

            // Save transaction to the Database
            $transactionModel = new EMerchantPay\Models\Transaction\Transaction();
            $transactionModel->setTransactionId($response->transaction_id);
            $transactionModel->setUniqueId($response->unique_id);
            $transactionModel->setReferenceId($response->reference_id);
            $transactionModel->setPaymentMethod($sdkService->getMethod());
            $transactionModel->setPaymentToken($sdkService->generatePaymentToken($response));
            $transactionModel->setAuthorizationToken(
                $this->Request()->getParam(EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN)
            );
            $transactionModel->setTerminalToken($sdkService->getConfig()[SdkSettingKeys::TOKEN]);
            $transactionModel->setStatus($response->status);
            $transactionModel->setOrderId($orderId);
            $transactionModel->setShopId($sdkService->getShopId());
            $transactionModel->setTransactionType($response->transaction_type);
            $transactionModel->setAmount(
                Currency::amountToExponent(
                    $response->amount,
                    $response->currency
                )
            );
            $transactionModel->setCurrency($response->currency);

            $mode = $sdkService->getConfig()[SdkSettingKeys::MODE] ?
                EmerchantpayPaymentAttributes::PAYMENT_MODE_TEST : EmerchantpayPaymentAttributes::PAYMENT_MODE_LIVE;

            $transactionModel->setMode($mode);
            $transactionModel->setMessage(isset($response->message) ? $response->message : '');
            $transactionModel->setTechnicalMessage(
                isset($response->technical_message) ? $response->technical_message : ''
            );
            $transactionModel->setRequest(serialize([0=>$creditCardDataTransformer->call($paymentData->toArray())]));
            $transactionModel->setResponse(serialize([0=>(array) $response]));
            $transactionModel->setCreatedAt(new DateTime());
            $transactionModel->setUpdatedAt(new DateTime());

            /** @var \Shopware\Components\Model\ModelManager $emService */
            $emService = $this->container->get('models');
            $emService->persist($transactionModel);
            $emService->flush();

            $this->redirect($redirectUrl);
            return;
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

    /**
     * Endpoint for the Credit Card data
     *
     * @throws Exception
     */
    public function creditCardAction()
    {
        try {
            list($author, $method) = explode('_', $this->getPaymentShortName());
            $paymentData = new PaymentData($this->Request()->getParams());
            $actionUrl   = $this->Front()->Router()->assemble(
                [
                    'method'      => 'frontend',
                    'controller'  => 'EmerchantpayDirectPayment',
                    'action'      => 'pay',
                    'forceSecure' => true
                ]
            );

            $data = [
                'method' => $author . ' Payment',
                'button' => 'Checkout with ' . $author . ' ' . ucfirst($method),
                'action' => $this->appendToken(
                    $actionUrl,
                    $this->Request()->getParam(EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN)
                ),
                'params' => $paymentData->toArray()
            ];

            $this->View()->assign($data);
        } catch (\Exception $e) {
            $this->logger->debug(
                'Error with the transaction data -> ' . $e->getMessage(),
                $this->getPaymentShortName(),
                $e->getTrace()
            );

            return $this->displayError(['message' => $e->getMessage()]);
        }
    }

    /**
     * Helper function for append the Transaction Id to the given url
     *
     * @param $url
     * @param $trxId
     * @return string
     */
    private function appendTransactionId($url, $trxId)
    {
        return $url . '&' . EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID . '=' . $trxId;
    }

    /**
     * Helper function for append the Token to the given url
     *
     * @param $url
     * @param $token
     * @return string
     */
    private function appendToken($url, $token)
    {
        return $url . '?' . EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN . '=' . $token;
    }
}
