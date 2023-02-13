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
use EMerchantPay\Components\Services\EmerchantpayConfig;
use EMerchantPay\Components\Services\WpfTokenizationService;
use EMerchantPay\Components\Services\ThreedsService;
use EMerchantPay\Models\Transaction\Repository;
use EMerchantPay\Models\Transaction\Transaction;
use Genesis\API\Constants\Payment\Methods as PproMethods;
use Genesis\API\Constants\Transaction\Types;
use Genesis\API\Notification;
use Genesis\API\Request\WPF\Create;
use Genesis\Genesis;
use Genesis\Utils\Currency;
use Genesis\Utils\Common as CommonUtils;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\DeliveryTimeframes;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\RegistrationIndicators;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\Purchase\Categories as ThreedsV2Categories;

/**
 * The Checkout Service delivers Checkout Method functionality
 *
 * Class CheckoutService
 * @package EMerchantPay\Components\Methods
 */
class CheckoutService extends SdkService
{
    /**
     * @var WpfTokenizationService
     */
    private $wpfTokenizationService;

    /**
     * @var ThreedsService
     */
    private $threedsService;

    public function __construct(
        $configService,
        $pluginName,
        $pluginLogger,
        $modelsManager,
        WpfTokenizationService $wpfTokenizationService,
        ThreedsService $threedsService
    ) {
        $this->wpfTokenizationService = $wpfTokenizationService;
        $this->threedsService         = $threedsService;
        parent::__construct($configService, $pluginName, $pluginLogger, $modelsManager);
    }

    /**
     * The Method
     *
     * @return string
     */
    public function getMethod()
    {
        return parent::METHOD_CHECKOUT;
    }

    /**
     * The actual Request attributes
     *
     * @param PaymentData $paymentData
     *
     * @return Genesis
     * @throws \Exception
     */
    public function setGenesisRequestProperties(PaymentData $paymentData)
    {
        $wpfTokenizationService = $this->wpfTokenizationService;

        $transactionId = $this->generateTransactionId(self::PLATFORM_TRANSACTION_PREFIX);

        $request = $this->genesis->request();
        $request
            ->setTransactionId($transactionId)
            ->setUsage('Payment via ' . $this->getShopName())
            ->setAmount($paymentData->getAmount())
            ->setCurrency($paymentData->getCurrency())
            ->setNotificationUrl($paymentData->getNotificationUrl())
            ->setReturnSuccessUrl(
                $paymentData->getSuccessUrl() . '&' . http_build_query([
                    EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID => $transactionId
                ])
            )
            ->setReturnPendingUrl(
                $paymentData->getSuccessUrl() . '&' . http_build_query([
                    EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID => $transactionId
                ])
            )
            ->setReturnFailureUrl(
                $paymentData->getFailureUrl() . '&' . http_build_query([
                    EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID => $transactionId
                ])
            )
            ->setReturnCancelUrl(
                $paymentData->getCancelUrl() . '&' . http_build_query([
                    EmerchantpayPaymentAttributes::RETURN_ACTION_PARAM_TRANSACTION_ID => $transactionId
                ])
            )
            ->setDescription($paymentData->buildOrderDescriptionText())

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
            ->setShippingCountry($paymentData->getShippingCountry())
            ->setLanguage($this->getConfig()[SdkSettingKeys::CHECKOUT_LANGUAGE]);

        if ($this->getConfig()[SdkSettingKeys::WPF_TOKENIZATION] === 'yes') {
            $request->setRememberCard('true');
            $request->setConsumerId(
                $wpfTokenizationService->getConsumerId(
                    $this->getShopwareUserId(),
                    $paymentData->getEmail(),
                    $this->getMethod()
                )
            );
        }

        if ($this->getConfig()[SdkSettingKeys::THREEDS_OPTION] === self::THREEDS_OPTION_ENABLED) {
            $this->prepareThreedsV2Params($paymentData);
        }

        $this->addScaExemptionParameters();

        $this->prepareTransactionTypes();

        return $this->genesis;
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

        // Load correct payment from the Reconcile Object
        $payment = $reconcileObject;
        if (isset($reconcileObject->payment_transaction)) {
            $payment = $this->populatePaymentTransaction($reconcileObject);
        }

        $this->pluginLogger->debug(
            'Checkout Payment Object',
            $this->getMethod(),
            (array) $payment
        );

        if ($transaction->getUniqueId() === $payment->unique_id) {
            // update the transaction
            // No reference transaction. This is the transaction approval
            $this->updateTransaction($transaction, $payment, $notificationObject);
        }

        if ($transaction->getUniqueId() !== $payment->unique_id) {
            // Record the Payment
            $this->addTransaction($transaction, $payment, $notificationObject);
        }

        // Update the Order State
        $orderRepository = Shopware()->Modules()->Order();
        $this->saveOrderState(
            $orderRepository,
            $order,
            $payment->status,
            $payment->transaction_type
        );
    }

