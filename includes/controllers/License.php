<?php

namespace LicenseManagerForWooCommerce\Controllers;

use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Enums\LicenseSource;
use LicenseManagerForWooCommerce\Enums\LicenseStatus as LicenseStatusEnum;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;

defined('ABSPATH') || exit;

class License
{
    /**
     * License constructor.
     */
    public function __construct()
    {
        // Admin POST requests
        add_action('admin_post_lmfwc_import_license_keys',   array($this, 'importLicenseKeys'),   10);
        add_action('admin_post_lmfwc_add_license_key',       array($this, 'addLicenseKey'),       10);
        add_action('admin_post_lmfwc_update_license_key',    array($this, 'updateLicenseKey'),    10);

        // AJAX calls
        add_action('wp_ajax_lmfwc_show_license_key',      array($this, 'showLicenseKey'),     10);
        add_action('wp_ajax_lmfwc_show_all_license_keys', array($this, 'showAllLicenseKeys'), 10);
    }

    /**
     * Import licenses from a compatible CSV or TXT file into the database.
     */
    public function importLicenseKeys()
    {
        // Check the nonce.
        check_admin_referer('lmfwc_import_license_keys');

        $orderId     = null;
        $productId   = null;
        $userId      = null;
        $status      = LicenseStatusEnum::ACTIVE;
        $source      = $_POST['source'];
        $licenseKeys = array();

        if (array_key_exists('order_id', $_POST) && $_POST['order_id']) {
            $orderId = $_POST['order_id'];
        }

        if (array_key_exists('product_id', $_POST) && $_POST['product_id']) {
            $productId = $_POST['product_id'];
        }

        if (array_key_exists('user_id', $_POST) && $_POST['user_id']) {
            $userId = $_POST['user_id'];
        }

        if (array_key_exists('status', $_POST)
            && $_POST['status']
            && in_array($_POST['status'], LicenseStatusEnum::$status)
        ) {
            $status = (int)$_POST['status'];
        }

        if ($source === 'file') {
            $licenseKeys = apply_filters('lmfwc_import_license_keys_file', null);
        }

        elseif ($source === 'clipboard') {
            $licenseKeys = apply_filters('lmfwc_import_license_keys_clipboard', $_POST['clipboard']);
        }

        if (!is_array($licenseKeys) || count($licenseKeys) === 0) {
            AdminNotice::error(__('There was a problem importing the license keys.', 'license-manager-for-woocommerce'));
            wp_redirect(sprintf('admin.php?page=%s&action=import', AdminMenus::LICENSES_PAGE));
            exit();
        }

        // Save the imported keys
        try {
            $result = apply_filters(
                'lmfwc_insert_imported_license_keys',
                $licenseKeys,
                $status,
                $orderId,
                $productId,
                $userId,
                $_POST['valid_for'],
                $_POST['times_activated_max']
            );
        } catch (Exception $e) {
            AdminNotice::error(__($e->getMessage(), 'license-manager-for-woocommerce'));
            wp_redirect(sprintf('admin.php?page=%s&action=import', AdminMenus::LICENSES_PAGE));
            exit();
        }

        // Redirect according to $result.
        if ($result['failed'] == 0 && $result['added'] == 0) {
            AdminNotice::error(__('There was a problem importing the license keys.', 'license-manager-for-woocommerce'));
            wp_redirect(sprintf('admin.php?page=%s&action=import', AdminMenus::LICENSES_PAGE));
            exit();
        }

        if ($result['failed'] == 0 && $result['added'] > 0) {
            // Update the stock
            if ($status === LicenseStatusEnum::ACTIVE) {
                lmfwc_stock_increase($productId, $result['added']);
            }

            // Display a success message
            AdminNotice::success(
                sprintf(
                    __('%d license key(s) added successfully.', 'license-manager-for-woocommerce'),
                    (int)$result['added']
                )
            );
            wp_redirect(sprintf('admin.php?page=%s&action=import', AdminMenus::LICENSES_PAGE));
            exit();
        }

        if ($result['failed'] > 0 && $result['added'] == 0) {
            AdminNotice::error(__('There was a problem importing the license keys.', 'license-manager-for-woocommerce'));
            wp_redirect(sprintf('admin.php?page=%s&action=import', AdminMenus::LICENSES_PAGE));
            exit();
        }

        if ($result['failed'] > 0 && $result['added'] > 0) {
            // Update the stock
            if ($status === LicenseStatusEnum::ACTIVE) {
                lmfwc_stock_increase($productId, $result['added']);
            }

            // Display a warning message
            AdminNotice::warning(
                sprintf(
                    __('%d key(s) have been imported, while %d key(s) were not imported.', 'license-manager-for-woocommerce'),
                    (int)$result['added'],
                    (int)$result['failed']
                )
            );
            wp_redirect(sprintf('admin.php?page=%s&action=import', AdminMenus::LICENSES_PAGE));
            exit();
        }
    }

