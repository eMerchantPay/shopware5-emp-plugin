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

use EMerchantPay\Components\Constants\ReferenceActionAttributes as ActionAttributes;
use EMerchantPay\Components\Constants\SdkSettingKeys;
use EMerchantPay\Components\Helpers\TransactionTree;
use EMerchantPay\Components\Methods\CheckoutService;
use EMerchantPay\Components\Methods\DirectService;
use EMerchantPay\Components\Services\EmerchantpayConfig;
use Genesis\API\Constants\Transaction\States;
use Genesis\API\Constants\Transaction\Types;

/**
 * Class Shopware_Controllers_Backend_EmerchantpayReferenceTransaction
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
//@codingStandardsIgnoreStart
class Shopware_Controllers_Backend_EmerchantpayReferenceTransaction extends Shopware_Controllers_Backend_ExtJs
//@codingStandardsIgnoreEnd
{
    /**
     * @var \EMerchantPay\Components\Services\EmerchantpayLogger
     */
    protected $logger;

    /**
     * @var EmerchantpayConfig
     */
    protected $config;

    public function preDispatch()
    {
        $this->logger = $this->get('emerchantpay.plugin_logger_service');
        $this->config = $this->get('emerchantpay.plugin_config_service');

        parent::preDispatch();
    }

    public function referenceTransactionAction()
    {
        try {
            $parameters = $this->parseParameters($this->Request()->getParams());

            /** @var EMerchantPay\Models\Transaction\Repository $transactionRepository */
            $transactionRepository = $this->container->get('models')->getRepository(
                EMerchantPay\Models\Transaction\Transaction::class
            );

            $initialTransaction = $transactionRepository->loadByMerchantTransactionAndOrder(
                $parameters[ActionAttributes::KEY_TRANSACTION_ID],
                $parameters[ActionAttributes::KEY_ORDER_ID]
            );

            if (!$initialTransaction) {
                throw new \Exception('No transaction found in the Database');
            }

            if ($initialTransaction->getPaymentToken() !== $parameters['payment_token']) {
                throw new Exception('Payment mismatch for the given transaction.');
            }

            // Retrieve all transaction for the given Order
            $transactions = $transactionRepository->loadAllByOrder($initialTransaction->getOrderId());

            $transactionTree = TransactionTree::buildTree($initialTransaction->getUniqueId(), $transactions);

            $lastMeaningfulTransaction = TransactionTree::findLastApprovedLeaf(
                $transactionTree,
                $initialTransaction->getUniqueId()
            );

            /** @var EMerchantPay\Models\Transaction\Transaction $actionTransaction */
            $actionTransaction = clone $initialTransaction;
            if ($initialTransaction->getUniqueId() !== $lastMeaningfulTransaction[TransactionTree::DATA_UNIQUE_ID]) {
                // get the latest DB Model data
                $referenceTransaction = $transactionRepository->loadByTransactionIdByUniqueId(
                    $lastMeaningfulTransaction[TransactionTree::DATA_TRANSACTION_ID],
                    $lastMeaningfulTransaction[TransactionTree::DATA_UNIQUE_ID]
                );
            }

            if (isset($referenceTransaction) && $referenceTransaction) {
                unset($actionTransaction);
                $actionTransaction = clone $referenceTransaction;
                unset($referenceTransaction);
            }

            if ($actionTransaction->getStatus() !== States::APPROVED) {
                throw new \Exception('Approved Transaction not found. Reference Actions are not allowed.');
            }

            // Validate the action
            if (!$this->isValidAction($parameters['action'], $actionTransaction->getTransactionType())) {
                throw new \Exception(
                    sprintf(
                        'Action %s is not valid for transaction type %s',
                        ucfirst($parameters[ActionAttributes::KEY_ACTION]),
                        ucfirst($actionTransaction->getTransactionType())
                    )
                );
            }

            // Load the Service Provider
            if ($actionTransaction->getPaymentMethod() === CheckoutService::METHOD_CHECKOUT) {
                /** @var CheckoutService $sdkService */
                $sdkService = $this->container->get('emerchantpay.genesis_checkout_service');
            }

            if ($actionTransaction->getPaymentMethod() === DirectService::METHOD_DIRECT) {
                /** @var DirectService $sdkService */
                $sdkService = $this->container->get('emerchantpay.genesis_direct_service');
            }

            if (!isset($sdkService)) {
                throw new Exception('Error during loading the emerchantpay service. Contact with admin.');
            }

            switch ($parameters[ActionAttributes::KEY_ACTION]) {
                case ActionAttributes::ACTION_CAPTURE:
                    $result = $sdkService->doCapture($actionTransaction);
                    break;
                case ActionAttributes::ACTION_REFUND:
                    $result = $sdkService->doRefund($actionTransaction);
                    break;
                case ActionAttributes::ACTION_VOID:
                    $result = $sdkService->doVoid($actionTransaction);
                    break;
            }

            $sdkService->processReferenceResponse($actionTransaction);

            $amountInfo = sprintf(
                'Amount: %s %s',
                $result->response()->getResponseObject()->amount,
                $result->response()->getResponseObject()->currency
            );

            $this->view->assign([
                'status'  => $result->response()->getResponseObject()->status,
                'action'  => ucfirst($parameters[ActionAttributes::KEY_ACTION]),
                'message' => $result->response()->getResponseObject()->amount ? $amountInfo : ''
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                $e->getMessage(),
                $this->Request()->getParam('paymentMethod') ?
                    $this->Request()->getParam('paymentMethod') : 'Unknown',
                $e->getTrace()
            );

            $this->view->assign([
                'status'  => 'error',
                'action'  => $this->Request()->getParam('transactionAction') ?
                    ucfirst($this->Request()->getParam('transactionAction')) : 'unknown',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Transform the Request params
     *
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function parseParameters($params)
    {
        if (!array_key_exists('orderId', $params)) {
            throw new \Exception('Invalid request given.');
        }

        if (!array_key_exists('paymentToken', $params)) {
            throw new \Exception('Invalid request given.');
        }

        if (!array_key_exists('transactionAction', $params)) {
            throw new \Exception('Invalid request given.');
        }

        if (!array_key_exists('paymentMethod', $params)) {
            throw new \Exception('Invalid request given.');
        }

        return [
            ActionAttributes::KEY_ORDER_ID       => $params['orderId'],
            ActionAttributes::KEY_PAYMENT_TOKEN  => $params['paymentToken'],
            ActionAttributes::KEY_ACTION         => $params['transactionAction'],
            ActionAttributes::KEY_METHOD         => $params['paymentMethod'],
            ActionAttributes::KEY_TRANSACTION_ID => $params['transactionId']
        ];
    }

    /**
     * Validate the Request action
     *
     * @param string $action
     * @param string $transactionType
     * @return bool
     * @throws Exception
     */
    private function isValidAction($action, $transactionType)
    {
        if ($this->isTransactionWithCustomAttribute($transactionType)) {
            return $this->checkReferenceActionByCustomAttr($action, $transactionType);
        }

        switch ($action) {
            case ActionAttributes::ACTION_CAPTURE:
                return Types::canCapture($transactionType);
                break;
            case ActionAttributes::ACTION_REFUND:
                return Types::canRefund($transactionType);
                break;
            case ActionAttributes::ACTION_VOID:
                return Types::canVoid($transactionType);
                break;
            default:
                throw new \Exception('Invalid Reference action given');
        }
    }

    /**
     * Check if special validation should be applied
     *
     * @param $transactionType
     * @return bool
     */
    private function isTransactionWithCustomAttribute($transactionType)
    {
        $transactionTypes = [
            Types::GOOGLE_PAY,
            Types::PAY_PAL,
            Types::APPLE_PAY,
        ];

        return in_array($transactionType, $transactionTypes);
    }

    /**
     * Check if canCapture, canRefund and canVoid
     *
     * @param $action
     * @param $transactionType
     * @return bool
     */
    private function checkReferenceActionByCustomAttr($action, $transactionType)
    {
        switch ($transactionType) {
            case Types::GOOGLE_PAY:
                if (ActionAttributes::ACTION_CAPTURE === $action || ActionAttributes::ACTION_VOID === $action) {
                    return in_array(
                        EmerchantpayConfig::GOOGLE_PAY_TRANSACTION_PREFIX .
                        EmerchantpayConfig::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                        $this->getCheckoutConfig()[SdkSettingKeys::TRANSACTION_TYPES]
                    );
                }

                if ($action === ActionAttributes::ACTION_REFUND) {
                    return in_array(
                        EmerchantpayConfig::GOOGLE_PAY_TRANSACTION_PREFIX .
                        EmerchantpayConfig::GOOGLE_PAY_PAYMENT_TYPE_SALE,
                        $this->getCheckoutConfig()[SdkSettingKeys::TRANSACTION_TYPES]
                    );
                }
                break;
            case Types::PAY_PAL:
                if (ActionAttributes::ACTION_CAPTURE === $action || ActionAttributes::ACTION_VOID === $action) {
                    return in_array(
                        EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX .
                        EmerchantpayConfig::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
                        $this->getCheckoutConfig()[SdkSettingKeys::TRANSACTION_TYPES]
                    );
                }

                if ($action === ActionAttributes::ACTION_REFUND) {
                    $refundableTypes = [
                        EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX .
                        EmerchantpayConfig::PAYPAL_PAYMENT_TYPE_SALE,
                        EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX .
                        EmerchantpayConfig::PAYPAL_PAYMENT_TYPE_EXPRESS,
                    ];

                    return (
                        count(
                            array_intersect(
                                $refundableTypes,
                                $this->getCheckoutConfig()[SdkSettingKeys::TRANSACTION_TYPES]
                            )
                        ) > 0
                    );
                }
                break;
            case Types::APPLE_PAY:
                if (ActionAttributes::ACTION_CAPTURE === $action || ActionAttributes::ACTION_VOID === $action) {
                    return in_array(
                        EmerchantpayConfig::APPLE_PAY_TRANSACTION_PREFIX .
                        EmerchantpayConfig::APPLE_PAY_TYPE_AUTHORIZE,
                        $this->getCheckoutConfig()[SdkSettingKeys::TRANSACTION_TYPES]
                    );
                }
                if ($action === ActionAttributes::ACTION_REFUND) {
                    return in_array(
                        EmerchantpayConfig::APPLE_PAY_TRANSACTION_PREFIX .
                        EmerchantpayConfig::APPLE_PAY_TYPE_SALE,
                        $this->getCheckoutConfig()[SdkSettingKeys::TRANSACTION_TYPES]
                    );
                }
                break;
            default:
                return false;
        } // end Switch

        return false;
    }

    /**
     * Get Checkout Config settings
     *
     * @return array
     */
    private function getCheckoutConfig()
    {
        return $this->config->getConfigByMethod(\EMerchantPay\Components\Base\SdkService::METHOD_CHECKOUT);
    }
}
