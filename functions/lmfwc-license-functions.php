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
 * @param string      $licenseKey        The license key being added
 * @param int         $status            Possible values: 1 = SOLD, 2 = DELIVERED, 3 = ACTIVE, 4 = INACTIVE
 * @param int|null    $orderId           WooCommerce Order ID
 * @param int|null    $productId         WooCommerce Product ID
 * @param string|null $expiresAt         Expiration DateTime format string: Y-m-d H:i:s
 * @param string|null $validFor          Number of days for which the license key is valid after purchase
 * @param int|null    $timesActivatedMax Maximum activation count
 * @param int|null    $timesActivated    Number of times the license key has been activated.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_add_license($licenseKey, $status, $orderId = null, $productId = null, $expiresAt = null, $validFor = null, $timesActivated = null, $timesActivatedMax = null)
{
    if (!in_array($status, LicenseStatusEnum::$status)) {
        throw new Exception('\'status\' array key not valid. Possible values are: 1 for SOLD, 2 for DELIVERED,
        3 for ACTIVE, and 4 for INACTIVE.');
    }

    if (($timesActivated !== null && $timesActivatedMax !== null) && ($timesActivated > $timesActivatedMax)) {
        throw new Exception('The activation count cannot be larger than the maximum activation count.');
    }

    if (apply_filters('lmfwc_duplicate', $licenseKey)) {
        throw new Exception("The license key '{$licenseKey}' already exists.");
    }

    if ($expiresAt !== null) {
        new DateTime($expiresAt);
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
        'times_activated'     => $timesActivated,
        'times_activated_max' => $timesActivatedMax
    );

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->insert($queryData);

    if (!$license) {
        return false;
    }

    return $license;
}

/**
 * Retrieves a single license from the database.
 *
 * @param string $licenseKey The license key to be deleted.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
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
        return false;
    }

    return $license;
}

/**
 * Updates the specified license.
 *
 * @param string $licenseKey The license key being updated.
 * @param array  $updateData Key/value pairs of the updated data.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_update_license($licenseKey, $updateData)
{
    $updateQuery = array();

    /** @var LicenseResourceModel $oldLicense */
    $oldLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$oldLicense) {
        return false;
    }

    // Order ID
    if (array_key_exists('orderId', $updateData)) {
        if ($updateData['orderId'] === null) {
            $updateQuery['order_id'] = null;
        } else {
            $updateQuery['order_id'] = intval($updateData['orderId']);
        }
    }

    // Product ID
    if (array_key_exists('productId', $updateData)) {
        if ($updateData['productId'] === null) {
            $updateQuery['product_id'] = null;
        } else {
            $updateQuery['product_id'] = intval($updateData['productId']);
        }
    }

    // License key
    if (array_key_exists('licenseKey', $updateData)) {
        // Check for possible duplicates
        if (apply_filters('lmfwc_duplicate', $updateData['licenseKey'], $oldLicense->getId())) {
            throw new Exception("The license key '{$updateData['licenseKey']}' already exists.");
        }

        $updateQuery['license_key'] = apply_filters('lmfwc_encrypt', $updateData['licenseKey']);
        $updateQuery['hash']        = apply_filters('lmfwc_hash', $updateData['licenseKey']);
    }

    // Expires at
    if (array_key_exists('expiresAt', $updateData)) {
        if ($updateData['expiresAt'] !== null) {
            new DateTime($updateData['expiresAt']);
        }

        $updateQuery['expires_at'] = $updateData['expiresAt'];
    }

    // Valid for
    if (array_key_exists('validFor', $updateData)) {
        if ($updateData['validFor'] === null) {
            $updateQuery['valid_for'] = null;
        } else {
            $updateQuery['valid_for'] = intval($updateData['validFor']);
        }
    }

    // Status
    if (array_key_exists('status', $updateData)) {
        if (!in_array(intval($updateData['status']), LicenseStatusEnum::$status)) {
            throw new Exception('The \'status\' array key not valid. Possible values are: 1 for SOLD, 2 for
            DELIVERED, 3 for ACTIVE, and 4 for INACTIVE.');
        }

        $updateQuery['status'] = intval($updateData['status']);
    }

    // Times activated
    if (array_key_exists('timesActivated', $updateData)) {
        if ($updateData['timesActivated'] === null) {
            $updateQuery['times_activated'] = null;
        } else {
            $updateQuery['times_activated'] = intval($updateData['timesActivated']);
        }
    }

    // Times activated max
    if (array_key_exists('timesActivatedMax', $updateData)) {
        if ($updateData['timesActivatedMax'] === null) {
            $updateQuery['times_activated_max'] = null;
        } else {
            $updateQuery['times_activated_max'] = intval($updateData['timesActivatedMax']);
        }
    }

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->updateBy(
        array(
            'hash' => $oldLicense->getHash()
        ),
        $updateQuery
    );

    if (!$license) {
        return false;
    }

    $newLicenseHash = apply_filters('lmfwc_hash', $licenseKey);

    if (array_key_exists('hash', $updateQuery)) {
        $newLicenseHash = $updateQuery['hash'];
    }

    $newLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => $newLicenseHash
        )
    );

    if (!$newLicense) {
        return false;
    }

    return $newLicense;
}

