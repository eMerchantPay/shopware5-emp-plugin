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

namespace EMerchantPay\Components\Helpers;

use EMerchantPay\Models\Transaction\Transaction;

/**
 * Helper for Simple Transaction Tree
 *
 * Class TransactionTree
 * @package EMerchantPay\Components\Helpers
 */
class TransactionTree
{
    /**
     * LEAF By Status
     */
    const APPROVED_LEAF         = 'approved';

    /**
     * Structure Constants
     */
    const BRANCH_NODE           = 'references';

    /**
     * Data Constants
     */
    const DATA_STATUS           = 'status';
    const DATA_UNIQUE_ID        = 'unique_id';
    const DATA_TRANSACTION_ID   = 'transaction_id';
    const DATA_REFERENCE_ID     = 'reference_id';
    const DATA_TRANSACTION_TYPE = 'transaction_type';
    const DATA_AMOUNT           = 'amount';
    const DATA_CURRENCY         = 'currency';


    /**
     * Builds a tree from given array with transactions
     *      array(
     *              [0] => Transactions Model Object,
     *              ...
     *      )
     * Outputs array tree
     *     tree = array (
     * Root           array (
     *                  transaction_id => {data}
     *                  reference_id => {data}
     *                  transaction_type => {data}
     *                  ...
     *                  references => array (
     * Branch              array (
     *                        unique_id => {data}
     *                        transaction_id => {data}
     *                        reference_id => {data}
     *                        transaction_type => {data}
     *                        ...
     *                        references => array (
     *  Leaf                      array (
     *                                unique_id => {data}
     *                                transaction_id => {data}
     *                                reference_id => {data}
     *                                transaction_type => {data}
     *                                ...
     *                                references => array ()
     *                            )
     *                        )
     *                    )
     * Branch             array (
     *                        unique_id => {data}
     *                        transaction_id => {data}
     *                        reference_id => {data}
     *                        transaction_type => {data}
     *                        ...
     *                        references => array (
     * Leaf                       array (
     *                                [unique_id] => {data}
     *                                transaction_id => {data}
     *                                reference_id => {data}
     *                                transaction_type => {data}
     *                                ...
     *                                references => array ()
     *                            )
     * Leaf                       array (
     *                                unique_id => {data}
     *                                transaction_id => {data}
     *                                reference_id => {data}
     *                                transaction_type => {data}
     *                                ...
     *                                references => array ()
     *                            )
     *                        )
     *                    )
     *                )
     *            )
     *
     * @param string $uniqueId
     * @param array $transactions
     * @return array
     */
    public static function buildTree($uniqueId, $transactions)
    {
        $tree = [];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            if ($transaction->getUniqueId() !== $uniqueId) {
                continue;
            }
            $tree[] = self::addNodeData($transaction);

            // Build Branches and Leaves
            $treeKeys = array_keys($tree);
            self::buildReferences($tree[end($treeKeys)], $transactions);
        }

        return $tree;
    }

    /**
     * Find the last approved Transaction Leaf in the transactionTree
     *
     * @param array $transactionTree
     * @param string $uniqueId
     * @return array
     */
    public static function findLastApprovedLeaf($transactionTree, $uniqueId)
    {
        foreach ($transactionTree as $transaction) {
            if ($transaction[self::DATA_UNIQUE_ID] === $uniqueId) {
                if (!empty($transaction[self::BRANCH_NODE])) {
                    // Loop the references for that branch
                    return self::loopBranches($transaction[self::BRANCH_NODE]);
                }

                return $transaction;
            }
        }

        return [];
    }

    /**
     * Build Branches and Leaves recursively
     *
     * @param array $node
     * @param array $transactions
     */
    private static function buildReferences(&$node, $transactions)
    {
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            if ($transaction->getReferenceId() === $node[self::DATA_UNIQUE_ID]) {
                $node[self::BRANCH_NODE][] = self::addNodeData($transaction);
                $nodeKeys = array_keys($node[self::BRANCH_NODE]);
                self::buildReferences(
                    $node[self::BRANCH_NODE][end($nodeKeys)],
                    $transactions
                );
            }
        }
    }

    /**
     * @param Transaction $transaction
     * @return array
     */
    private static function addNodeData($transaction)
    {
        return [
            self::DATA_UNIQUE_ID        => $transaction->getUniqueId(),
            self::DATA_TRANSACTION_ID   => $transaction->getTransactionId(),
            self::DATA_REFERENCE_ID     => $transaction->getReferenceId(),
            self::DATA_STATUS           => $transaction->getStatus(),
            self::DATA_TRANSACTION_TYPE => $transaction->getTransactionType(),
            self::DATA_AMOUNT           => $transaction->getAmount(),
            self::DATA_CURRENCY         => $transaction->getCurrency(),

            // Create empty Branch for every node
            self::BRANCH_NODE  => []
        ];
    }

    /**
     * Walk through all the branches for the specified unique_id.
     * Return the last approved leaf. If approved leaf is missing then return empty array
     *
     * @param array $transactionBranches
     * @param string $status
     * @return array
     */
    private static function loopBranches($transactionBranches, $status = self::APPROVED_LEAF)
    {
        foreach ($transactionBranches as $transaction) {
            if (empty($transaction[self::BRANCH_NODE]) && $transaction[self::DATA_STATUS] === $status) {
                return $transaction;
            }

            if (!empty($transaction[self::BRANCH_NODE])) {
                return self::loopBranches($transaction[self::BRANCH_NODE]);
            }

            return $transaction;
        }

        return [];
    }
}
