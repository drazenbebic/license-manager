<?php
/**
 * LicenseManager for WooCommerce - License functions
 *
 * Functions for license key manipulation.
 */

use LicenseManagerForWooCommerce\Enums\LicenseSource;
use LicenseManagerForWooCommerce\Enums\LicenseStatus as LicenseStatusEnum;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;

defined('ABSPATH') || exit;

/**
 * Adds a new license to the database.
 *
 * @param string $licenseKey  The license key being added
 * @param array  $licenseData Key/value pairs with the license table column names as keys
 * @return LicenseResourceModel|WP_Error
 */
function lmfwc_add_license($licenseKey, $licenseData = array())
{
    $status            = LicenseStatusEnum::INACTIVE;
    $orderId           = null;
    $productId         = null;
    $userId            = null;
    $expiresAt         = null;
    $validFor          = null;
    $timesActivatedMax = null;

    if (array_key_exists('status', $licenseData)) {
        $status = $licenseData['status'];
    }

    if (array_key_exists('order_id', $licenseData)) {
        $orderId = $licenseData['order_id'];
    }

    if (array_key_exists('product_id', $licenseData)) {
        $productId = $licenseData['product_id'];
    }

    if (array_key_exists('user_id', $licenseData)) {
        $productId = $licenseData['user_id'];
    }

    if (array_key_exists('expires_at', $licenseData)) {
        $expiresAt = $licenseData['expires_at'];
    }

    if (array_key_exists('valid_for', $licenseData)) {
        $validFor = $licenseData['valid_for'];
    }

    if (array_key_exists('times_activated_max', $licenseData)) {
        $timesActivatedMax = $licenseData['times_activated_max'];
    }

    if (!in_array($status, LicenseStatusEnum::$status)) {
        return new WP_Error('\'status\' array key not valid. Possible values are: 1 for SOLD, 2 for DELIVERED, 3 for ACTIVE, 4 for INACTIVE, and 5 for DISABLED.');
    }

    if (apply_filters('lmfwc_duplicate', $licenseKey)) {
        return new WP_Error("The license key '{$licenseKey}' already exists.");
    }

    if ($expiresAt !== null) {
        try {
            new DateTime($expiresAt);
        } catch (Exception $e) {
            return new WP_Error($e->getMessage());
        }
    }

    $encryptedLicenseKey = apply_filters('lmfwc_encrypt', $licenseKey);
    $hashedLicenseKey    = apply_filters('lmfwc_hash', $licenseKey);

    $queryData = array(
        'order_id'            => $orderId,
        'product_id'          => $productId,
        'license_key'         => $encryptedLicenseKey,
        'hash'                => $hashedLicenseKey,
        'expires_at'          => $expiresAt,
        'valid_for'           => $validFor,
        'source'              => LicenseSource::IMPORT,
        'status'              => $status,
        'times_activated_max' => $timesActivatedMax
    );

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->insert($queryData);

    if (!$license) {
        return new WP_Error("The license key '{$licenseKey}' could not be added");
    }

    // Update the stock
    if ($license->getProductId() !== null && $license->getStatus() === LicenseStatusEnum::ACTIVE) {
        lmfwc_stock_increase($license->getProductId());
    }

    return $license;
}

/**
 * Retrieves a single license from the database.
 *
 * @param string $licenseKey The license key to be deleted.
 * @return LicenseResourceModel|WP_Error
 */
function lmfwc_get_license($licenseKey)
{
    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return new WP_Error("The license key '{$licenseKey}' could not be found.");
    }

    return $license;
}

/**
 * Retrieves multiple license keys by a query array.
 *
 * @param array       $query   Key/value pairs with the license table column names as keys
 * @param string|null $orderBy Column name, followed by either ASC or DESC
 * @return LicenseResourceModel[]
 */