/**
 * Deletes the specified license.
 *
 * @param string $licenseKey The license key to be deleted.
 *
 * @return bool
 * @throws Exception
 */
function lmfwc_delete_license($licenseKey)
{
    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->deleteBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return false;
    }

    return true;
}

/**
 * Increments the "times_activated" column, if "times_activated_max" allows it.
 *
 * @param string $licenseKey The license key to be activated.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_activate_license($licenseKey)
{
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return false;
    }

    $timesActivated    = null;
    $timesActivatedMax = null;

    if ($license->getTimesActivated() !== null) {
        $timesActivated = absint($license->getTimesActivated());
    }

    if ($license->getTimesActivatedMax() !== null) {
        $timesActivatedMax = absint($license->getTimesActivatedMax());
    }

    if ($timesActivatedMax && ($timesActivated >= $timesActivatedMax)) {
        throw new Exception("License Key: {$licenseKey} reached maximum activation count.");
    }

    if (!$timesActivated) {
        $timesActivatedNew = 1;
    } else {
        $timesActivatedNew = intval($timesActivated) + 1;
    }

    /** @var LicenseResourceModel $updatedLicense */
    $updatedLicense = LicenseResourceRepository::instance()->update(
        $license->getId(),
        array(
            'times_activated' => $timesActivatedNew
        )
    );

    if (!$updatedLicense) {
        return false;
    }

    return $updatedLicense;
}

/**
 * Decrements the "times_activated" column, if possible.
 *
 * @param string $licenseKey The license key to be deactivated.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_deactivate_license($licenseKey)
{
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return false;
    }

    $timesActivated = null;

    if ($license->getTimesActivated() !== null) {
        $timesActivated = absint($license->getTimesActivated());
    }

    if (!$timesActivated || $timesActivated === 0) {
        throw new Exception("License Key: {$licenseKey} has not been activated yet.");
    }

    $timesActivatedNew = intval($timesActivated) - 1;

    /** @var LicenseResourceModel $updatedLicense */
    $updatedLicense = LicenseResourceRepository::instance()->update(
        $license->getId(),
        array(
            'times_activated' => $timesActivatedNew
        )
    );

    if (!$updatedLicense) {
        return false;
    }

    return $updatedLicense;
=======
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
 * @param string      $licenseKey        The license key being added
 * @param int         $status            Possible values: 1 = SOLD, 2 = DELIVERED, 3 = ACTIVE, 4 = INACTIVE
 * @param int|null    $orderId           WooCommerce Order ID
 * @param int|null    $productId         WooCommerce Product ID
 * @param string|null $expiresAt         Expiration DateTime format string: Y-m-d H:i:s
 * @param string|null $validFor          Number of days for which the license key is valid after purchase
 * @param int|null    $timesActivatedMax Maximum activation count
 * @param int|null    $timesActivated    Number of times the license key has been activated.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_add_license($licenseKey, $status, $orderId = null, $productId = null, $expiresAt = null, $validFor = null, $timesActivated = null, $timesActivatedMax = null)
{
    if (!in_array($status, LicenseStatusEnum::$status)) {
        throw new Exception('\'status\' array key not valid. Possible values are: 1 for SOLD, 2 for DELIVERED,
        3 for ACTIVE, and 4 for INACTIVE.');
    }

    if (($timesActivated !== null && $timesActivatedMax !== null) && ($timesActivated > $timesActivatedMax)) {
        throw new Exception('The activation count cannot be larger than the maximum activation count.');
    }

    if (apply_filters('lmfwc_duplicate', $licenseKey)) {
        throw new Exception("The license key '{$licenseKey}' already exists.");
    }

    if ($expiresAt !== null) {
        new DateTime($expiresAt);
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
        'times_activated'     => $timesActivated,
        'times_activated_max' => $timesActivatedMax
    );

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->insert($queryData);

    if (!$license) {
        return false;
    }

    return $license;
}

/**
 * Retrieves a single license from the database.
 *
 * @param string $licenseKey The license key to be deleted.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
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
        return false;
    }

    return $license;
}

/**
 * Updates the specified license.
 *
 * @param string $licenseKey The license key being updated.
 * @param array  $updateData Key/value pairs of the updated data.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_update_license($licenseKey, $updateData)
{
    $updateQuery = array();

    /** @var LicenseResourceModel $oldLicense */
    $oldLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$oldLicense) {
        return false;
    }

    // Order ID
    if (array_key_exists('orderId', $updateData)) {
        if ($updateData['orderId'] === null) {
            $updateQuery['order_id'] = null;
        } else {
            $updateQuery['order_id'] = intval($updateData['orderId']);
        }
    }

    // Product ID
    if (array_key_exists('productId', $updateData)) {
        if ($updateData['productId'] === null) {
            $updateQuery['product_id'] = null;
        } else {
            $updateQuery['product_id'] = intval($updateData['productId']);
        }
    }

    // License key
    if (array_key_exists('licenseKey', $updateData)) {
        // Check for possible duplicates
        if (apply_filters('lmfwc_duplicate', $updateData['licenseKey'], $oldLicense->getId())) {
            throw new Exception("The license key '{$updateData['licenseKey']}' already exists.");
        }

        $updateQuery['license_key'] = apply_filters('lmfwc_encrypt', $updateData['licenseKey']);
        $updateQuery['hash']        = apply_filters('lmfwc_hash', $updateData['licenseKey']);
    }

    // Expires at
    if (array_key_exists('expiresAt', $updateData)) {
        if ($updateData['expiresAt'] !== null) {
            new DateTime($updateData['expiresAt']);
        }

        $updateQuery['expires_at'] = $updateData['expiresAt'];
    }

    // Valid for
    if (array_key_exists('validFor', $updateData)) {
        if ($updateData['validFor'] === null) {
            $updateQuery['valid_for'] = null;
        } else {
            $updateQuery['valid_for'] = intval($updateData['validFor']);
        }
    }

    // Status
    if (array_key_exists('status', $updateData)) {
        if (!in_array(intval($updateData['status']), LicenseStatusEnum::$status)) {
            throw new Exception('The \'status\' array key not valid. Possible values are: 1 for SOLD, 2 for
            DELIVERED, 3 for ACTIVE, and 4 for INACTIVE.');
        }

        $updateQuery['status'] = intval($updateData['status']);
    }

    // Times activated
    if (array_key_exists('timesActivated', $updateData)) {
        if ($updateData['timesActivated'] === null) {
            $updateQuery['times_activated'] = null;
        } else {
            $updateQuery['times_activated'] = intval($updateData['timesActivated']);
        }
    }

    // Times activated max
    if (array_key_exists('timesActivatedMax', $updateData)) {
        if ($updateData['timesActivatedMax'] === null) {
            $updateQuery['times_activated_max'] = null;
        } else {
            $updateQuery['times_activated_max'] = intval($updateData['timesActivatedMax']);
        }
    }

    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->updateBy(
        array(
            'hash' => $oldLicense->getHash()
        ),
        $updateQuery
    );

    if (!$license) {
        return false;
    }

    $newLicenseHash = apply_filters('lmfwc_hash', $licenseKey);

    if (array_key_exists('hash', $updateQuery)) {
        $newLicenseHash = $updateQuery['hash'];
    }

    $newLicense = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => $newLicenseHash
        )
    );

    if (!$newLicense) {
        return false;
    }

    return $newLicense;
}