    /**
     * Append WPF Transaction Types
     */
    protected function prepareTransactionTypes()
    {
        $types = $this->getCheckoutTransactionTypes();

        /** @var Create $request */
        $request = $this->genesis->request();

        foreach ($types as $transactionType) {
            if (is_array($transactionType)) {
                $request->addTransactionType(
                    $transactionType['name'],
                    $transactionType['parameters']
                );

                continue;
            }

            switch ($transactionType) {
                case Types::IDEBIT_PAYIN:
                case Types::INSTA_DEBIT_PAYIN:
                    $parameters = [
                        'customer_account_id' => $this->getShopwareCustomerNumber()
                    ];
                    break;
                case Types::TRUSTLY_SALE:
                    $parameters = [
                        'user_id' => $this->getShopwareUserId()
                    ];
                    break;
                case Types::ONLINE_BANKING_PAYIN:
                    //Remove empty placeholder if no bank code is selected
                    $selectedBankCodes = array_filter($this->getConfig()[SdkSettingKeys::BANK_CODES]);
                    if (CommonUtils::isValidArray($selectedBankCodes)) {
                        $parameters['bank_codes'] = array_map(
                            function ($value) {
                                return ['bank_code' => $value];
                            },
                            $selectedBankCodes
                        );
                    }
                    break;
            }

            if (!isset($parameters)) {
                $parameters = [];
            }

            $request->addTransactionType(
                $transactionType,
                $parameters
            );

            unset($parameters);
        }
    }

    /**
     * @param Transaction $transaction
     * @param \stdClass $payment
     * @param Notification $notificationObject
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateTransaction($transaction, $payment, $notificationObject)
    {
        $transaction->setStatus($payment->status);
        $transaction->setUpdatedAt(new \DateTime());

        if (isset($reconcileObject->message)) {
            $transaction->setMessage($transaction->getMessage() . PHP_EOL . $payment->message);
        }
        if (isset($reconcileObject->technical_message)) {
            $transaction->setTechnicalMessage(
                $transaction->getTechnicalMessage() . PHP_EOL . $payment->technical_message
            );
        }

        $request   = unserialize($transaction->getRequest());
        $request[] = (array)$payment;
        $transaction->setRequest(serialize($request));

        $response   = unserialize($transaction->getResponse());
        $response[] = $notificationObject->generateResponse();
        $transaction->setResponse(serialize($response));

        $this->modelsManager->persist($transaction);
        $this->modelsManager->flush();
    }

    /**
     * @param Transaction $transaction
     * @param \stdClass $payment
     * @param Notification $notificationObject
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function addTransaction($transaction, $payment, $notificationObject)
    {
        $paymentTransaction = new Transaction();
        $paymentTransaction->setTransactionId($transaction->getTransactionId());
        $paymentTransaction->setUniqueId($payment->unique_id);
        $paymentTransaction->setReferenceId($transaction->getUniqueId());
        $paymentTransaction->setOrderId($transaction->getOrderId());
        $paymentTransaction->setStatus($payment->status);
        $paymentTransaction->setTransactionType($payment->transaction_type);
        $paymentTransaction->setPaymentToken($transaction->getPaymentToken());
        $paymentTransaction->setPaymentMethod($this->getMethod());
        $paymentTransaction->setAmount(Currency::amountToExponent($payment->amount, $payment->currency));
        $paymentTransaction->setCurrency($payment->currency);
        $paymentTransaction->setMessage(isset($payment->message) ? $payment->message : '');
        $paymentTransaction->setTechnicalMessage(
            isset($payment->technical_message) ? $payment->technical_message : ''
        );
        $paymentTransaction->setMode($transaction->getMode());
        $paymentTransaction->setTerminalToken($payment->terminal_token);
        $paymentTransaction->setRequest(serialize([0 => $notificationObject->getReconciliationObject()]));
        $paymentTransaction->setResponse(serialize([0 => $notificationObject->generateResponse()]));
        $paymentTransaction->setCreatedAt(new \DateTime());
        $paymentTransaction->setUpdatedAt(new \DateTime());

        $this->modelsManager->persist($paymentTransaction);
        $this->modelsManager->flush();
    }

    /**
     * @param \stdClass $reconcileObject The Genesis Reconcile Object
     * @return \stdClass
     */
    protected function populatePaymentTransaction($reconcileObject)
    {
        if (isset($reconcileObject->payment_transaction->unique_id)) {
            return $reconcileObject->payment_transaction;
        }

        if (count($reconcileObject->payment_transaction) > 1) {
            $paymentTransactions = $reconcileObject->payment_transaction;
            $lastTransaction     = $this->getLastTransaction(
                $reconcileObject->transaction_id
            );

            if (!isset($lastTransaction)) {
                return $paymentTransactions[0];
            }

            foreach ($paymentTransactions as $paymentTransaction) {
                if ($paymentTransaction->unique_id == $lastTransaction->getReferenceId()) {
                    return $paymentTransaction;
                }
            }

            return $paymentTransactions[0];
        }
    }