    /**
     * Add a single license key to the database.
     */
    public function addLicenseKey()
    {
        // Check the nonce
        check_admin_referer('lmfwc_add_license_key');

        $status            = absint($_POST['status']);
        $orderId           = null;
        $productId         = null;
        $userId            = null;
        $validFor          = null;
        $expiresAt         = null;
        $timesActivatedMax = null;

        if (array_key_exists('order_id', $_POST) && $_POST['order_id']) {
            $orderId = $_POST['order_id'];
        }

        if (array_key_exists('product_id', $_POST) && $_POST['product_id']) {
            $productId = $_POST['product_id'];
        }

        if (array_key_exists('user_id', $_POST) && $_POST['user_id']) {
            $userId = $_POST['user_id'];
        }

        if (array_key_exists('valid_for', $_POST) && $_POST['valid_for']) {
            $validFor  = $_POST['valid_for'];
            $expiresAt = null;
        }

        if (array_key_exists('expires_at', $_POST) && $_POST['expires_at']) {
            $validFor  = null;
            $expiresAt = $_POST['expires_at'];
        }

        if (array_key_exists('times_activated_max', $_POST) && $_POST['times_activated_max']) {
            $timesActivatedMax = absint($_POST['times_activated_max']);
        }

        if (apply_filters('lmfwc_duplicate', $_POST['license_key'])) {
            AdminNotice::error(__('The license key already exists.', 'license-manager-for-woocommerce'));
            wp_redirect(sprintf('admin.php?page=%s&action=add', AdminMenus::LICENSES_PAGE));
            exit;
        }

        /** @var LicenseResourceModel $license */
        $license = LicenseResourceRepository::instance()->insert(
            array(
                'order_id'            => $orderId,
                'product_id'          => $productId,
                'user_id'             => $userId,
                'license_key'         => apply_filters('lmfwc_encrypt', $_POST['license_key']),
                'hash'                => apply_filters('lmfwc_hash', $_POST['license_key']),
                'expires_at'          => $expiresAt,
                'valid_for'           => $validFor,
                'source'              => LicenseSource::IMPORT,
                'status'              => $status,
                'times_activated_max' => $timesActivatedMax
            )
        );

        // Redirect with message
        if ($license) {
            if (!$expiresAt && $validFor) {
                $expiresAt = lmfwc_convert_valid_for_to_expires_at($validFor);
            }

            lmfwc_update_order_downloads_expiration($expiresAt, $orderId);

            AdminNotice::success(__('1 license key(s) added successfully.', 'license-manager-for-woocommerce'));

            // Update the stock
            if ($license->getStatus() == LicenseStatusEnum::ACTIVE) {
                lmfwc_stock_increase($productId);
            }
        }

        else {
            AdminNotice::error(__('There was a problem adding the license key.', 'license-manager-for-woocommerce'));
        }

        wp_redirect(sprintf('admin.php?page=%s&action=add', AdminMenus::LICENSES_PAGE));
        exit();
    }

