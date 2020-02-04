<?php
/**
 * LicenseManager for WooCommerce Core Functions
 *
 * General core functions available on both the front-end and admin.
 */

use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use LicenseManagerForWooCommerce\Repositories\Resources\LicenseInstance as LicenseInstanceResourceRepository;

defined('ABSPATH') || exit;

/**
 * Checks if a license key already exists inside the database table.
 *
 * @param string   $licenseKey
 * @param null|int $licenseKeyId
 *
 * @return bool
 */
function lmfwc_duplicate($licenseKey, $licenseKeyId = null)
{
    $duplicate = false;
    $hash      = apply_filters('lmfwc_hash', $licenseKey);

    // Add action
    if ($licenseKeyId === null) {
        $query = array('hash' => $hash);

        if (LicenseResourceRepository::instance()->findBy($query)) {
            $duplicate = true;
        }
    }

    // Update action
    elseif ($licenseKeyId !== null && is_numeric($licenseKeyId)) {
        global $wpdb;

        $table = LicenseResourceRepository::instance()->getTable();

        $query = "
            SELECT
                id
            FROM
                {$table}
            WHERE
                1=1
                AND hash = '{$hash}'
                AND id NOT LIKE {$licenseKeyId}
            ;
        ";

        if (LicenseResourceRepository::instance()->query($query)) {
            $duplicate = true;
        }
    }

    return $duplicate;
}
add_filter('lmfwc_duplicate', 'lmfwc_duplicate', 10, 2);

/**
 * Checks if a license instance ID for a given license key already exists inside the database table.
 *
 * @param string   $instanceKey
 * @param int      $licenseID
 * @param null|int $instanceID
 *
 * @return bool
 */
function lmfwc_duplicate_instance($instanceKey, $licenseID, $instanceID = null)
{
    $duplicate = false;
    $hash      = apply_filters('lmfwc_hash', $instanceKey);

    // Add action
    if ($instanceID === null) {
        $query = array(
          'license_id'    => $licenseID,
          'instance_hash' => $hash
        );

        if (LicenseInstanceResourceRepository::instance()->findBy($query)) {
            $duplicate = true;
        }
    }

    // Update action
    elseif ($instanceID !== null && is_numeric($instanceID)) {
        global $wpdb;

        $table = LicenseInstanceResourceRepository::instance()->getTable();

        $query = "
            SELECT
                id
            FROM
                {$table}
            WHERE
                1=1
                AND license_id = '{$licenseID}'
                AND instance_hash = '{$hash}'
                AND id NOT LIKE {$instanceID}
            ;
        ";

        if (LicenseInstanceResourceRepository::instance()->query($query)) {
            $duplicate = true;
        }
    }

    return $duplicate;
}
add_filter('lmfwc_duplicate_instance', 'lmfwc_duplicate_instance', 10, 3);

/**
 * Generates a random hash.
 *
 * @return string
 */
function lmfwc_rand_hash()
{
    if ($hash = apply_filters('lmfwc_rand_hash', null)) {
        return $hash;
    }

    if (function_exists('wc_rand_hash')) {
        return wc_rand_hash();
    }

    if (!function_exists('openssl_random_pseudo_bytes')) {
        return sha1(wp_rand());
    }

    return bin2hex(openssl_random_pseudo_bytes(20));
}