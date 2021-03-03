<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce\Emails;

use WC_Email;
use WC_Order;

defined('ABSPATH') || exit;

class Templates
{
    /**
     * Templates constructor.
     */
    function __construct()
    {
        add_action('lmfwc_email_order_licenses', array($this, 'orderLicenses'), 10, 4);
    }

    /**
     * Adds basic order info to the email body.
     *
     * @param WC_Order $order       WooCommerce Order
     * @param bool     $sentToAdmin Determines if the email is sent to the admin
     * @param bool     $plainText   Determines if a plain text or HTML email will be sent
     * @param WC_Email $email       WooCommerce Email
     */
    public function orderLicenses($order, $sentToAdmin, $plainText, $email)
    {
        $args = array(
            'order' => $order,
            'data'  => null
        );

        $customerLicenseKeys = apply_filters('lmfwc_get_customer_license_keys', $args);

        if ($plainText) {
            echo wc_get_template(
                'emails/plain/lmfwc-email-order-licenses.php',
                array(
                    'order'         => $order,
                    'sent_to_admin' => false,
                    'plain_text'    => false,
                    'email'         => $email,
                    'data'          => $customerLicenseKeys['data'],
                    'date_format'   => get_option('date_format'),
                ),
                '',
                LMFWC_TEMPLATES_DIR
            );
        }

        else {
            echo wc_get_template_html(
                'emails/lmfwc-email-order-licenses.php',
                array(
                    'data'          => $customerLicenseKeys['data'],
                    'date_format'   => get_option('date_format'),
                    'order'         => $order,
                    'sent_to_admin' => false,
                    'plain_text'    => false,
                    'email'         => $email
                ),
                '',
                LMFWC_TEMPLATES_DIR
            );
        }
    }
}