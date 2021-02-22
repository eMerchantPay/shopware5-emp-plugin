<?php
/*
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

namespace EMerchantPay\Components\Methods;

use EMerchantPay\Components\Base\SdkService;
use EMerchantPay\Components\Constants\EmerchantpayPaymentAttributes;
use EMerchantPay\Components\Constants\SdkSettingKeys;
use EMerchantPay\Components\Models\PaymentData;
use EMerchantPay\Models\Transaction\Repository;
use EMerchantPay\Models\Transaction\Transaction;
use Genesis\API\Constants\Transaction\Types;
use Genesis\API\Notification;
use Genesis\Genesis;
use Genesis\Utils\Currency;

class DirectService extends SdkService
{
    /**
     * The Method
     *
     * @return string
     */
    public function getMethod()
    {
        return parent::METHOD_DIRECT;
    }

    /**
     * The actual Request attributes
     *
     * @param PaymentData $paymentData
     * @return Genesis
     */
    public function setGenesisRequestProperties(PaymentData $paymentData)
    {
        $transactionId = $this->generateTransactionId(self::PLATFORM_TRANSACTION_PREFIX);

        $this->genesis
            ->request()
            ->setTransactionId($transactionId)
            ->setRemoteIp($_SERVER['REMOTE_ADDR'])
            ->setUsage('Payment via ' . $this->getShopName())
            ->setAmount($paymentData->getAmount())
            ->setCurrency($paymentData->getCurrency())

            // CreditCard Data
            ->setCardHolder($paymentData->getCcFullName())
            ->setCardNumber($paymentData->getCcNumber())
            ->setExpirationMonth($paymentData->getCcExpiryMonth())
            ->setExpirationYear($paymentData->getCcExpiryYear())
            ->setCvv($paymentData->getCcCvv())

            // Customer Details
            ->setCustomerEmail($paymentData->getEmail())
            ->setCustomerPhone($paymentData->getPhone())

            // Billing/Invoice Details
            ->setBillingFirstName($paymentData->getBillingFirstName())
            ->setBillingLastName($paymentData->getBillingLastName())
            ->setBillingAddress1($paymentData->getBillingAddress())
            ->setBillingZipCode($paymentData->getBillingZipcode())
            ->setBillingCity($paymentData->getBillingCity())
            ->setBillingState($paymentData->getBillingState())
            ->setBillingCountry($paymentData->getBillingCountry())

            // Shipping Details
            ->setShippingFirstName($paymentData->getShippingFirstName())
            ->setShippingLastName($paymentData->getShippingLastName())
            ->setShippingAddress1($paymentData->getShippingAddress())
            ->setShippingZipCode($paymentData->getShippingZipcode())
            ->setShippingCity($paymentData->getShippingCity())
            ->setShippingState($paymentData->getShippingState())
            ->setShippingCountry($paymentData->getShippingCountry());

        // Urls
        if (Types::is3D($this->getTransactionType())) {
            $this->genesis
                ->request()
                ->setNotificationUrl($paymentData->getNotificationUrl())
                ->setReturnSuccessUrl(
                    $paymentData->getSuccessUrl() . '&' . http_build_query([
                        EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID => $transactionId
                    ])
                )
                ->setReturnFailureUrl(
                    $paymentData->getFailureUrl() . '&' . http_build_query([
                        EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID => $transactionId
                    ])
                );
        }

        return $this->genesis;
    }

    /**
     * Retrieve the Genesis's transaction type used for the request
     *
     * @return string
     */
    public function getTransactionType()
    {
        return $this->getConfig()[SdkSettingKeys::TRANSACTION_TYPES][0];
    }

    /**
     * @param Notification $notificationObject
     * @throws \Exception
     */
    public function processNotification($notificationObject)
    {
        /** @var \stdClass $reconcileObject */
        $reconcileObject = $notificationObject->getReconciliationObject();

        /** @var Repository $transactionRepository */
        $transactionRepository = $this->getTransactionRepository();

        // Load the Transaction Model
        /** @var Transaction $transaction */
        $transaction = $transactionRepository->loadByPaymentToken(
            $reconcileObject->transaction_id,
            $this->generatePaymentToken($reconcileObject)
        );

        $order = $this->loadOrder($transaction->getOrderId());

        if (!$order) {
            throw new \Exception('Order not found');
        }

        if ($transaction->getUniqueId() === $reconcileObject->unique_id) {
            // update the transaction
            // No reference transaction. This is the transaction approval
            $this->updateTransaction($transaction, $notificationObject);
        }

        if ($transaction->getUniqueId() !== $reconcileObject->unique_id) {
            // record new transaction
            // this is reference transaction case. This can happen if we have events created from Genesis Admin
            // TODO Not tested yet
            $this->addTransaction($transaction, $notificationObject);
        }

        $orderRepository = Shopware()->Modules()->Order();
        $this->saveOrderState(
            $orderRepository,
            $order,
            $reconcileObject->status,
            $reconcileObject->transaction_type
        );
    }

    /**
     * @param Transaction $transaction
     * @param Notification $notificationObject
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateTransaction($transaction, $notificationObject)
    {
        /** @var \stdClass $reconcileObject */
        $reconcileObject = $notificationObject->getReconciliationObject();

        $transaction->setStatus($reconcileObject->status);
        $transaction->setUpdatedAt(new \DateTime());

        if (isset($reconcileObject->message)) {
            $transaction->setMessage($transaction->getMessage() . PHP_EOL . $reconcileObject->message);
        }
        if (isset($reconcileObject->technical_message)) {
            $transaction->setTechnicalMessage(
                $transaction->getTechnicalMessage() . PHP_EOL . $reconcileObject->technical_message
            );
        }

        $request   = unserialize($transaction->getRequest());
        $request[] = (array)$reconcileObject;
        $transaction->setRequest(serialize($request));

        $response   = unserialize($transaction->getResponse());
        $response[] = $notificationObject->generateResponse();
        $transaction->setResponse(serialize($response));

        $this->modelsManager->persist($transaction);
        $this->modelsManager->flush();
    }

    /**
     * @param Transaction $transaction
     * @param Notification $notificationObject
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function addTransaction($transaction, $notificationObject)
    {
        /** @var \stdClass $reconcileObject */
        $reconcileObject = $notificationObject->getReconciliationObject();

        $paymentTransaction = new Transaction();
        $paymentTransaction->setTransactionId($transaction->getTransactionId());
        $paymentTransaction->setUniqueId($reconcileObject->unique_id);
        $paymentTransaction->setReferenceId($reconcileObject->reference_unique_id);
        $paymentTransaction->setPaymentToken($transaction->getPaymentToken());
        $paymentTransaction->setTerminalToken($transaction->getTerminalToken());
        $paymentTransaction->setMode($transaction->getMode());
        $paymentTransaction->setStatus($reconcileObject->status);
        $paymentTransaction->setTransactionType($reconcileObject->transaction_type);
        $paymentTransaction->setAmount(
            Currency::amountToExponent($reconcileObject->amount, $reconcileObject->currency)
        );
        $paymentTransaction->setCurrency($reconcileObject->currency);
        $paymentTransaction->setPaymentMethod($this->getMethod());
        $paymentTransaction->setOrderId($transaction->getOrderId());
        $paymentTransaction->setMessage(isset($reconcileObject->message) ? $reconcileObject->message : '');
        $paymentTransaction->setTechnicalMessage(
            isset($reconcileObject->technical_message) ? $reconcileObject->technical_message : ''
        );
        $paymentTransaction->setRequest(serialize([0 => (array)$reconcileObject]));
        $paymentTransaction->setResponse(serialize([0 => $notificationObject->generateResponse()]));
        $paymentTransaction->setCreatedAt(new \DateTime());
        $paymentTransaction->setUpdatedAt(new \DateTime());

        $this->modelsManager->persist($paymentTransaction);
        $this->modelsManager->flush();
    }
}
