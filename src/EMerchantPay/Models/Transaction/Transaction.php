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

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class EmerchantpayTransactionModel
 * @package EMerchantPay\Models\Base
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @codingStandardsIgnoreStart
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="emerchantpay_transactions", uniqueConstraints={@ORM\UniqueConstraint(name="unique_id", columns={"transaction_id", "unique_id"})})
 * @codingStandardsIgnoreEnd
 */
class Transaction extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string $transaction_id
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $transaction_id;

    /**
     * @var string $unique_id
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $unique_id;

    /**
     * @var string $reference_id
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $reference_id;

    /**
     * @var string $payment_method
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $payment_method;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $authorization_token;

    /**
     * @var string $terminal_token
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $terminal_token;

    /**
     * @var string $payment_token
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $payment_token;

    /**
     * @var string $status
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $status;

    /**
     * @var string $order_id
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $order_id;

    /**
     * @var int $shop_id
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $shop_id;

    /**
     * @var string $transaction_type
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $transaction_type;

    /**
     * @var int $amount
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $amount;

    /**
     * @var string $currency
     * @ORM\Column(type="string", nullable=true)
     */
    protected $currency;

    /**
     * @var string $mode
     * @ORM\Column(type="string", nullable=true)
     */
    protected $mode;

    /**
     * @var string $message
     * @ORM\Column(type="string", nullable=true)
     */
    protected $message;

    /**
     * @var string $technical_message
     * @ORM\Column(type="string", nullable=true)
     */
    protected $technical_message;

    /**
     * @var string $request
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $request;

    /**
     * @var string $response
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $response;

    /**
     * @var \DateTime $date_create
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_create;

    /**
     * @var \DateTime $date_update
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_update;

    /**
     * The Record Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $value
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    /**
     * Transaction Id
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param string $value
     */
    public function setTransactionId($value)
    {
        $this->transaction_id = $value;
    }

    /**
     * The Genesis Unique Id
     *
     * @return string
     */
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * @param string $value
     */
    public function setUniqueId($value)
    {
        $this->unique_id = $value;
    }

    /**
     * The Reference Transaction Id
     *
     * @return string
     */
    public function getReferenceId()
    {
        return $this->reference_id;
    }

    /**
     * @param string $value
     */
    public function setReferenceId($value)
    {
        $this->reference_id = $value;
    }

    /**
     * The payment method (Checkout) used for the current transaction
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * The payment method (Checkout) used for the current transaction
     *
     * @param string $value
     */
    public function setPaymentMethod($value)
    {
        $this->payment_method = $value;
    }

    /**
     * The Token used for the transaction
     *
     * @return string
     */
    public function getTerminalToken()
    {
        return $this->terminal_token;
    }

    /**
     * @param string $value
     */
    public function setTerminalToken($value)
    {
        $this->terminal_token = $value;
    }

    /**
     * Token used for transaction identification
     *
     * @return string
     */
    public function getPaymentToken()
    {
        return $this->payment_token;
    }

    /**
     * Token used for transaction identification
     *
     * @param string $value
     */
    public function setPaymentToken($value)
    {
        $this->payment_token = $value;
    }

    /**
     * Token used for authorization identification
     * Used only when WPF returns to the cancel URL
     *
     * @return string
     */
    public function getAuthorizationToken()
    {
        return $this->authorization_token;
    }

    /**
     * Token used for authorization identification
     * Used only when WPF returns to the cancel URL
     *
     * @param string $value
     */
    public function setAuthorizationToken($value)
    {
        $this->authorization_token = $value;
    }

    /**
     * The returned Genesis Status of the transaction
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $value
     */
    public function setStatus($value)
    {
        $this->status = $value;
    }

    /**
     * The Order Id that transaction belongs to
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param string $value
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
    }

    /**
     * The identifier of the Shop used for the transaction
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * The identifier of the Shop used for the transaction
     *
     * @param int $value
     */
    public function setShopId($value)
    {
        $this->shop_id = $value;
    }

    /**
     * The Transaction Type
     *
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transaction_type;
    }

    /**
     * @param string $value
     */
    public function setTransactionType($value)
    {
        $this->transaction_type = $value;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $value
     */
    public function setAmount($value)
    {
        $this->amount = (int) $value;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $value
     */
    public function setCurrency($value)
    {
        $this->currency = $value;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($value)
    {
        $this->mode = $value;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $value
     */
    public function setMessage($value)
    {
        $this->message = $value;
    }

    /**
     * @return string
     */
    public function getTechnicalMessage()
    {
        return $this->technical_message;
    }

    /**
     * @param string $value
     */
    public function setTechnicalMessage($value)
    {
        $this->technical_message = $value;
    }

    /**
     * The Raw Transaction Request
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $value
     */
    public function setRequest($value)
    {
        $this->request = $value;
    }

    /**
     * The Raw Response from Genesis
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $value
     */
    public function setResponse($value)
    {
        $this->response = $value;
    }

    /**
     * The Timestamp of record creation
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->date_create;
    }

    /**
     * @param \DateTime $value
     */
    public function setCreatedAt($value)
    {
        $this->date_create = $value;
    }

    /**
     * The Timestamp of the record update
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->date_update;
    }

    /**
     * @param \DateTime $value
     */
    public function setUpdatedAt($value)
    {
        $this->date_update = $value;
    }
}
