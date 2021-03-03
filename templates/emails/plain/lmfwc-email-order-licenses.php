<?php
/**
 * Template part which adds the license keys to the "Completed order" and
 * "Deliver license keys" email (plain text).
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

defined('ABSPATH') || exit;

echo __('Licenses', 'license-manager-for-woocommerce');
echo "\n\n";

foreach ($data as $i => $row) {
    $count = count($row['keys']);
    echo esc_html($row['name']) . "\n";
    echo "----------\n";

    foreach ($row['keys'] as $i => $license) {
        echo esc_html($license->getDecryptedLicenseKey());

        if ($license->getExpiresAt()) {
            try {
                $date = new DateTime($license->getExpiresAt());
                printf(
                    ', %s <b>%s</b>',
                    __('Valid until', 'license-manager-for-woocommerce'),
                    $date->format($date_format)
                );
            } catch (Exception $e) {
            }
        }

        echo "\n";

        if ($i === ($count - 1)) {
            echo "\n";
        }
    }
}