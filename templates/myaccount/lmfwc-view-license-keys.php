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
use LicenseManagerForWooCommerce\Settings;

defined('ABSPATH') || exit; ?>

<h2><?php _e('Your license keys', 'license-manager-for-woocommerce'); ?></h2>

<?php foreach ($licenseKeys as $productId => $licenseKeyData): ?>
    <?php $product = wc_get_product($productId); ?>

    <h3 class="product-name">
        <?php if ($product): ?>
            <a href="<?php echo esc_url(get_post_permalink($productId)); ?>">
                <span><?php echo ($licenseKeyData['name']); ?></span>
            </a>
        <?php else: ?>
            <span><?php echo __('Product', 'license-manager-for-woocommerce') . ' #' . $productId; ?></span>
        <?php endif; ?>
    </h3>

    <table class="shop_table shop_table_responsive my_account_orders">
        <thead>
        <tr>
            <th class="license-key"><?php _e('License key', 'license-manager-for-woocommerce'); ?></th>
            <th class="activation"><?php _e('Activation status', 'license-manager-for-woocommerce'); ?></th>
            <th class="valid-until"><?php _e('Valid until', 'license-manager-for-woocommerce'); ?></th>
            <th class="actions"></th>
        </tr>
        </thead>

        <tbody>

        <?php
        /** @var LicenseResourceModel $license */
        foreach ($licenseKeyData['licenses'] as $license):
            $timesActivated    = $license->getTimesActivated() ? $license->getTimesActivated() : '0';
            $timesActivatedMax = $license->getTimesActivatedMax() ? $license->getTimesActivatedMax() : '&infin;';
            $order             = wc_get_order($license->getOrderId());
            ?>
            <tr>
                <td><span class="lmfwc-myaccount-license-key"><?php echo $license->getDecryptedLicenseKey(); ?></span></td>
                <td>
                    <span><?php esc_html_e($timesActivated); ?></span>
                    <span>/</span>
                    <span><?php echo $timesActivatedMax; ?></span>
                </td>
                <td><?php
                    if ($license->getExpiresAt()) {
                        $date = new \DateTime($license->getExpiresAt());
                        printf('<b>%s</b>', $date->format($dateFormat));
                    }
                    ?></td>
                <td class="license-key-actions">
                    <?php if (Settings::get('lmfwc_allow_users_to_activate')): ?>
                        <form method="post" style="display: inline-block; margin: 0;">
                            <input type="hidden" name="license" value="<?php echo $license->getDecryptedLicenseKey();?>"/>
                            <input type="hidden" name="action" value="activate">
                            <?php wp_nonce_field('lmfwc_myaccount_activate_license'); ?>
                            <button class="button" type="submit"><?php _e('Activate', 'license-manager-for-woocommerce');?></button>
                        </form>
                    <?php endif; ?>

                    <?php if (Settings::get('lmfwc_allow_users_to_deactivate')): ?>
                        <form method="post" style="display: inline-block; margin: 0;">
                            <input type="hidden" name="license" value="<?php echo $license->getDecryptedLicenseKey();?>"/>
                            <input type="hidden" name="action" value="deactivate">
                            <?php wp_nonce_field('lmfwc_myaccount_deactivate_license'); ?>
                            <button class="button" type="submit"><?php _e('Deactivate', 'license-manager-for-woocommerce');?></button>
                        </form>
                    <?php endif; ?>
                        <form method="get" style="display: inline-block; margin: 0;" action="<?php echo esc_url($order->get_view_order_url()); ?>">
                            <button class="button" type="submit"><?php _e('Order', 'license-manager-for-woocommerce');?></button>
                        </form>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
<?php endforeach; ?>
