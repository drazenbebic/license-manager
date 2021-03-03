<?php
/**
 * Customer license delivery email template. The email is triggered by using
 * the order action "Send license key(s) to customer" from within an order
 * (plain text).
 *
 * This template can be overridden by copying it to
 * yourtheme/woocommerce/emails/plain/lmfwc-email-customer-deliver-license-keys.php.
 *
 * HOWEVER, on occasion I will need to update template files and you
 * (the developer) will need to copy the new files to your theme to
 * maintain compatibility. I try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @var string   $email_heading The email's heading, as defined in the settings
 * @var WC_Order $order         The WooCommerce order object
 * @var bool     $sent_to_admin True if email was sent to admin as well
 * @var bool     $plain_text    True if email is plain text
 * @var WC_Email $email         The WooCommerce email object
 *
 * @version 2.3.0
 */

defined('ABSPATH') || exit;

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);

/**
 * Adds the ordered license keys table.
 */
do_action('lmfwc_email_order_licenses', $order, $sent_to_admin, $plain_text, $email);

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($email->settings['additional_content']) {
    echo wp_kses_post(wpautop(wptexturize($email->settings['additional_content'])));
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);