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
use EMerchantPay\Components\Methods\CheckoutService;
use EMerchantPay\Components\Methods\DirectService;
use EMerchantPay\Controllers\Base\FrontendPaymentAction;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_EmerchantpayCheckoutPayment
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
//@codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_EmerchantpayReturnPayment extends FrontendPaymentAction implements CSRFWhitelistAware
//@codingStandardsIgnoreEnd
{
    public function getWhitelistedCSRFActions()
    {
        return [
            'notification'
        ];
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        if (empty(Shopware()->Session()->sUserId)) {
            $this->logger->error(
                'Customer Returned from the payment gateway without active session.',
                $this->getPaymentShortName(),
                $this->Request()->getParams()
            );

            $this->redirect(
                [
                    'controller'  => 'checkout',
                    'action'      => 'confirm',
                    'forceSecure' => true
                ]
            );

            return;
        }

        $parameters = [
            'transactionId' => $this->Request()->getParam(
                EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID
            ),
            'token'         => $this->Request()->getParam(
                EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TOKEN
            ),
            'status'        => $this->Request()->getParam(
                EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_STATUS
            )
        ];

        if (empty($parameters['transactionId']) || empty($parameters['token']) || empty($parameters['status'])) {
            $this->logger->debug(
                'Invalid Return URL parameters received from Genesis',
                $this->getPaymentShortName(),
                $this->Request()->getParams()
            );

            return $this->displayError(
                [
                    'message' => 'Error during Payment Authentication.'
                ]
            );
        }

        /** @var EMerchantPay\Models\Transaction\Repository $transactionRepository */
        $transactionRepository = $this->container->get('models')->getRepository(
            EMerchantPay\Models\Transaction\Transaction::class
        );

        /** @var \EMerchantPay\Models\Transaction\Transaction $transaction */
        $transaction = $transactionRepository->loadByAuthorizationToken(
            $parameters['transactionId'],
            $parameters['token']
        );

        if (!$transaction) {
            $this->logger->error(
                'Payment transaction mismatch, contact admin!',
                $this->getPaymentShortName(),
                $this->Request()->getParams()
            );
        }

        switch ($parameters['status']) {
            case EmerchantpayPaymentAttributes::RETURN_ACTION_STATUS_SUCCESS:
                // The Order States will be handled from the Notification Logic
                $this->logger->info(
                    'Received Success Return URL from Genesis',
                    sprintf('emerchantpay_%s', $transaction->getPaymentMethod()),
                    $parameters
                );

                if ($this->isUserOrder($transaction->getOrderId())) {
                    $this->logger->info(
                        'Successful payment for a guest checkout user received or Order ID mismatch.',
                        $this->getPaymentShortName(),
                        [
                            'sessionOrder' => $this->getOrderNumber(),
                            'parameters'   => $parameters,
                            'transaction'  => (array) $transaction
                        ]
                    );

                    return $this->displayError(
                        [
                            'message' => sprintf(
                                'Successful payment for Order #%s',
                                $transaction->getOrderId()
                            ),
                            'title' => 'Successful Payment',
                            'state' => 'Success'
                        ]
                    );
                }

                $this->redirect(
                    [
                        'controller'  => 'checkout',
                        'action'      => 'finish',
                        'forceSecure' => true
                    ]
                );
                break;
            case EmerchantpayPaymentAttributes::RETURN_ACTION_STATUS_CANCEL:
                $this->logger->info(
                    'Received Cancel Return URL from Genesis',
                    sprintf('emerchantpay_%s', $transaction->getPaymentMethod()),
                    $parameters
                );

                return $this->displayError(
                    [
                        'message' => 'Your payment has been successfully cancelled.',
                        'title'   => 'Payment cancel',
                        'state'   => 'Info'
                    ]
                );
                break;
            case EmerchantpayPaymentAttributes::RETURN_ACTION_STATUS_FAILURE:
                $this->logger->info(
                    'Received Failure Return URL from Genesis',
                    sprintf('emerchantpay_%s', $transaction->getPaymentMethod()),
                    $parameters
                );

                return $this->displayError(
                    [
                        'message' => 'Error acquire during the Payment Process.'
                    ]
                );
                break;
            default:
                $this->redirect(
                    [
                        'controller'  => 'checkout',
                        'action'      => 'cart',
                        'forceSecure' => true
                    ]
                );
                break;
        }
    }

    /**
     * Notification Method
     *
     * Handles the notification send from Genesis
     */
    public function notificationAction()
    {
        $this->logger->info(
            'Notification Received.',
            'genesis_notification',
            $this->Request()->getParams()
        );

        if (array_key_exists('wpf_unique_id', $this->request->getParams())) {
            // Load WPF SDK
            /** @var CheckoutService $sdkService */
            $sdkService = $this->container->get('emerchantpay.genesis_checkout_service');
        }

        if (array_key_exists('unique_id', $this->request->getParams())) {
            // Load Processing SDK
            /** @var DirectService $sdkService */
            $sdkService = $this->container->get('emerchantpay.genesis_direct_service');
        }

        if (!isset($sdkService)) {
            $errorMessage = 'Error during loading the emerchantpay Method Service';
            $this->logger->error(
                $errorMessage,
                'notification',
                (array) $this->Request()->getParams()
            );

            http_response_code(400);
            echo $errorMessage;
            die();
        }

        try {
            $notification = $sdkService->initializeGenesisReconcile($this->Request()->getParams());

            if (!$notification->isAuthentic()) {
                throw new \Exception('Given request is not authentic notification!');
            }

            /** @var \stdClass $reconcileObject */
            $reconcileObject = $notification->initReconciliation();

            $this->logger->debug(
                'Successful Reconciliation',
                $sdkService->getMethod(),
                (array) $reconcileObject
            );

            $sdkService->processNotification($notification);

            http_response_code(200);
            $notification->renderResponse();

            // Exit the script other way the template engine and internal router will handle the rest
            die();
        } catch (\Exception $e) {
            $method = 'notification';
            if (isset($sdkService) &&
                ($sdkService instanceof CheckoutService || $sdkService instanceof DirectService)
            ) {
                $method = $sdkService->getMethod();
            }

            $this->logger->error(
                'Error during notification process: ' . $e->getMessage(),
                $method,
                $e->getTrace()
            );
            http_response_code(400);
            echo $e->getMessage();

            // Exit the script
            die();
        }
    }

    /**
     * Checks if the current session data is correct
     *
     * @param string $orderId
     * @return bool
     */
    private function isUserOrder($orderId)
    {
        return empty(Shopware()->Session()->sUserId) || $this->getOrderNumber() != $orderId;
    }
}
