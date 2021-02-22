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

namespace EMerchantPay\Models\Transaction;

use Shopware\Components\Model\ModelRepository;

/**
 * Class EmerchantpayTransactionRepository
 * @package EMerchantPay\Models
 */
class Repository extends ModelRepository
{
    /**
     * @param string $transactionId
     * @param string $authorizationToken
     * @return object|null
     * @throws \Exception
     */
    public function loadByAuthorizationToken($transactionId, $authorizationToken)
    {
        $transaction = $this->findOneBy(
            [
                'transaction_id' => $transactionId,
                'authorization_token' => $authorizationToken
            ]
        );

        if (!$transaction) {
            throw new \Exception('Transaction can not be fetched in the database!');
        }

        return $transaction;
    }

    /**
     * @param $transactionId
     * @param $paymentToken
     * @return object|null
     * @throws \Exception
     */
    public function loadByPaymentToken($transactionId, $paymentToken)
    {
        $transaction = $this->findOneBy(
            [
                'transaction_id' => $transactionId,
                'payment_token' => $paymentToken
            ],
            [
                'id' => 'DESC'
            ]
        );

        if (!$transaction) {
            throw new \Exception('Transaction can not be fetched in the database!');
        }

        return $transaction;
    }

    /**
     * Not in use.
     * Example use of the Entity
     *
     * @param Transaction $transaction
     * @param $status
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateTransactionStatus($transaction, $status)
    {
        $transaction->setStatus($status);
        $transaction->setUpdatedAt(new \DateTime());

        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }

    /**
     * Load transactions records assigned to a record
     *
     * @param int $orderNumber
     * @return array
     */
    public function loadAllByOrder($orderNumber)
    {
        return $this->findBy(['order_id' => $orderNumber]);
    }

    /**
     * Retrieve transaction by reference ID
     *
     * @param string $referenceId
     * @return object|null
     */
    public function loadByReferenceId($referenceId)
    {
        return $this->findOneBy(['reference_id' => $referenceId]);
    }

    /**
     * Load the initial transaction for given transaction id and order id
     *
     * @param string $transactionId
     * @param string $orderId
     * @return object|null
     */
    public function loadByMerchantTransactionAndOrder($transactionId, $orderId)
    {
        return $this->findOneBy(
            [
                'transaction_id' => $transactionId,
                'order_id' => $orderId
            ],
            [
                'id' => 'ASC'
            ]
        );
    }

    /**
     * @param $transactionId
     * @param $uniqueId
     * @return object|null
     */
    public function loadByTransactionIdByUniqueId($transactionId, $uniqueId)
    {
        return $this->findOneBy(
            [
                'transaction_id' => $transactionId,
                'unique_id'      => $uniqueId
            ]
        );
    }
}