function lmfwc_get_licenses($query, $orderBy = null)
{
    if (array_key_exists('license_key', $query)) {
        $query['hash'] = apply_filters('lmfwc_hash', $query['license_key']);
        unset($query['license_key']);
    }

    /** @var LicenseResourceModel[] $licenses */
    $licenses = LicenseResourceRepository::instance()->findAllBy($query, $orderBy);

    if (!$licenses) {
        return array();
    }

    return $licenses;
}

/**
 * Updates the specified license.
 *
 * @param string $licenseKey  The license key being updated.
 * @param array  $licenseData Key/value pairs of the updated data.
 * @return LicenseResourceModel|WP_Error
 */
function lmfwc_update_license($licenseKey, $licenseData)
{
    $updateData = array();

    /** @var LicenseResourceModel $oldLicense */
    $oldLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$oldLicense) {
        return new WP_Error("The license key '{$licenseKey}' could not be found.");
    }

    // Order ID
    if (array_key_exists('order_id', $licenseData)) {
        if ($licenseData['order_id'] === null) {
            $updateData['order_id'] = null;
        } else {
            $updateData['order_id'] = (int)$licenseData['order_id'];
        }
    }

    // Product ID
    if (array_key_exists('product_id', $licenseData)) {
        if ($licenseData['product_id'] === null) {
            $updateData['product_id'] = null;
        } else {
            $updateData['product_id'] = (int)$licenseData['product_id'];
        }
    }

    // User ID
    if (array_key_exists('user_id', $licenseData)) {
        if ($licenseData['user_id'] === null) {
            $updateData['user_id'] = null;
        } else {
            $updateData['user_id'] = (int)$licenseData['user_id'];
        }
    }

    // License key
    if (array_key_exists('license_key', $licenseData)) {
        // Check for possible duplicates
        if (apply_filters('lmfwc_duplicate', $licenseData['license_key'], $oldLicense->getId())) {
            return new WP_Error("The license key '{$licenseData['license_key']}' already exists.");
        }

        $updateData['license_key'] = apply_filters('lmfwc_encrypt', $licenseData['license_key']);
        $updateData['hash']        = apply_filters('lmfwc_hash', $licenseData['license_key']);
    }

    // Expires at
    if (array_key_exists('expires_at', $licenseData)) {
        if ($licenseData['expires_at'] !== null) {
            try {
                new DateTime($licenseData['expires_at']);
            } catch (Exception $e) {
                return new WP_Error($e->getMessage());
            }
        }

        $updateData['expires_at'] = $licenseData['expires_at'];
    }

    // Valid for
    if (array_key_exists('valid_for', $licenseData)) {
        if ($licenseData['valid_for'] === null) {
            $updateData['valid_for'] = null;
        } else {
            $updateData['valid_for'] = (int)$licenseData['valid_for'];
        }
    }

    // Status
    if (array_key_exists('status', $licenseData)) {
        if (!in_array((int)$licenseData['status'], LicenseStatusEnum::$status)) {
            return new WP_Error('The \'status\' array key not valid. Possible values are: 1 for SOLD, 2 for DELIVERED, 3 for ACTIVE, 4 for INACTIVE, and 5 for DISABLED.');
        }

        $updateData['status'] = (int)$licenseData['status'];
    }

    // Times activated
    if (array_key_exists('times_activated', $licenseData)) {
        if ($licenseData['times_activated'] === null) {
            $updateData['times_activated'] = null;
        } else {
            $updateData['times_activated'] = (int)$licenseData['times_activated'];
        }
    }

    // Times activated max
    if (array_key_exists('times_activated_max', $licenseData)) {
        if ($licenseData['times_activated_max'] === null) {
            $updateData['times_activated_max'] = null;
        } else {
            $updateData['times_activated_max'] = (int)$licenseData['times_activated_max'];
        }
    }

    // Update the stock
    if ($oldLicense->getProductId() !== null && $oldLicense->getStatus() === LicenseStatusEnum::ACTIVE) {
        lmfwc_stock_decrease($oldLicense->getProductId());
    }

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->updateBy(
        array(
            'hash' => $oldLicense->getHash()
        ),
        $updateData
    );

    if (!$license) {
        return new WP_Error("The license key '{$licenseKey}' could not be updated.");
    }

    $newLicenseHash = apply_filters('lmfwc_hash', $licenseKey);

    if (array_key_exists('hash', $updateData)) {
        $newLicenseHash = $updateData['hash'];
    }

    /** @var LicenseResourceModel $newLicense */
    $newLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => $newLicenseHash
        )
    );

    if (!$newLicense) {
        return new WP_Error('The updated license key could not be found.');
    }

    // Update the stock
    if ($newLicense->getProductId() !== null && $newLicense->getStatus() === LicenseStatusEnum::ACTIVE) {
        lmfwc_stock_increase($newLicense->getProductId());
    }

    return $newLicense;
}

