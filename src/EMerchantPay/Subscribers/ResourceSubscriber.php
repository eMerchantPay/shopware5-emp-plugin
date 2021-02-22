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

namespace EMerchantPay\Subscribers;

use Enlight\Event\SubscriberInterface;

/**
 * Register Libraries, Views etc...
 *
 * Class ResourceSubscriber
 *
 * @package EMerchantPay\Subscribers
 */
class ResourceSubscriber implements SubscriberInterface
{
    /**
     * Library directory in the plugin tree
     */
    const LIBRARIES_DIR = 'Libraries';

    /**
     * Plug-in root directory
     *
     * @var string $pluginDirectory
     */
    protected $pluginDirectory;

    /**
     * @param string $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * Affected Events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatchSecure_Frontend' => 'onPostDispatch',
            'Enlight_Controller_Action_PreDispatchSecure_Backend'  => 'onPostDispatch',
            'Enlight_Controller_Action_PreDispatch_Frontend'       => 'onPostDispatch',
            'Enlight_Controller_Action_PreDispatch_Backend'        => 'onPostDispatch',
        ];
    }

    /**
     * Action
     */
    public function onPostDispatch()
    {
        $this->registerLibraries();
    }

    /**
     * Register external Libraries
     */
    private function registerLibraries()
    {
        $librariesPath = $this->pluginDirectory . DIRECTORY_SEPARATOR . self::LIBRARIES_DIR;

        require_once "{$librariesPath}/genesis/vendor/autoload.php";
    }
}
