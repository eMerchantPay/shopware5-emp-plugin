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

namespace EMerchantPay;

use Doctrine\ORM\Tools\SchemaTool;
use EMerchantPay\Components\Helpers\MethodConfigs;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;

/**
 * EMerchantPay Payment Plugin
 *
 * Class EMerchantPay
 * @package EMerchantPay
 */
class EMerchantPay extends Plugin
{
    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        // Create Databases
        $this->createDatabase();
        $this->createRecords();

        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $options = [
            'name' => 'emerchantpay_checkout',
            'description' => 'emerchantpay Checkout',
            'action' => 'EmerchantpayPayment',
            'active' => 0,
            'position' => 0,
            'additionalDescription' =>
                '<div><img style="padding: 10px 0 10px 0" ' .
                'src="custom/plugins/EMerchantPay/Resources/views/frontend/_public/src/img/emerchantpay_checkout.png" '.
                'alt="emerchantpay Checkout"></div>' .
                '<div>' .
                '<b>emerchantpay Checkout</b> offers a secure way to pay for your order, ' .
                'using <b>Credit/Debit/Prepaid Card</b> <b>e-Wallet</b> or <b>Vouchers</b>' .
                '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);

        $options = [
            'name' => 'emerchantpay_direct',
            'description' => 'emerchantpay Direct',
            'action' => 'EmerchantpayPayment',
            'active' => 0,
            'position' => 0,
            'additionalDescription' =>
                '<div><img style="padding: 10px 0 10px 0" '.
                'src="custom/plugins/EMerchantPay/Resources/views/frontend/_public/src/img/emerchantpay_direct.png" '.
                'alt="emerchantpay Direct"></div>' .
                '<div>' .
                '<b>emerchantpay Direct</b> offers a secure way to pay for your order, using <b>Credit/Debit Card</b>' .
                '</div>'
        ];

        $installer->createOrUpdate($context->getPlugin(), $options);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);

        if (false === $context->keepUserData()) {
            $this->removeDatabase();
        }

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * Helper Methods
     */

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }

    /**
     * Creates Plug-in Tables
     */
    private function createDatabase()
    {
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);

        $classes = $this->getClasses($modelManager);

        $tool->updateSchema($classes, true);
    }

    /**
     * Remove Plug-in Tables
     */
    private function removeDatabase()
    {
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);

        $classes = $this->getClasses($modelManager);

        $tool->dropSchema($classes);
    }

    /**
     * @param ModelManager $modelManager
     * @return array
     */
    private function getClasses(ModelManager $modelManager)
    {
        return [
            $modelManager->getClassMetadata(Models\Transaction\Transaction::class),
            $modelManager->getClassMetadata(Models\Config\Methods::class)
        ];
    }

    /**
     * Fills data into the Emerchantpay Database Tables
     */
    private function createRecords()
    {
        // Methods Config Initial Data
        $checkoutConfigs = MethodConfigs::getConfigCheckoutData();
        foreach ($checkoutConfigs as $config) {
            $options      = $config['options'];
            $optionValues = $config['optionValues'];
            $method       = $config['methods'];

            $sql = "INSERT IGNORE INTO emerchantpay_config_methods (options, optionValues, methods) " .
                "VALUES ('${options}', '${optionValues}', '${method}')";
            $this->container->get('dbal_connection')->exec($sql);
        }

        $directConfigs = MethodConfigs::getConfigDirectData();
        foreach ($directConfigs as $config) {
            $options      = $config['options'];
            $optionValues = $config['optionValues'];
            $method       = $config['methods'];

            $sql = "INSERT IGNORE INTO emerchantpay_config_methods (options, optionValues, methods) " .
                "VALUES ('${options}', '${optionValues}', '${method}')";
            $this->container->get('dbal_connection')->exec($sql);
        }
    }
}
