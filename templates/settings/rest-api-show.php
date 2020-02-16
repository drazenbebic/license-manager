<?php

use LicenseManagerForWooCommerce\Models\Resources\ApiKey as ApiKeyResourceModel;

defined('ABSPATH') || exit;

/** @var ApiKeyResourceModel $keyData */

?>

<h2><?php esc_html_e('Key details', 'license-manager-for-woocommerce');?></h2>
<hr class="wp-header-end">

<?php if ($keyData): ?>

    <form method="post" action="<?=admin_url('admin-post.php');?>">
        <?php wp_nonce_field('lmfwc-api-key-update'); ?>

        <table class="form-table">
            <tbody>
                <tr scope="row">
                    <th scope="row">
                        <label for="consumer_key"><?php esc_html_e('Consumer key', 'license-manager-for-woocommerce');?></label>
                    </th>
                    <td>
                        <input
                            id="consumer_key"
                            class="regular-text"
                            name="consumer_key"
                            type="text"
                            value="<?php echo esc_attr($consumerKey); ?>"
                            readonly="readonly"
                        >
                    </td>
                </tr>
                <tr scope="row">
                    <th scope="row">
                        <label for="consumer_secret"><?php esc_html_e('Consumer secret', 'license-manager-for-woocommerce');?></label>
                    </th>
                    <td>
                        <input
                            id="consumer_secret"
                            class="regular-text"
                            name="consumer_secret"
                            type="text"
                            value="<?php echo esc_attr($keyData->getConsumerSecret()); ?>"
                            readonly="readonly"
                        >
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <a
                style="color: #a00; text-decoration: none; margin-left: 10px;"
                href="<?php echo esc_url(
                    wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'revoke',
                                'key' => $keyData->getId()
                            ),
                            sprintf(admin_url('admin.php?page=%s&tab=rest_api'), \LicenseManagerForWooCommerce\AdminMenus::SETTINGS_PAGE)
                        ),
                        'revoke'
                    )
                );?>"
            >
                <span><?php esc_html_e('Revoke key', 'license-manager-for-woocommerce'); ?></span>
            </a>
        </p>
    </form>

<?php else: ?>

    <div><?php esc_html_e('Nothing to see here...', 'license-manager-for-woocommerce'); ?></div>

<?php endif; ?>