    /**
     * @param string $transaction_id
     * @return array|object[]
     */
    protected function getLastTransaction($transaction_id)
    {
        return $this->modelsManager->getRepository(Transaction::class)->findOneBy(
            ['transaction_id' => $transaction_id],
            ['id' => 'DESC'],
            1
        );
    }

    /**
     * Process the Checkout Config and provides the transaction type names with their params
     *
     * @return array
     */
    protected function getCheckoutTransactionTypes()
    {
        $processedList = [];
        $aliasMap      = [];

        $selectedTypes = $this->getConfig()[SdkSettingKeys::TRANSACTION_TYPES];
        $pproSuffix    = EmerchantpayConfig::PPRO_TRANSACTION_SUFFIX;
        $methods        = PproMethods::getMethods();

        foreach ($methods as $method) {
            $aliasMap[$method . $pproSuffix] = Types::PPRO;
        }

        $aliasMap = array_merge($aliasMap, [
            EmerchantpayConfig::GOOGLE_PAY_TRANSACTION_PREFIX . EmerchantpayConfig::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE =>
                Types::GOOGLE_PAY,
            EmerchantpayConfig::GOOGLE_PAY_TRANSACTION_PREFIX . EmerchantpayConfig::GOOGLE_PAY_PAYMENT_TYPE_SALE      =>
                Types::GOOGLE_PAY,
            EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX . EmerchantpayConfig::PAYPAL_PAYMENT_TYPE_AUTHORIZE         =>
                Types::PAY_PAL,
            EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX . EmerchantpayConfig::PAYPAL_PAYMENT_TYPE_SALE              =>
                Types::PAY_PAL,
            EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX . EmerchantpayConfig::PAYPAL_PAYMENT_TYPE_EXPRESS           =>
                Types::PAY_PAL,
            EmerchantpayConfig::APPLE_PAY_TRANSACTION_PREFIX . EmerchantpayConfig::APPLE_PAY_TYPE_AUTHORIZE           =>
                Types::APPLE_PAY,
            EmerchantpayConfig::APPLE_PAY_TRANSACTION_PREFIX . EmerchantpayConfig::APPLE_PAY_TYPE_SALE                =>
                Types::APPLE_PAY,
        ]);

        foreach ($selectedTypes as $selectedType) {
            if (!array_key_exists($selectedType, $aliasMap)) {
                $processedList[] = $selectedType;

                continue;
            }

            $transactionType = $aliasMap[$selectedType];

            $processedList[$transactionType]['name'] = $transactionType;

            $key = $this->getCustomParameterKey($transactionType);

            $processedList[$transactionType]['parameters'][] = [
                $key => str_replace(
                    [
                        $pproSuffix,
                        EmerchantpayConfig::GOOGLE_PAY_TRANSACTION_PREFIX,
                        EmerchantpayConfig::PAYPAL_TRANSACTION_PREFIX,
                        EmerchantpayConfig::APPLE_PAY_TRANSACTION_PREFIX,
                    ],
                    '',
                    $selectedType
                )
            ];
        }

        return $processedList;
    }

