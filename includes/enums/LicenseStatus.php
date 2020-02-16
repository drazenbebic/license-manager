<?php

namespace LicenseManagerForWooCommerce\Enums;

use ReflectionClass;
use ReflectionException;

defined('ABSPATH') || exit;

abstract class LicenseStatus
{
    /**
     * Enumerator value used for sold licenses.
     *
     * @var int
     */
    const SOLD = 1;

    /**
     * Enumerator value used for delivered licenses.
     *
     * @var int
     */
    const DELIVERED = 2;

    /**
     * Enumerator value used for active licenses.
     *
     * @var int
     */
    const ACTIVE = 3;

    /**
     * Enumerator value used for inactive licenses.
     *
     * @var int
     */
    const INACTIVE = 4;

    /**
     * Available enumerator values.
     *
     * @var array
     */
    public static $status = array(
        self::SOLD,
        self::DELIVERED,
        self::ACTIVE,
        self::INACTIVE
    );

    /**
     * Available text representations of the enumerator
     *
     * @var array
     */
    public static $enumArray = array(
        'sold',
        'delivered',
        'active',
        'inactive'
    );

    /**
     * Key/value pairs of text representations and actual enumerator values.
     *
     * @var array
     */
    public static $values = array(
        'sold'      => self::SOLD,
        'delivered' => self::DELIVERED,
        'active'    => self::ACTIVE,
        'inactive'  => self::INACTIVE
    );

    /**
     * Returns the string representation of a specific enumerator value.
     *
     * @param int $status Status enumerator value
     *
     * @return string
     */
    public static function getExportLabel($status)
    {
        $labels = array(
            self::SOLD      => 'SOLD',
            self::DELIVERED => 'DELIVERED',
            self::ACTIVE    => 'ACTIVE',
            self::INACTIVE  => 'INACTIVE'
        );

        return $labels[$status];
    }

    /**
     * Returns an array of enumerators to be used as a dropdown.
     *
     * @return array
     */
    public static function dropdown()
    {
        return array(
            array(
                'value' => self::ACTIVE,
                'name' => __('Active', 'license-manager-for-woocommerce')
            ),
            array(
                'value' => self::INACTIVE,
                'name' => __('Inactive', 'license-manager-for-woocommerce')
            ),
            array(
                'value' => self::SOLD,
                'name' => __('Sold', 'license-manager-for-woocommerce')
            ),
            array(
                'value' => self::DELIVERED,
                'name' => __('Delivered', 'license-manager-for-woocommerce')
            )
        );
    }

    /**
     * Returns the class constants as an array.
     *
     * @return array
     * @throws ReflectionException
     */
    public static function getConstants()
    {
        $oClass = new ReflectionClass(__CLASS__);

        return $oClass->getConstants();
    }
}
