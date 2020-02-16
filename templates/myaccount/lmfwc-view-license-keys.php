<?php
/**
 * The template for the overview of all customer license keys, across all orders, inside "My Account"
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/lmfwc-view-license-keys.php.
 *
 * HOWEVER, on occasion I will need to update template files and you
 * (the developer) will need to copy the new files to your theme to
 * maintain compatibility. I try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 2.1.0
 *
 * Default variables
 *
 * @var $licenseKeys array
 * @var $page        int
 * @var $dateFormat  string
 */

use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;

defined('ABSPATH') || exit; ?>

<h2><?php _e('Your license keys', 'license-manager-for-woocommerce'); ?></h2>

<?php foreach ($licenseKeys as $productId => $licenseKeyData): ?>
    <h3 class="product-name">
        <a href="<?php echo esc_url(get_post_permalink($productId)); ?>">
            <span><?php echo ($licenseKeyData['name']); ?></span>
        </a>
    </h3>

    <table class="shop_table shop_table_responsive my_account_orders">
        <thead>
        <tr>
            <th class="license-key"><?php esc_html_e('License key', 'license-manager-for-woocommerce'); ?></th>
            <th class="valid-until"><?php esc_html_e('Valid until', 'license-manager-for-woocommerce'); ?></th>
            <th class="actions"></th>
        </tr>
        </thead>

        <tbody>

        <?php
            /** @var LicenseResourceModel $license */
            foreach ($licenseKeyData['licenses'] as $license):
                $order = wc_get_order($license->getOrderId());
        ?>
            <tr>
                <td><span class="lmfwc-myaccount-license-key"><?php echo $license->getDecryptedLicenseKey(); ?></span></td>
                <td><?php
                    if ($license->getExpiresAt()) {
                        $date = new \DateTime($license->getExpiresAt());
                        printf('<b>%s</b>', $date->format($dateFormat));
                    }
                ?></td>
                <td class="license-key-actions">
                    <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="button view"><?php esc_html_e('View', 'license-manager-for-woocommerce');?></a>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
<?php endforeach; ?>
