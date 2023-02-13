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

namespace EMerchantPay\Components\Services;

use EMerchantPay\Components\Models\PaymentData;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ShippingIndicators;
use Shopware\Bundle\CustomerSearchBundleDBAL\Indexing\CustomerProvider;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Status;
use Shopware\Models\Order\Order;

/**
 * Class ThreedsService
 *
 * Helper service for fetching the 3DSv2 optional parameters
 *
 * @package EMerchantPay\Components\Services
 */
class ThreedsService
{

    const ACTIVITY_24_HOURS = 'PT24H';
    const ACTIVITY_6_MONTHS = 'P6M';
    const ACTIVITY_1_YEAR   = 'P1Y';

    /**
     * 3DSv2 date format
     */
    const THREEDS_DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var $products
     */
    private $products;

    /**
     * @var array $billingAddress
     */
    private $billingAddress = [];

    /**
     * @var array $shippingAddress
     */
    private $shippingAddress = [];

    /**
     * User from Order
     *
     * @var $user
     */
    private $user;

    /**
     * @var ThreedsIndicatorService
     */
    private $threedsIndicatorService;

    /**
     *
     * @var CustomerProvider
     */
    private $customerProvider;

    /**
     * Customer from database
     *
     * @var $customer
     */
    private $customer;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var $paymentData
     */
    private $paymentData;

    /**
     * ThreedsService constructor.
     *
     * @param $customerProvider
     * @param $threedsIndicatorService
     * @param ModelManager $modelManager
     */
    public function __construct(
        $customerProvider,
        $threedsIndicatorService,
        ModelManager $modelManager
    ) {
        $this->customerProvider        = $customerProvider;
        $this->threedsIndicatorService = $threedsIndicatorService;
        $this->modelManager            = $modelManager;
    }

    /**
     * Injects current Order parameters and set initial values
     *
     * @param PaymentData $paymentData
     */
    public function initData(PaymentData $paymentData)
    {
        $this->setUser($paymentData->getUser());

        $this->paymentData             = $paymentData;
        $this->products                = $paymentData->getOrderItems();

        $this->billingAddress          = [
            $paymentData->getBillingFirstName(),
            $paymentData->getBillingLastName(),
            $paymentData->getBillingAddress(),
            $paymentData->getBillingZipcode(),
            $paymentData->getBillingCity(),
            $paymentData->getBillingCountry()
        ];

        $this->shippingAddress         = [
            $paymentData->getShippingFirstName(),
            $paymentData->getShippingLastName(),
            $paymentData->getShippingAddress(),
            $paymentData->getShippingZipcode(),
            $paymentData->getShippingCity(),
            $paymentData->getShippingCountry()
        ];

        $this->customer                = $this->customerProvider->get(
            [$this->getUserId()]
        )[$this->getUserId()];
    }

    /**
     * Sets user parameter
     *
     * @param $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Return current user parameter
     *
     * @return mixed
     */
    public function getUserId()
    {
        return array_key_exists('additional', $this->user)
            ?  $this->user['additional']['user']['userID']
            : null;
    }

    /**
     * @return bool
     */
    public function hasPhysicalProduct()
    {
        return !$this->areAllProductsDigital() ? true : false;
    }

