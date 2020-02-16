<?php

namespace LicenseManagerForWooCommerce\Controllers;

use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Models\Resources\ApiKey as ApiKeyResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\ApiKey as ApiKeyResourceRepository;

defined('ABSPATH') || exit;

class ApiKey
{
    /**
     * ApiKey constructor.
     */
    public function __construct()
    {
        // Admin POST requests
        add_action('admin_post_lmfwc_api_key_update', array($this, 'apiKeyUpdate'), 10);
    }

    /**
     * Store a created API key to the database or updates an existing key.
     */
    public function apiKeyUpdate()
    {
        // Check the nonce.
        check_admin_referer('lmfwc-api-key-update');

        $error = null;

        if (empty($_POST['description'])) {
            $error = __('Description is missing.', 'license-manager-for-woocommerce');
        }

        if (empty($_POST['user']) || $_POST['user'] == -1) {
            $error = __('User is missing.', 'license-manager-for-woocommerce');
        }

        if (empty($_POST['permissions'])) {
            $error = __('Permissions are missing.', 'license-manager-for-woocommerce');
        }

        $keyId       = absint($_POST['id']);
        $description = sanitize_text_field(wp_unslash($_POST['description']));
        $permissions = 'read';
        $userId      = absint($_POST['user']);
        $action      = sanitize_text_field(wp_unslash($_POST['lmfwc_action']));

        // Set the correct permissions from the form
        if (in_array($_POST['permissions'], array('read', 'write', 'read_write'))) {
            $permissions = sanitize_text_field($_POST['permissions']);
        }

        // Check if current user can edit other users
        if ($userId && !current_user_can('edit_user', $userId)) {
            if (get_current_user_id() !== $userId) {
                $error = __('You do not have permission to assign API keys to the selected user.', 'license-manager-for-woocommerce');
            }
        }

        if ($error) {
            AdminNotice::error($error);
            wp_redirect(sprintf('admin.php?page=%s&tab=rest_api&create_key=1', AdminMenus::SETTINGS_PAGE));
            exit();
        }

        if ($action === 'create') {
            $consumerKey    = 'ck_' . lmfwc_rand_hash();
            $consumerSecret = 'cs_' . lmfwc_rand_hash();

            /** @var ApiKeyResourceModel $apiKey */
            $apiKey = ApiKeyResourceRepository::instance()->insert(
                array(
                    'user_id'         => $userId,
                    'description'     => $description,
                    'permissions'     => $permissions,
                    'consumer_key'    => wc_api_hash($consumerKey),
                    'consumer_secret' => $consumerSecret,
                    'truncated_key'   => substr($consumerKey, -7),
                )
            );

            if ($apiKey) {
                AdminNotice::success(__('API key generated successfully. Make sure to copy your new keys now as the secret key will be hidden once you leave this page.', 'license-manager-for-woocommerce'));
                set_transient('lmfwc_consumer_key', $consumerKey, 60);
                set_transient('lmfwc_api_key', $apiKey, 60);
            }

            else {
                AdminNotice::error(__('There was a problem generating the API key.', 'license-manager-for-woocommerce'));
            }

            wp_redirect(sprintf('admin.php?page=%s&tab=rest_api&show_key=1', AdminMenus::SETTINGS_PAGE));
            exit();
        }

        elseif ($action === 'edit') {
            $apiKey = ApiKeyResourceRepository::instance()->update(
                $keyId,
                array(
                    'user_id'     => $userId,
                    'description' => $description,
                    'permissions' => $permissions
                )
            );

            if ($apiKey) {
                AdminNotice::success(__('API key updated successfully.', 'license-manager-for-woocommerce'));
            }

            else {
                AdminNotice::error(__('There was a problem updating the API key.', 'license-manager-for-woocommerce'));
            }

            wp_redirect(sprintf('admin.php?page=%s&tab=rest_api', AdminMenus::SETTINGS_PAGE));
            exit();
        }
    }
}