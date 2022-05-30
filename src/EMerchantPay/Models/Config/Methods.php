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

namespace EMerchantPay\Models\Config;

use Genesis\Exceptions\Exception;
use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * The Configuration Settings Model for Checkout and Direct Method
 *
 * Class Methods
 * @package EMerchantPay\Models\Config
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @codingStandardsIgnoreStart
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="emerchantpay_config_methods", uniqueConstraints={@ORM\UniqueConstraint(name="idx_option_methods", columns={"options", "methods"})})
 * @codingStandardsIgnoreEnd
 */
class Methods extends ModelEntity
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
     * @var string $options
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $options;

    /**
     * @var string $optionValues
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $optionValues;

    /**
     * @var string $store
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $stores;

    /**
     * @var string $methods
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $methods;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $value
     */
    public function setOption($value)
    {
        $this->options = $value;
    }

    /**
     * @return string
     */
    public function getOption()
    {
        return $this->options;
    }

    /**
     * @param string $value
     * @throws Exception
     */
    public function setOptionValue($value)
    {
        if (empty($this->options)) {
            throw new Exception('Set Option first before its value');
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        $this->optionValues = $value;

        if (in_array($this->options, ['transaction_types', 'bank_codes'])) {
            $this->optionValues = serialize($this->optionValues);
        }
    }

    /**
     * @return string
     */
    public function getOptionValue()
    {
        if (in_array($this->options, ['transaction_types', 'bank_codes'])) {
            return unserialize($this->optionValues);
        }

        return $this->optionValues;
    }

    /**
     * @param string $value
     */
    public function setStore($value)
    {
        $this->stores = $value;
    }

    /**
     * @return string
     */
    public function getStore()
    {
        return $this->stores;
    }

    /**
     * @param string $value
     */
    public function setMethod($value)
    {
        $this->methods = $value;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->methods;
    }
}
