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
use Shopware\Components\Plugin\Context\UpdateContext;
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

        $this->addCustomerAttributes();
    }

    /**
     * @param UpdateContext $context
     * @return void
     */
    public function update(UpdateContext $context)
    {
        $this->addCustomerAttributes();
        $this->addWpfTokenizationOptionDefaults();
        $this->addBankCodeOptionDefaults();
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);

        if (false === $context->keepUserData()) {
            $this->removeDatabase();
            $this->removeCustomerAttributes();
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

    /**
     * Remove customers attributes and catch exception if new version is copied over the old one
     *
     * @return void
     */
    private function removeCustomerAttributes()
    {
        try {
            $service = $this->container->get('shopware_attribute.crud_service');
            $service->delete('s_user_attributes', 'emp_token_consumer_id');
        } catch (\Exception $exception) {
            $logger = $this->container->get('pluginlogger');
            $logger->debug('Ignore missing user\'s attribute in the DB.');
        }

        $this->clearMetaDataCache();
    }

    /**
     * @return void
     */
    private function addCustomerAttributes()
    {
        // Add consumer_id to the Users' record via attribute
        $service = $this->container->get('shopware_attribute.crud_service');
        $service->update('s_user_attributes', 'emp_token_consumer_id', 'string');

        $this->clearMetaDataCache();
        $this->container->get('models')->generateAttributeModels(['s_user_attributes']);
    }

    /**
     * Add / Update checkout tokenization option in Emerchantpay Database Tables
     */
    private function addWpfTokenizationOptionDefaults()
    {
        $checkoutConfigs          = MethodConfigs::getConfigCheckoutData();
        $wpfTokenizationConfigKey = array_search(
            'wpf_tokenization',
            array_column($checkoutConfigs, 'options')
        );

        $wpfTokenizationConfig = $checkoutConfigs[$wpfTokenizationConfigKey];

        $options      = $wpfTokenizationConfig['options'];
        $optionValues = $wpfTokenizationConfig['optionValues'];
        $method       = $wpfTokenizationConfig['methods'];
        $sql = "INSERT IGNORE INTO emerchantpay_config_methods (options, optionValues, methods) " .
            "VALUES ('${options}', '${optionValues}', '${method}')";
        $this->container->get('dbal_connection')->exec($sql);
    }

    /**
     * Add / Update checkout bank_code option in Emerchantpay Database Tables
     *
     * @return void
     */
    private function addBankCodeOptionDefaults()
    {
        $checkoutConfigs   = MethodConfigs::getConfigCheckoutData();
        $bankCodeConfigKey = array_search(
            'bank_codes',
            array_column($checkoutConfigs, 'options')
        );

        $bankCodeConfig    = $checkoutConfigs[$bankCodeConfigKey];

        $options           = $bankCodeConfig['options'];
        $optionValues      = $bankCodeConfig['optionValues'];
        $method            = $bankCodeConfig['methods'];
        $sql = "INSERT IGNORE INTO emerchantpay_config_methods (options, optionValues, methods) " .
            "VALUES ('${options}', '${optionValues}', '${method}')";
        $this->container->get('dbal_connection')->exec($sql);
    }

    /**
     * Clears MetaData cache
     *
     * @return void
     */
    private function clearMetaDataCache()
    {
        $modelManager  = $this->container->get('models');
        $metaDataCache = $modelManager->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
    }
}
