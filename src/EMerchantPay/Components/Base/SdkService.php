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

namespace EMerchantPay\Components\Base;

use EMerchantPay\Components\Constants\EmerchantpayPaymentAttributes;
use EMerchantPay\Components\Constants\SdkSettingKeys;
use EMerchantPay\Components\Services\EmerchantpayConfig;
use EMerchantPay\Components\Services\EmerchantpayLogger;
use EMerchantPay\Components\Models\PaymentData;
use EMerchantPay\Models\Transaction\Transaction;
use Genesis\API\Constants\Endpoints;
use Genesis\API\Constants\Environments;
use Genesis\API\Constants\Transaction\States;
use Genesis\API\Constants\Transaction\Types;
use Genesis\Config;
use Genesis\Genesis;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\DetachedShop;

/**
 * Base Service implementing the Genesis PHP SDK Requests
 *
 * Class SdkService
 * @package EMerchantPay\Components\Base
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
abstract class SdkService
{
    /**
     * Recognizable string that emerchantpay Uses for the transaction ids
     */
    const PLATFORM_TRANSACTION_PREFIX = 'sw5';

    /**
     * The Maximum length that limits the transaction id string
     */
    const MAX_TRANSACTION_ID_LENGTH = 30;

    /**
     * Available Methods
     */
    const METHOD_CHECKOUT = 'checkout';
    const METHOD_DIRECT   = 'direct';

    /**
     * Emerchantpay Plugin Config Service
     *
     * @var EmerchantpayConfig $configService
     */
    protected $configService;

    /**
     * The name of the plugin
     *
     * @var string $pluginName
     */
    protected $pluginName;

    /**
     * The Genesis SDK Object
     *
     * @var Genesis $genesis
     */
    protected $genesis;

    /**
     * The Plugin Logger Service
     *
     * @var EmerchantpayLogger $pluginLogger
     */
    protected $pluginLogger;

    /**
     * The Shopware Doctrine Models Manager
     *
     * @var ModelManager $modelsManager
     */
    protected $modelsManager;

    /** @var DetachedShop $shopwareShop */
    protected $shopwareShop;

    /**
     * The logged Shopware User Id
     *
     * @var integer|null
     */
    protected $shopwareUserId;

    /**
     * The logged Shopware customer number
     *
     * @var integer|null
     */
    protected $shopwareCustomerNumber;

    /**
     * Get the Method Instance (Checkout/Direct)
     *      Possible values emerchantpay_checkout, emerchantpay_direct
     *
     * @return Genesis
     */
    abstract public function getMethod();

    /**
     * Set the Genesis Request Attributes
     *
     * @var PaymentData $paymentData
     *
     * @return Genesis
     */
    abstract public function setGenesisRequestProperties(PaymentData $paymentData);

    /**
     * GenesisSdkService constructor
     *
     * @param EmerchantpayConfig $configService       Emerchantpay Config Service
     * @param string             $pluginName          The name of the Plugin
     * @param EmerchantpayLogger $pluginLogger        The plugin Logger Service
     * @param ModelManager       $modelsManager       The models manager Shopware Service
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     */
    public function __construct($configService, $pluginName, $pluginLogger, $modelsManager)
    {
        $this->configService = $configService;
        $this->pluginName    = $pluginName;
        $this->genesis       = $this->initializeGenesisSdk();
        $this->pluginLogger  = $pluginLogger;
        $this->modelsManager = $modelsManager;
    }

    /**
     * Loads the Shopware Store Object
     * @param DetachedShop $shop
     */
    public function loadShopwareShop($shop)
    {
        $this->shopwareShop = $shop;
    }

    /**
     * Returns the Shopware Store Name
     *
     * @return string|null
     */
    public function getShopName()
    {
        if (is_object($this->shopwareShop)) {
            return $this->shopwareShop->getName();
        }

        return null;
    }

    /**
     * Returns the Shopware Store Id
     *
     * @return int|null
     */
    public function getShopId()
    {
        if (is_object($this->shopwareShop)) {
            return $this->shopwareShop->getId();
        }

        return null;
    }

    /**
     * Get the Genesis SDK Instance
     *
     * @return Genesis
     */
    public function getGenesis()
    {
        return $this->genesis;
    }

    /**
     * Get the Plugin Name
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Get the config properties for specific method
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->configService->getConfigByMethod($this->getMethod());
    }

    /**
     * The logged Shopware User Id
     *
     * @return integer|null
     */
    public function getShopwareUserId()
    {
        return $this->shopwareUserId;
    }

    /**
     * The logged Shopware User Id
     *
     * @param integer|null $value
     */
    public function setShopwareUserId($value)
    {
        $this->shopwareUserId = $value;
    }

    /**
     * The logged Shopware customer number
     *
     * @return integer|null
     */
    public function getShopwareCustomerNumber()
    {
        return $this->shopwareCustomerNumber;
    }

    /**
     * The logged Shopware customer number
     *
     * @param integer|null $value
     */
    public function setShopwareCustomerNumber($value)
    {
        $this->shopwareCustomerNumber = $value;
    }

    /**
     * Initialize the Genesis SDK Object
     *
     * @return Genesis
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     */
    public function initializeGenesisSdk()
    {
        $this->initializeGenesisConfig();

        if ($this->getMethod() == self::METHOD_CHECKOUT) {
            $this->genesis = new Genesis('WPF\Create');
        }

        if ($this->getMethod() == self::METHOD_DIRECT) {
            $this->genesis = new Genesis(
                Types::getFinancialRequestClassForTrxType($this->getConfig()[SdkSettingKeys::TRANSACTION_TYPES][0])
            );
        }

        return $this->genesis;
    }

    /**
     * Initialize the Genesis SDK Object for Reference Transactions
     *
     * @param string $token
     * @param string $transactionType
     * @return Genesis
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     */
    public function initializeReferenceConfig($token)
    {
        $config = $this->getConfig();

        Config::setEndpoint(Endpoints::EMERCHANTPAY);
        Config::setEnvironment(
            $config[SdkSettingKeys::MODE] ? Environments::STAGING : Environments::PRODUCTION
        );
        Config::setUsername($config[SdkSettingKeys::USERNAME]);
        Config::setPassword($config[SdkSettingKeys::PASSWORD]);
        Config::setToken($token);
    }

    /**
     * @param $data
     * @return \Genesis\API\Notification|Genesis
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    public function initializeGenesisReconcile($data)
    {
        $this->initializeGenesisConfig();

        $this->genesis = new \Genesis\API\Notification($data);

        return $this->genesis;
    }

    /**
     * @param string $token
     * @param string $transactionType
     * @return Genesis
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     */
    public function initializeGenesisReference($token, $transactionType)
    {
        $this->initializeReferenceConfig($token);

        $this->genesis = new Genesis($transactionType);

        return $this->genesis;
    }

    /**
     * Generate Unique Payment Token used for identification of the transaction
     *
     * @param \stdClass $genesisResponse
     * @return string
     */
    public function generatePaymentToken($genesisResponse)
    {
        return md5($genesisResponse->amount.$genesisResponse->currency.$genesisResponse->unique_id);
    }

    /**
     * Generate transaction id, unique to this instance
     *
     * @param string $prefix
     *
     * @return string
     */
    protected function generateTransactionId($prefix = '')
    {
        $unique = sprintf(
            '|%s|%s|%s|%s|',
            rand(PHP_INT_MIN, PHP_INT_MAX),
            microtime(true),
            @$_SERVER['HTTP_USER_AGENT'],
            md5(uniqid(mt_rand(), true))
        );

        $prefix = empty($prefix) ?: "{$prefix}-";
        $length = self::MAX_TRANSACTION_ID_LENGTH - mb_strlen($prefix);

        return $prefix . strtolower(substr(sha1($unique), 0, $length));
    }

    /**
     * Initialize the SDK Config
     *
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    private function initializeGenesisConfig()
    {
        $config = $this->getConfig();

        Config::setEndpoint(Endpoints::EMERCHANTPAY);
        Config::setEnvironment(
            $config[SdkSettingKeys::MODE] ? Environments::STAGING : Environments::PRODUCTION
        );
        Config::setUsername($config[SdkSettingKeys::USERNAME]);
        Config::setPassword($config[SdkSettingKeys::PASSWORD]);

        if ($this->getMethod() == self::METHOD_DIRECT) {
            Config::setToken($config[SdkSettingKeys::TOKEN]);
        }
    }

    /**
     * Get the Transaction Entity
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    protected function getTransactionRepository()
    {
        return $this->modelsManager->getRepository(Transaction::class);
    }

    /**
     * Loads the Order by the Order Id
     *
     * @param $orderId
     * @return Order
     */
    protected function loadOrder($orderId)
    {
        return $this->modelsManager
            ->getRepository(Order::class)
            ->findOneBy(['number' => $orderId]);
    }

    /**
     * @param \sOrder $orderRepository
     * @param Order $orderModel
     * @param $status
     * @param string $transactionType
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveOrderState($orderRepository, $orderModel, $status, $transactionType)
    {
        switch ($status) {
            case States::APPROVED:
                $paymentStatus = Types::isAuthorize($transactionType) ?
                    Status::PAYMENT_STATE_COMPLETELY_PAID : Status::PAYMENT_STATE_COMPLETELY_INVOICED;

                $orderRepository->setPaymentStatus(
                    $orderModel->getId(),
                    $paymentStatus,
                    false,
                    'Payment was ' . $status
                );
                break;
            case States::PENDING_ASYNC:
            case States::PENDING:
                $orderRepository->setPaymentStatus(
                    $orderModel->getId(),
                    Status::PAYMENT_STATE_OPEN,
                    false,
                    'Payment is ' . $status
                );
                break;
            case States::ERROR:
            case States::DECLINED:
            case States::TIMEOUT:
            case States::VOIDED:
                $orderRepository->setPaymentStatus(
                    $orderModel->getId(),
                    Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED,
                    false,
                    'Payment was ' . $status
                );
                $orderRepository->setOrderStatus(
                    $orderModel->getId(),
                    Status::ORDER_STATE_CANCELLED_REJECTED,
                    false,
                    'Order was ' . $status
                );
                break;
        }
    }

    /**
     * Execcute Reference Transaction Capture
     *
     * @param Transaction $transaction
     * @return Genesis
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\ErrorAPI
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     */
    public function doCapture($transaction)
    {
        $this->initializeGenesisReference(
            $transaction->getTerminalToken(),
            Types::getCaptureTransactionClass($transaction->getTransactionType())
        );
        $this->genesis
            ->request()
            ->setTransactionId($this->generateTransactionId(self::PLATFORM_TRANSACTION_PREFIX))
            ->setUsage('e-Commerce Platform') // TODO use Store Name
            ->setRemoteIp($this->getRemoteIp())
            ->setReferenceId($transaction->getUniqueId())
            ->setAmount(\Genesis\Utils\Currency::exponentToAmount(
                $transaction->getAmount(),
                $transaction->getCurrency()
            ))
            ->setCurrency($transaction->getCurrency());

        $this->genesis->execute();

        return $this->genesis;
    }

    /**
     * Execcute Reference Transaction Capture
     *
     * @param Transaction $transaction
     * @return Genesis
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\ErrorAPI
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     */
    public function doRefund($transaction)
    {
        $this->initializeGenesisReference(
            $transaction->getTerminalToken(),
            Types::getRefundTransactionClass($transaction->getTransactionType())
        );
        $this->genesis
            ->request()
            ->setTransactionId($this->generateTransactionId(self::PLATFORM_TRANSACTION_PREFIX))
            ->setUsage('e-Commerce Platform') // TODO use Store Name
            ->setRemoteIp($this->getRemoteIp())
            ->setReferenceId($transaction->getUniqueId())
            ->setAmount(\Genesis\Utils\Currency::exponentToAmount(
                $transaction->getAmount(),
                $transaction->getCurrency()
            ))
            ->setCurrency($transaction->getCurrency());

        $this->genesis->execute();

        return $this->genesis;
    }

    /**
     * Execcute Reference Transaction Capture
     *
     * @param Transaction $transaction
     * @return Genesis
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\ErrorAPI
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     */
    public function doVoid($transaction)
    {
        $this->initializeGenesisReference(
            $transaction->getTerminalToken(),
            Types::getFinancialRequestClassForTrxType(Types::VOID)
        );
        $this->genesis
            ->request()
            ->setTransactionId($this->generateTransactionId(self::PLATFORM_TRANSACTION_PREFIX))
            ->setUsage('e-Commerce Platform') // TODO use Store Name
            ->setRemoteIp($this->getRemoteIp())
            ->setReferenceId($transaction->getUniqueId());

        $this->genesis->execute();

        return $this->genesis;
    }

    /**
     * Process the doCapture response and record in the Store
     * @param Transaction $transaction
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processReferenceResponse($transaction)
    {
        $referenceTransaction = new Transaction();
        $referenceTransaction->setTransactionId($this->genesis->request()->getTransactionId());
        $referenceTransaction->setReferenceId($this->genesis->request()->getReferenceId());
        $referenceTransaction->setTransactionType($this->genesis->response()->getResponseObject()->transaction_type);
        $referenceTransaction->setUniqueId($this->genesis->response()->getResponseObject()->unique_id);
        $referenceTransaction->setAmount(
            \Genesis\Utils\Currency::amountToExponent(
                $this->genesis->response()->getResponseObject()->amount,
                $this->genesis->response()->getResponseObject()->currency
            )
        );
        $referenceTransaction->setCurrency($this->genesis->response()->getResponseObject()->currency);
        $referenceTransaction->setPaymentMethod($this->getMethod());
        $referenceTransaction->setPaymentToken(
            $this->generatePaymentToken($this->genesis->response()->getResponseObject())
        );
        $referenceTransaction->setOrderId($transaction->getOrderId());

        $mode = $this->getConfig()[SdkSettingKeys::MODE] ?
            EmerchantpayPaymentAttributes::PAYMENT_MODE_TEST : EmerchantpayPaymentAttributes::PAYMENT_MODE_LIVE;
        $referenceTransaction->setMode($mode);

        $referenceTransaction->setTerminalToken($transaction->getTerminalToken());
        $referenceTransaction->setStatus($this->genesis->response()->getResponseObject()->status);
        $referenceTransaction->setMessage(
            isset($this->genesis->response()->getResponseObject()->message) ?
                $this->genesis->response()->getResponseObject()->message : ''
        );
        $referenceTransaction->setTechnicalMessage(
            isset($this->genesis->response()->getResponseObject()->technical_message) ?
                $this->genesis->response()->getResponseObject()->technical_message : ''
        );
        $referenceTransaction->setRequest(serialize([0=>(array) $transaction]));
        $referenceTransaction->setResponse(serialize([0=>(array) $this->genesis->response()->getResponseObject()]));
        $referenceTransaction->setCreatedAt(new \DateTime());
        $referenceTransaction->setUpdatedAt(new \DateTime());

        $this->modelsManager->persist($referenceTransaction);
        $this->modelsManager->flush();

        // Update the Order State
        if ($referenceTransaction->getStatus() !== States::PENDING_ASYNC) {
            $order = $this->loadOrder($transaction->getOrderId());
            $orderRepository = Shopware()->Modules()->Order();
            $this->saveOrderState(
                $orderRepository,
                $order,
                $referenceTransaction->getStatus(),
                $referenceTransaction->getTransactionType()
            );
        }
    }

    /**
     * Retrieve the Remote Ip
     *
     * @return mixed
     */
    public function getRemoteIp()
    {
        $remoteIp = @$_SERVER['REMOTE_ADDR'];

        if (filter_var($remoteIp, FILTER_VALIDATE_IP)) {
            return $remoteIp;
        }

        return null;
    }

    /**
     * Retrieve Recurring transaction Types
     *
     * @return array
     */
    public static function getRecurringTransactionTypes()
    {
        return [
            Types::SDD_INIT_RECURRING_SALE,
            Types::INIT_RECURRING_SALE,
            Types::INIT_RECURRING_SALE_3D
        ];
    }
}
