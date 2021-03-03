<?php
/**
 * The template for the overview of all customer license keys, across all
 * orders, inside "My Account"
 *
 * This template can be overridden by copying it to
 * yourtheme/woocommerce/myaccount/lmfwc-license.php.
 *
 * HOWEVER, on occasion I will need to update template files and you
 * (the developer) will need to copy the new files to your theme to
 * maintain compatibility. I try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @var $date_format       string The website's default date format.
 * @var $customer_licenses array  Array containing the customer's licenses
 * @var $page              int    Current page
 * @var $can_activate      bool   "User activation" setting value
 * @var $can_deactivate    bool   "User deactivation" setting value
 *
 * @version 2.3.0
 */

use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;

defined('ABSPATH') || exit; ?>

<div class="woocommerce_account_licenses">

    <?php foreach ($customer_licenses as $customer_license): ?>
        <?php $order = $customer_license['order']; ?>
        <?php $order_id = $customer_license['orderId']; ?>
        <?php $licenses = $customer_license['licenses']; ?>

        <h3 class="lmfwc-myaccount-order">
            <?php if ($order): ?>
                <a href="<?php echo esc_url(get_edit_post_link($order_id)); ?>">
                    <span><?php _e('Order', 'license-manager-for-woocommerce'); ?></span>
                    <span>#<?php echo esc_html($order_id); ?></span>
                </a>
            <?php else: ?>
                <span><?php echo __('Order', 'license-manager-for-woocommerce') . ' #' . $order; ?></span>
            <?php endif; ?>
        </h3>

        <table class="my_account_licenses my_account_orders woocommerce-orders-table woocommerce-MyAccount-licenses shop_table shop_table_responsive woocommerce-orders-table--licenses">
            <thead>
            <tr>
                <th class="license-key"><?php _e('License key', 'license-manager-for-woocommerce'); ?></th>
                <th class="license-key"><?php _e('Product', 'license-manager-for-woocommerce'); ?></th>
                <th class="activation"><?php _e('Activation status', 'license-manager-for-woocommerce'); ?></th>
                <th class="valid-until"><?php _e('Valid until', 'license-manager-for-woocommerce'); ?></th>
                <?php if ($can_activate || $can_deactivate): ?>
                    <th class="actions"></th>
                <?php endif; ?>
            </tr>
            </thead>

            <tbody>

            <?php
            /** @var LicenseResourceModel $license */
            foreach ($licenses as $license):
                $times_activated     = $license->getTimesActivated() ? $license->getTimesActivated() : '0';
                $times_activated_max = $license->getTimesActivatedMax() ? $license->getTimesActivatedMax() : '&infin;';
                $product             = wc_get_product($license->getProductId());
                ?>
                <tr>
                    <td class="lmfwc-myaccount-license-key"
                        data-title="<?php esc_attr_e('License key', 'license-manager-for-woocommerce'); ?>">
                        <span><?php echo $license->getDecryptedLicenseKey(); ?></span>
                    </td>
                    <td class="lmfwc-myaccount-product-name"
                        data-title="<?php esc_attr_e('Product', 'license-manager-for-woocommerce'); ?>">
                        <?php if ($product): ?>
                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                                <span><?php echo $product->get_name(); ?></span>
                            </a>
                        <?php else: ?>
                            <span><?php _e('Unknown product', 'license-manager-for-woocommerce'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="lmfwc-myaccount-activation-status"
                        data-title="<?php esc_attr_e('Activation status', 'license-manager-for-woocommerce'); ?>">
                        <span><?php echo esc_html($times_activated); ?></span>
                        <span>/</span>
                        <span><?php echo esc_html($times_activated_max); ?></span>
                    </td>
                    <td class="lmfwc-myaccount-valid-until"
                        data-title="<?php esc_attr_e('Valid until', 'license-manager-for-woocommerce'); ?>">
                        <?php
                        if ($license->getExpiresAt()) {
                            try {
                                printf('<b>%s</b>', (new DateTime($license->getExpiresAt()))->format($date_format));
                            } catch (Exception $e) {
                                echo esc_html($license->getExpiresAt());
                            }
                        }
                        ?>
                    </td>
                    <?php if ($can_activate || $can_deactivate): ?>
                        <td class="lmfwc-myaccount-actions">
                            <?php if ($can_activate): ?>
                                <form method="post">
                                    <input type="hidden"
                                           name="license"
                                           value="<?php echo esc_attr($license->getDecryptedLicenseKey());?>"/>
                                    <input type="hidden" name="action" value="activate">
                                    <?php wp_nonce_field('lmfwc_myaccount_activate_license'); ?>
                                    <button class="button" type="submit">
                                        <?php _e('Activate', 'license-manager-for-woocommerce');?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($can_deactivate): ?>
                                <form method="post">
                                    <input type="hidden"
                                           name="license"
                                           value="<?php echo esc_attr($license->getDecryptedLicenseKey());?>"/>
                                    <input type="hidden" name="action" value="deactivate">
                                    <?php wp_nonce_field('lmfwc_myaccount_deactivate_license'); ?>
                                    <button class="button" type="submit">
                                        <?php _e('Deactivate', 'license-manager-for-woocommerce');?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    <?php endforeach; ?>

</div>