    /**
     * Check for physical product
     *
     * @return bool
     */
    public function areAllProductsDigital()
    {
        foreach ($this->products as $product) {
            if (array_key_exists('esd', $product) && (bool)$product['esd'] === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fetch the Shipping Indicator from the Order Data
     *
     * @return string
     */
    public function fetchShippingIndicator()
    {

        if ($this->areAllProductsDigital()) {
            return ShippingIndicators::DIGITAL_GOODS;
        }

        if ($this->hasSameAddresses()) {
            return ShippingIndicators::SAME_AS_BILLING;
        }

        if ($this->hasBillingAddress() &&
             $this->hasShippingAddress()
        ) {
             return ShippingIndicators::STORED_ADDRESS;
        }

        return ShippingIndicators::OTHER;
    }

    /**
     * Checks for same billing and shipping addresses
     *
     * @return bool
     */
    public function hasSameAddresses()
    {
        return count(array_diff($this->billingAddress, $this->shippingAddress)) === 0;
    }

    /**
     * Check that billing address is empty
     *
     * @return bool
     */
    public function hasBillingAddress()
    {
        return !empty($this->billingAddress);
    }

    /**
     * Check that shipping address is empty
     *
     * @return bool
     */
    public function hasShippingAddress()
    {
        return !empty($this->shippingAddress);
    }

    /**
     * Returns current customer date created
     *
     * @return mixed
     */
    public function customerDateCreated()
    {
        return array_key_exists('additional', $this->user)
            ? $this->user['additional']['user']['firstlogin']
            : null;
    }

    /**
     * Returns current user changed date
     *
     * @return mixed
     */
    public function getLastChangedDate()
    {
        return array_key_exists('additional', $this->user)
            ? $this->user['additional']['user']['changed']
            : null;
    }

    /**
     * Returns date when password of current user is changed
     *
     * @return mixed
     */
    public function getPasswordChangedDate()
    {
        return array_key_exists('additional', $this->user)
            ? $this->user['additional']['user']['password_change_date']
            : null;
    }

    /**
     * Fetch 3DSv2 Account Holder Update Indicator
     *
     * @return mixed|string
     * @throws \Exception
     */
    public function fetchUpdateIndicator()
    {
        return $this->threedsIndicatorService->fetchUpdateIndicator(
            $this->getLastChangedDate()
        );
    }

    /**
     * Fetch 3DSv2 Account Holder Password Change Indicator
     *
     * @return mixed
     * @throws \Exception
     */
    public function fetchPasswordChangeIndicator()
    {
        return $this->threedsIndicatorService->fetchPasswordChangeIndicator(
            $this->getPasswordChangedDate()
        );
    }

    /**
     * Get Shipping address usage indicator by current profile first order date
     *
     * @return mixed
     * @throws \Exception
     */
    public function fetchShippingAddressUsageIndicator()
    {
        return $this->threedsIndicatorService->fetchShippingAddressUsageIndicator(
            $this->getFirstUseOfShippingAddress()
        );
    }

    /**
     * Fetch 3DSv2 Registration Indicator
     *
     * @return string
     * @throws \Exception
     */
    public function fetchRegistrationIndicator()
    {
         return $this->threedsIndicatorService->fetchRegistrationIndicator($this->getProfileFirstOrderDate());
    }

    /**
     * @return string|null
     */
    public function fetchReorderItemsIndicator()
    {
        $boughtProducts = $this->getProfileBoughtProducts();

        return ($boughtProducts === null)
            ? null
            : $this->threedsIndicatorService->fetchReorderItemsIndicator($this->products, $boughtProducts);
    }

    /**
     * Get profile items history
     *
     * @return array
     */
    public function getProfileBoughtProducts()
    {
        return ($this->customer === null)
            ? null
            : $this->customer->getOrderInformation()->getProducts();
    }

    /**
     * Returns customers first order date
     *
     * @return string|null
     */
    public function getProfileFirstOrderDate()
    {
        if ($this->customer === null) {
            return null;
        }

        return (array_key_exists('date', $this->getFirstOrderTime()) && $this->getFirstOrderTime()['date'] !== null)
            ? date(self::THREEDS_DATE_FORMAT, strtotime($this->getFirstOrderTime()['date']))
            : null;
    }

    /**
     * Return customer`s first order date
     *
     * @return mixed
     */
    public function getFirstOrderTime()
    {
        return (array)$this->customer->getOrderInformation()->getFirstOrderTime();
    }

    /**
     * Based on the submitted period parameters(for 24 hours, 6 months, and previous year)
     *
     * @param $period
     *
     * @return mixed
     * @throws \Exception
     */
    public function countOrdersPeriod($period)
    {
        $from = (new \DateTime())->sub(new \DateInterval($period));
        $now  = new \DateTime('now');

        if ($period == self::ACTIVITY_1_YEAR) {
            $previousYear = $this->getPreviousYear();

            return $this->getCustomerOrders(
                $previousYear['from'],
                $previousYear['to']
            );
        }

        if ($period == self::ACTIVITY_6_MONTHS) {
            return $this->getCustomerOrders(
                $from,
                $now,
                $period
            );
        }

        return $this->getCustomerOrders(
            $from,
            $now
        );
    }

    /**
     * Returns count of customer orders by specific parameters
     *
     * @param $dateFrom
     * @param $dateTo
     * @param string $period
     *
     * @return mixed
     */
    public function getCustomerOrders($dateFrom = '', $dateTo = '', $period = '')
    {
        if ($this->customer === null) {
            return null;
        }

        $paymentMethodId = $this->customer->getPaymentId();
        $query           = $this->modelManager->createQueryBuilder();

        $query
            ->select('COUNT(orders)')
            ->from(Order::class, 'orders')
            ->where('orders.orderTime BETWEEN :from AND :to')
            ->andWhere('orders.customerId = :id')
            ->andWhere('orders.paymentId = :paymentId');

        if ($period == self::ACTIVITY_6_MONTHS) {
            $query
                ->andWhere('orders.cleared = :paidStatus')
                ->setParameter(
                    ':paidStatus',
                    Status::PAYMENT_STATE_COMPLETELY_PAID
                );
        }

        $query
            ->setParameter(':from', $dateFrom->format(self::THREEDS_DATE_FORMAT))
            ->setParameter(':to', $dateTo->format(self::THREEDS_DATE_FORMAT))
            ->setParameter(':id', $this->getUserId())
            ->setParameter(':paymentId', $paymentMethodId);

        return $query->getQuery()
                     ->getSingleScalarResult();
    }

    /**
     * It returns the dates of the previous year, not one year back.
     *
     * @return array
     */
    private function getPreviousYear()
    {
        $previousYear = gmdate('Y', strtotime('-1 Year'));
        $dateFrom     = \DateTime::createFromFormat(
            self::THREEDS_DATE_FORMAT,
            "$previousYear-01-01 00:00:00"
        );
        $dateTo       = \DateTime::createFromFormat(
            self::THREEDS_DATE_FORMAT,
            "$previousYear-12-31 23:59:59"
        );

        return ['from' => $dateFrom, 'to' => $dateTo];
    }

    /**
     * @return |null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFirstUseOfShippingAddress()
    {
        if ($this->customer === null) {
            return null;
        }

        $paymentMethodId = $this->customer->getPaymentId();
        $query           = $this->modelManager->createQueryBuilder();

        $query
            ->select('orders.orderTime')
            ->from(Order::class, 'orders')
            ->leftJoin('orders.shipping', 'shipping')
            ->leftJoin('shipping.country', 'country')
            ->leftJoin('country.states', 'states')
            ->leftJoin('orders.customer', 'customer')
            ->andWhere('orders.customerId = :id')
            ->andWhere('orders.paymentId = :paymentId')
            ->andWhere('shipping.firstName = :firstName')
            ->andWhere('shipping.lastName = :lastName')
            ->andWhere('shipping.street = :street')
            ->andWhere('shipping.zipCode = :zipCode')
            ->andWhere('shipping.city = :city')
            ->orWhere('states.id = :stateId')
            ->andWhere('country.iso = :countryIso')
            ->orderBy('orders.orderTime', 'ASC')
            ->setParameter(':id', $this->getUserId())
            ->setParameter(':paymentId', $paymentMethodId)
            ->setParameter(':firstName', $this->paymentData->getShippingFirstName())
            ->setParameter(':lastName', $this->paymentData->getShippingLastName())
            ->setParameter(':street', $this->paymentData->getShippingAddress())
            ->setParameter(':zipCode', $this->paymentData->getShippingZipcode())
            ->setParameter(':city', $this->paymentData->getShippingCity())
            ->setParameter(':countryIso', $this->paymentData->getShippingCountry())
            ->setParameter(':stateId', $this->paymentData->getShippingState())
            ->setMaxResults(1);

        $result = $query
            ->getQuery()
            ->getOneOrNullResult();

        return isset($result['orderTime'])
            ? $result['orderTime']->format(self::THREEDS_DATE_FORMAT)
            : null;
    }
}
