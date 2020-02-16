<?php

namespace LicenseManagerForWooCommerce\Controllers;

use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\Generator as GeneratorResourceRepository;

defined('ABSPATH') || exit;

class Generator
{
    /**
     * Generator constructor.
     */
    public function __construct()
    {
        // Admin POST requests
        add_action('admin_post_lmfwc_save_generator',        array($this, 'saveGenerator'),       10);
        add_action('admin_post_lmfwc_update_generator',      array($this, 'updateGenerator'),     10);
        add_action('admin_post_lmfwc_generate_license_keys', array($this, 'generateLicenseKeys'), 10);
    }

    /**
     * Save the generator to the database.
     */
    public function saveGenerator()
    {
        // Verify the nonce.
        check_admin_referer('lmfwc_save_generator');

        // Validate request.
        if ($_POST['name'] == '' || !is_string($_POST['name'])) {
            AdminNotice::error(__('Generator name is missing.', 'license-manager-for-woocommerce'));
            wp_redirect(admin_url(sprintf('admin.php?page=%s&action=add', AdminMenus::GENERATORS_PAGE)));
            exit();
        }

        if ($_POST['charset'] == '' || !is_string($_POST['charset'])) {
            AdminNotice::error(__('The charset is invalid.', 'license-manager-for-woocommerce'));
            wp_redirect(admin_url(sprintf('admin.php?page=%s&action=add', AdminMenus::GENERATORS_PAGE)));
            exit();
        }

        if ($_POST['chunks'] == '' || !is_numeric($_POST['chunks'])) {
            AdminNotice::error(__('Only integer values allowed for chunks.', 'license-manager-for-woocommerce'));
            wp_redirect(admin_url(sprintf('admin.php?page=%s&action=add', AdminMenus::GENERATORS_PAGE)));
            exit();
        }

        if ($_POST['chunk_length'] == '' || !is_numeric($_POST['chunk_length'])) {
            AdminNotice::error(__('Only integer values allowed for chunk length.', 'license-manager-for-woocommerce'));
            wp_redirect(admin_url(sprintf('admin.php?page=%s&action=add', AdminMenus::GENERATORS_PAGE)));
            exit();
        }

        // Save the generator.
        $generator = GeneratorResourceRepository::instance()->insert(
            array(
                'name'                => $_POST['name'],
                'charset'             => $_POST['charset'],
                'chunks'              => $_POST['chunks'],
                'chunk_length'        => $_POST['chunk_length'],
                'times_activated_max' => $_POST['times_activated_max'],
                'separator'           => $_POST['separator'],
                'prefix'              => $_POST['prefix'],
                'suffix'              => $_POST['suffix'],
                'expires_in'          => $_POST['expires_in']
            )
        );

        if ($generator) {
            AdminNotice::success(__('The generator was added successfully.', 'license-manager-for-woocommerce'));
        }

        else {
            AdminNotice::error(__('There was a problem adding the generator.', 'license-manager-for-woocommerce'));
        }

        wp_redirect(admin_url(sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE)));
        exit();
    }

    /**
     * Update an existing generator.
     */
    public function updateGenerator()
    {
        // Verify the nonce.
        check_admin_referer('lmfwc_update_generator');

        $generatorId = absint($_POST['id']);

        // Validate request.
        if ($_POST['name'] == '' || !is_string($_POST['name'])) {
            AdminNotice::error(__('The Generator name is invalid.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        if ($_POST['charset'] == '' || !is_string($_POST['charset'])) {
            AdminNotice::error(__('The Generator charset is invalid.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        if ($_POST['chunks'] == '' || !is_numeric($_POST['chunks'])) {
            AdminNotice::error(__('The Generator chunks are invalid.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        if ($_POST['chunk_length'] == '' || !is_numeric($_POST['chunk_length'])) {
            AdminNotice::error(__('The Generator chunk length is invalid.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        // Update the generator.
        $generator = GeneratorResourceRepository::instance()->update(
            $_POST['id'],
            array(
                'name'                => $_POST['name'],
                'charset'             => $_POST['charset'],
                'chunks'              => $_POST['chunks'],
                'chunk_length'        => $_POST['chunk_length'],
                'times_activated_max' => $_POST['times_activated_max'],
                'separator'           => $_POST['separator'],
                'prefix'              => $_POST['prefix'],
                'suffix'              => $_POST['suffix'],
                'expires_in'          => $_POST['expires_in']
            )
        );

        // Redirect according to $result.
        if (!$generator) {
            AdminNotice::error(__('There was a problem updating the generator.', 'license-manager-for-woocommerce'));
        }

        else {
            AdminNotice::success(__('The Generator was updated successfully.', 'license-manager-for-woocommerce'));
        }

        wp_redirect(admin_url(sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE)));
        exit();
    }

    /**
     * Generates a chosen amount of license keys using the selected generator.
     */
    public function generateLicenseKeys()
    {
        // Verify the nonce.
        check_admin_referer('lmfwc_generate_license_keys');

        $generatorId = absint($_POST['generator_id']);
        $amount      = absint($_POST['amount']);
        $status      = absint($_POST['status']);
        $orderId     = null;
        $productId   = null;

        /** @var GeneratorResourceModel $generator */
        $generator = GeneratorResourceRepository::instance()->find($generatorId);

        if (array_key_exists('order_id', $_POST) && $_POST['order_id']) {
            $orderId = absint($_POST['order_id']);
        }

        if (array_key_exists('product_id', $_POST) && $_POST['product_id']) {
            $productId = absint($_POST['product_id']);
        }

        // Validate request.
        if (!$generator) {
            AdminNotice::error(__('The chosen generator does not exist.', 'license-manager-for-woocommerce'));

            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        if ($orderId && !wc_get_order($orderId)) {
            AdminNotice::error(__('The chosen order does not exist.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        if ($productId && !wc_get_product($productId)) {
            AdminNotice::error(__('The chosen product does not exist.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::GENERATORS_PAGE,
                        $generatorId
                    )
                )
            );
            exit();
        }

        $licenses = apply_filters('lmfwc_generate_license_keys', $amount, $generator);

        // Save the license keys.
        apply_filters(
            'lmfwc_insert_generated_license_keys',
            $orderId,
            $productId,
            $licenses,
            $status,
            $generator
        );

        // Show message and redirect.
        AdminNotice::success(sprintf(__('Successfully generated %d license key(s).', 'license-manager-for-woocommerce'), $amount));
        wp_redirect(admin_url(sprintf('admin.php?page=%s&action=generate', AdminMenus::GENERATORS_PAGE)));
        exit();
    }
}