/**
 * Deletes the specified license.
 *
 * @param string $licenseKey The license key to be deleted.
 * @return bool|WP_Error
 */
function lmfwc_delete_license($licenseKey)
{
    /** @var LicenseResourceModel $oldLicense */
    $oldLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    // Update the stock
    if ($oldLicense
        && $oldLicense->getProductId() !== null
        && $oldLicense->getStatus() === LicenseStatusEnum::ACTIVE
    ) {
        lmfwc_stock_decrease($oldLicense->getProductId());
    }

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->deleteBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return new WP_Error("The license key '{$licenseKey}' could not be found.");
    }

    return true;
}

/**
 * Increments the "times_activated" column, if "times_activated_max" allows it.
 *
 * @param string $licenseKey The license key to be activated.
 * @return LicenseResourceModel|WP_Error
 */
function lmfwc_activate_license($licenseKey)
{
    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return new WP_Error("The license key '{$licenseKey}' could not be found.");
    }

    $timesActivated    = null;
    $timesActivatedMax = null;

    if ($license->getTimesActivated() !== null) {
        $timesActivated = absint($license->getTimesActivated());
    }

    if ($license->getTimesActivatedMax() !== null) {
        $timesActivatedMax = absint($license->getTimesActivatedMax());
    }

    if ($license->getStatus() === LicenseStatusEnum::DISABLED) {
        return new WP_Error("The license key '{$licenseKey}' is disabled.");
    }

    if ($timesActivatedMax && ($timesActivated >= $timesActivatedMax)) {
        return new WP_Error("The license key '{$licenseKey}' reached its maximum activation count.");
    }

    if (!$timesActivated) {
        $timesActivatedNew = 1;
    } else {
        $timesActivatedNew = (int)$timesActivated + 1;
    }

    /** @var LicenseResourceModel $updatedLicense */
    $updatedLicense = LicenseResourceRepository::instance()->update(
        $license->getId(),
        array(
            'times_activated' => $timesActivatedNew
        )
    );

    if (!$updatedLicense) {
        return new WP_Error("The license key '{$licenseKey}' could not be activated.");
    }

    return $updatedLicense;
}

/**
 * Decrements the "times_activated" column, if possible.
 *
 * @param string $licenseKey The license key to be deactivated.
 * @return LicenseResourceModel|WP_Error
 */
function lmfwc_deactivate_license($licenseKey)
{
    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return new WP_Error("The license key '{$licenseKey}' could not be found.");
    }

    $timesActivated = null;

    if ($license->getTimesActivated() !== null) {
        $timesActivated = $license->getTimesActivated();
    }

    if ($license->getStatus() === LicenseStatusEnum::DISABLED) {
        return new WP_Error("The license key '{$licenseKey}' is disabled.");
    }

    if (!$timesActivated || $timesActivated === 0) {
        return new WP_Error("The license key '{$licenseKey}' has not been activated yet.");
    }

    $timesActivatedNew = (int)$timesActivated - 1;

    /** @var LicenseResourceModel $updatedLicense */
    $updatedLicense = LicenseResourceRepository::instance()->update(
        $license->getId(),
        array(
            'times_activated' => $timesActivatedNew
        )
    );

    if (!$updatedLicense) {
        return new WP_Error("The license key '{$licenseKey}' could not be deactivated.");
    }

    return $updatedLicense;
}
