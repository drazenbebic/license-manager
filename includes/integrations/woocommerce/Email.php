<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce;

use LicenseManagerForWooCommerce\Integrations\WooCommerce\Emails\CustomerDeliverLicenseKeys;
use LicenseManagerForWooCommerce\Integrations\WooCommerce\Emails\Templates;
use LicenseManagerForWooCommerce\Settings;
use WC_Email;
use WC_Order;

defined('ABSPATH') || exit;

class Email
{
    /**
     * OrderManager constructor.
     */
    public function __construct() {
        add_action('woocommerce_email_after_order_table', array($this, 'afterOrderTable'), 10, 4);
        add_action('woocommerce_email_classes',           array($this, 'registerClasses'), 90, 1);
    }

    /**
     * Adds the bought license keys to the "Order complete" email, or displays a notice - depending on the settings.
     *
     * @param WC_Order $order
     * @param bool     $isAdminEmail
     * @param bool     $plainText
     * @param WC_Email $email
     */
    public function afterOrderTable($order, $isAdminEmail, $plainText, $email)
    {
        // Return if the order isn't complete.
        if ($order->get_status() !== 'completed' && !lmfwc_is_order_complete($order->get_id())) {
            return;
        }

        $args = array(
            'order' => $order,
            'data'  => null
        );

        $customerLicenseKeys = apply_filters('lmfwc_get_customer_license_keys', $args);

        if (!$customerLicenseKeys['data']) {
            return;
        }

        if (Settings::get('lmfwc_auto_delivery')) {
            // Send the keys out if the setting is active.
            if ($plainText) {
                echo wc_get_template(
                    'emails/plain/lmfwc-email-order-licenses.php',
                    array(
                        'order'         => $order,
                        'sent_to_admin' => $isAdminEmail,
                        'plain_text'    => true,
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
                        'order'         => $order,
                        'sent_to_admin' => $isAdminEmail,
                        'plain_text'    => false,
                        'email'         => $email,
                        'data'          => $customerLicenseKeys['data'],
                        'date_format'   => get_option('date_format')
                    ),
                    '',
                    LMFWC_TEMPLATES_DIR
                );
            }
        }

        else {
            // Only display a notice.
            if ($plainText) {
                echo wc_get_template(
                    'emails/plain/lmfwc-email-order-license-notice.php',
                    array(
                        'order'         => $order,
                        'sent_to_admin' => $isAdminEmail,
                        'plain_text'    => false,
                        'email'         => $email,
                        'data'          => $customerLicenseKeys['data'],
                        'date_format'   => get_option('date_format')
                    ),
                    '',
                    LMFWC_TEMPLATES_DIR
                );
            }

            else {
                echo wc_get_template_html(
                    'emails/lmfwc-email-order-license-notice.php',
                    array(
                        'order'         => $order,
                        'sent_to_admin' => $isAdminEmail,
                        'plain_text'    => false,
                        'email'         => $email,
                        'data'          => $customerLicenseKeys['data'],
                        'date_format'   => get_option('date_format')
                    ),
                    '',
                    LMFWC_TEMPLATES_DIR
                );
            }
        }
    }

    /**
     * Registers the plugin email classes to work with WooCommerce.
     *
     * @param array $emails
     *
     * @return array
     */
    public function registerClasses($emails)
    {
        new Templates();

        $pluginEmails = array(
            'LMFWC_Customer_Deliver_License_Keys' => new CustomerDeliverLicenseKeys()
        );

        return array_merge($emails, $pluginEmails);
    }
}