    /**
     * @param $transactionType
     * @return string
     */
    private function getCustomParameterKey($transactionType)
    {
        switch ($transactionType) {
            case Types::PPRO:
                $result = 'payment_method';
                break;
            case Types::PAY_PAL:
                $result = 'payment_type';
                break;
            case Types::GOOGLE_PAY:
            case Types::APPLE_PAY:
                $result = 'payment_subtype';
                break;
            default:
                $result = 'unknown';
        }

        return $result;
    }

    /**
     * Prepare Threeds V2 request
     *
     * @param $paymentData
     *
     * @throws \Exception
     */
    private function prepareThreedsV2Params($paymentData)
    {
        $this->threedsService->initData($paymentData);

        /** @var Create $request */
        $request = $this->genesis->request();

        $request
            // Challenge Indicator
            ->setThreedsV2ControlChallengeIndicator(
                $this->getConfig()[SdkSettingKeys::CHALLENGE_INDICATOR]
            )
            ->setThreedsV2PurchaseCategory(
                $this->threedsService->hasPhysicalProduct()
                ? ThreedsV2Categories::GOODS
                : ThreedsV2Categories::SERVICE
            )
            ->setThreedsV2MerchantRiskShippingIndicator(
                $this->threedsService->fetchShippingIndicator()
            )
            ->setThreedsV2MerchantRiskDeliveryTimeframe(
                $this->threedsService->hasPhysicalProduct()
                ? DeliveryTimeframes::ANOTHER_DAY
                : DeliveryTimeframes::ELECTRONICS
            )
            ->setThreedsV2MerchantRiskReorderItemsIndicator(
                $this->threedsService->fetchReorderItemsIndicator()
            )
            ->setThreedsV2CardHolderAccountCreationDate(
                $this->threedsService->customerDateCreated()
            )
            ->setThreedsV2CardHolderAccountUpdateIndicator(
                $this->threedsService->fetchUpdateIndicator()
            )
            ->setThreedsV2CardHolderAccountLastChangeDate(
                $this->threedsService->getLastChangedDate()
            )
            ->setThreedsV2CardHolderAccountPasswordChangeIndicator(
                $this->threedsService->fetchPasswordChangeIndicator()
            )
            ->setThreedsV2CardHolderAccountPasswordChangeDate(
                $this->threedsService->getPasswordChangedDate()
            )
            ->setThreedsV2CardHolderAccountShippingAddressUsageIndicator(
                $this->threedsService->fetchShippingAddressUsageIndicator()
            )
            ->setThreedsV2CardHolderAccountShippingAddressDateFirstUsed(
                $this->threedsService->getFirstUseOfShippingAddress()
            )
            ->setThreedsV2CardHolderAccountTransactionsActivityLast24Hours(
                $this->threedsService->countOrdersPeriod(
                    ThreedsService::ACTIVITY_24_HOURS
                )
            )
            ->setThreedsV2CardHolderAccountTransactionsActivityPreviousYear(
                $this->threedsService->countOrdersPeriod(
                    ThreedsService::ACTIVITY_1_YEAR
                )
            )
            ->setThreedsV2CardHolderAccountPurchasesCountLast6Months(
                $this->threedsService->countOrdersPeriod(
                    ThreedsService::ACTIVITY_6_MONTHS
                )
            )
            ->setThreedsV2CardHolderAccountRegistrationDate(
                $this->threedsService->getProfileFirstOrderDate()
            )
            ->setThreedsV2CardHolderAccountRegistrationIndicator(
                $this->threedsService->fetchRegistrationIndicator()
            );
    }

    /**
     * Add SCA Exemption parameter to Genesis Request
     *
     * @return Genesis
     */
    private function addScaExemptionParameters()
    {
        $request = $this->genesis->request();
        $wpfAmount = (float)$request->getAmount();
        $scaExemption = $this->getConfig()[SdkSettingKeys::SCA_EXEMPTION_OPTION];
        $scaExemptionValue = (float)$this->getConfig()[SdkSettingKeys::SCA_EXEMPTION_AMOUNT];

        if ($wpfAmount <= $scaExemptionValue) {
            $request->setScaExemption($scaExemption);
        }

        return $this->genesis;
    }

}