/**
 * Deletes the specified license.
 *
 * @param string $licenseKey The license key to be deleted.
 *
 * @return bool
 * @throws Exception
 */
function lmfwc_delete_license($licenseKey)
{
    /** @var LicenseResourceModel $license */
    $license = LicenseResourceRepository::instance()->deleteBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return false;
    }

    return true;
}

/**
 * Increments the "times_activated" column, if "times_activated_max" allows it.
 *
 * @param string $licenseKey The license key to be activated.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_activate_license($licenseKey)
{
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return false;
    }

    $timesActivated    = null;
    $timesActivatedMax = null;

    if ($license->getTimesActivated() !== null) {
        $timesActivated = absint($license->getTimesActivated());
    }

    if ($license->getTimesActivatedMax() !== null) {
        $timesActivatedMax = absint($license->getTimesActivatedMax());
    }

    if ($timesActivatedMax && ($timesActivated >= $timesActivatedMax)) {
        throw new Exception("License Key: {$licenseKey} reached maximum activation count.");
    }

    if (!$timesActivated) {
        $timesActivatedNew = 1;
    } else {
        $timesActivatedNew = intval($timesActivated) + 1;
    }

    /** @var LicenseResourceModel $updatedLicense */
    $updatedLicense = LicenseResourceRepository::instance()->update(
        $license->getId(),
        array(
            'times_activated' => $timesActivatedNew
        )
    );

    if (!$updatedLicense) {
        return false;
    }

    return $updatedLicense;
}

/**
 * Decrements the "times_activated" column, if possible.
 *
 * @param string $licenseKey The license key to be deactivated.
 *
 * @return bool|LicenseResourceModel
 * @throws Exception
 */
function lmfwc_deactivate_license($licenseKey)
{
    $license = LicenseResourceRepository::instance()->findBy(
        array(
            'hash' => apply_filters('lmfwc_hash', $licenseKey)
        )
    );

    if (!$license) {
        return false;
    }

    $timesActivated = null;

    if ($license->getTimesActivated() !== null) {
        $timesActivated = absint($license->getTimesActivated());
    }

    if (!$timesActivated || $timesActivated === 0) {
        throw new Exception("License Key: {$licenseKey} has not been activated yet.");
    }

    $timesActivatedNew = intval($timesActivated) - 1;

    /** @var LicenseResourceModel $updatedLicense */
    $updatedLicense = LicenseResourceRepository::instance()->update(
        $license->getId(),
        array(
            'times_activated' => $timesActivatedNew
        )
    );

    if (!$updatedLicense) {
        return false;
    }

    return $updatedLicense;
}