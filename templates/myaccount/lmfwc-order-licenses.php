<?php
/**
 * The template for the purchased license keys inside "My account".
 *
 * This template can be overridden by copying it to
 * yourtheme/woocommerce/myaccount/lmfwc-order-licenses.php.
 *
 * HOWEVER, on occasion I will need to update template files and you
 * (the developer) will need to copy the new files to your theme to
 * maintain compatibility. I try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @var WC_Order $order       The WooCommerce order object
 * @var array    $data        Customer licenses
 * @var string   $date_format Website's date format
 *
 * @version 2.3.0
 */

defined('ABSPATH') || exit; ?>

<h2><?php _e('Related licenses', 'license-manager-for-woocommerce'); ?></h2>

<?php foreach ($data as $productId => $row): ?>
    <table class="shop_table shop_table_responsive my_account_orders woocommerce-orders-table woocommerce-MyAccount-licenses woocommerce-orders-table--licenses">
        <thead>
        <tr>
            <th colspan="2"><?php echo esc_html($row['name']); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($row['keys'] as $license): ?>
            <tr>
                <td colspan="<?php echo esc_attr(($license->getExpiresAt()) ? '1' : '2'); ?>">
                    <span class="lmfwc-myaccount-license-key">
                        <?php echo esc_html($license->getDecryptedLicenseKey()); ?>
                    </span>
                </td>
                <?php if ($license->getExpiresAt()): ?>
                    <td>
                        <span class="lmfwc-myaccount-expires-at">
                        <?php
                        try {
                            printf(
                                '%s <b>%s</b>',
                                __('Valid until', 'license-manager-for-woocommerce'),
                                (new DateTime($license->getExpiresAt()))->format($date_format)
                            );
                        } catch (Exception $e) {
                        }
                        ?>
                        </span>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>