    /**
     * Updates an existing license keys.
     *
     * @throws Exception
     */
    public function updateLicenseKey()
    {
        // Check the nonce
        check_admin_referer('lmfwc_update_license_key');

        $licenseId         = absint($_POST['license_id']);
        $status            = absint($_POST['status']);
        $orderId           = null;
        $productId         = null;
        $userId            = null;
        $validFor          = null;
        $expiresAt         = null;
        $timesActivatedMax = null;

        /** @var LicenseResourceModel $oldLicense */
        $oldLicense = LicenseResourceRepository::instance()->find($licenseId);

        if (array_key_exists('order_id', $_POST) && $_POST['order_id']) {
            $orderId = $_POST['order_id'];
        }

        if (array_key_exists('product_id', $_POST) && $_POST['product_id']) {
            $productId = $_POST['product_id'];
        }

        if (array_key_exists('user_id', $_POST) && $_POST['user_id']) {
            $userId = $_POST['user_id'];
        }

        if (array_key_exists('valid_for', $_POST) && $_POST['valid_for']) {
            $validFor  = $_POST['valid_for'];
            $expiresAt = null;
        }

        if (array_key_exists('expires_at', $_POST) && $_POST['expires_at']) {
            $validFor  = null;
            $expiresAt = $_POST['expires_at'];
        }

        if (array_key_exists('times_activated_max', $_POST) && $_POST['times_activated_max']) {
            $timesActivatedMax = absint($_POST['times_activated_max']);
        }

        // Check for duplicates
        if (apply_filters('lmfwc_duplicate', $_POST['license_key'], $licenseId)) {
            AdminNotice::error(__('The license key already exists.', 'license-manager-for-woocommerce'));
            wp_redirect(sprintf('admin.php?page=%s&action=edit&id=%d', AdminMenus::LICENSES_PAGE, $licenseId));
            exit;
        }

        // Update the stock
        if ($oldLicense->getProductId() !== null && $oldLicense->getStatus() === LicenseStatusEnum::ACTIVE) {
            lmfwc_stock_decrease($oldLicense->getProductId());
        }

        /** @var LicenseResourceModel $license */
        $license = LicenseResourceRepository::instance()->update(
            $licenseId,
            array(
                'order_id'            => $orderId,
                'product_id'          => $productId,
                'user_id'             => $userId,
                'license_key'         => apply_filters('lmfwc_encrypt', $_POST['license_key']),
                'hash'                => apply_filters('lmfwc_hash', $_POST['license_key']),
                'expires_at'          => $expiresAt,
                'valid_for'           => $validFor,
                'source'              => $_POST['source'],
                'status'              => $status,
                'times_activated_max' => $timesActivatedMax
            )
        );

        if ($license) {
            // Update the stock
            if ($license->getProductId() !== null && $license->getStatus() === LicenseStatusEnum::ACTIVE) {
                lmfwc_stock_increase($license->getProductId());
            }

            if ( ! $expiresAt && $validFor ) {
                $expiresAt = lmfwc_convert_valid_for_to_expires_at($validFor);
            }

            lmfwc_update_order_downloads_expiration( $expiresAt, $orderId );

            // Display a success message
            AdminNotice::success(__('Your license key has been updated successfully.', 'license-manager-for-woocommerce'));
        }

        else {
            //Display an error message
            AdminNotice::error(__('There was a problem updating the license key.', 'license-manager-for-woocommerce'));
        }

        wp_redirect(sprintf('admin.php?page=%s&action=edit&id=%d', AdminMenus::LICENSES_PAGE, $licenseId));
        exit();
    }

    /**
     * Show a single license key.
     */
    public function showLicenseKey()
    {
        // Validate request.
        check_ajax_referer('lmfwc_show_license_key', 'show');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die(__('Invalid request.', 'license-manager-for-woocommerce'));
        }

        /** @var LicenseResourceModel $license */
        $license = LicenseResourceRepository::instance()->findBy(array('id' => $_POST['id']));

        wp_send_json($license->getDecryptedLicenseKey());

        wp_die();
    }

    /**
     * Shows all visible license keys.
     */
    public function showAllLicenseKeys()
    {
        // Validate request.
        check_ajax_referer('lmfwc_show_all_license_keys', 'show_all');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            wp_die(__('Invalid request.', 'license-manager-for-woocommerce'));
        }

        $licenseKeysIds = array();

        foreach (json_decode($_POST['ids']) as $licenseKeyId) {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->find($licenseKeyId);

            $licenseKeysIds[$licenseKeyId] = $license->getDecryptedLicenseKey();
        }

        wp_send_json($licenseKeysIds);
    }
}
