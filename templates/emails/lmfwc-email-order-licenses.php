<?php
/**
 * Template part which adds the license keys to the "Completed order" and
 * "Deliver license keys" email (HTML).
 *
 * This template can be overridden by copying it to
 * yourtheme/woocommerce/emails/plain/lmfwc-email-order-licenses.php
 *
 * HOWEVER, on occasion I will need to update template files and you
 * (the developer) will need to copy the new files to your theme to
 * maintain compatibility. I try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @var WC_Order $order         The WooCommerce order object
 * @var bool     $sent_to_admin True if email was sent to admin as well
 * @var bool     $plain_text    True if email is plain text
 * @var WC_Email $email         The WooCommerce email object
 * @var array    $data          Customer licenses
 * @var string   $date_format   Website's date format
 *
 * @version 2.3.0
 */

defined('ABSPATH') || exit; ?>

<h2><?php _e('Licenses', 'license-manager-for-woocommerce'); ?></h2>

<div style="margin-bottom: 40px;">
    <?php foreach ($data as $row): ?>
        <table class="td" cellspacing="0" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
            <thead>
            <tr>
                <th class="td" scope="col" style="text-align: left;" colspan="2">
                    <span><?php echo esc_html($row['name']); ?></span>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($row['keys'] as $license): ?>
                <tr>
                    <td class="td" style="text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" colspan="<?php echo ($license->getExpiresAt()) ? '1' : '2'; ?>">
                        <code><?php echo esc_html($license->getDecryptedLicenseKey()); ?></code>
                    </td>

                    <?php if ($license->getExpiresAt()): ?>
                        <td class="td" style="text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                            <?php
                            try {
                                $date = new DateTime($license->getExpiresAt());
                                printf(
                                    '%s <b>%s</b>',
                                    __('Valid until', 'license-manager-for-woocommerce'),
                                    $date->format($date_format)
                                );
                            } catch (Exception $e) {
                            }
                            ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>
