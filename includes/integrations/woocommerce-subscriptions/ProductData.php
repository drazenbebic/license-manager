<?php


namespace LicenseManagerForWooCommerce\Integrations\WooCommerceSubscriptions;

use WC_Subscriptions_Product;
use WP_Post;

defined('ABSPATH') || exit;

class ProductData
{
    /**
     * ProductData constructor.
     */
    public function __construct()
    {
        add_action('lmfwc_simple_product_data_panel',   array($this, 'simpleProductDataPanel'),   10, 1);
        add_action('lmfwc_simple_product_save',         array($this, 'simpleProductSave'),        10, 1);
        add_action('lmfwc_variable_product_data_panel', array($this, 'variableProductDataPanel'), 10, 3);
        add_action('lmfwc_variable_product_save',       array($this, 'variableProductSave'),      10, 2);
    }

    /**
     * Adds the integration-specific fields to the plugin data panel.
     *
     * @param WP_Post $post
     */
    public function simpleProductDataPanel($post)
    {
        if (!WC_Subscriptions_Product::is_subscription($post->ID)) {
            return;
        }

        $renewalAction       = get_post_meta($post->ID, 'lmfwc_subscription_renewal_action', true);
        $renewalIntervalType = get_post_meta($post->ID, 'lmfwc_subscription_renewal_interval_type', true);
        $customInterval      = get_post_meta($post->ID, 'lmfwc_subscription_renewal_custom_interval', true);
        $customPeriod        = get_post_meta($post->ID, 'lmfwc_subscription_renewal_custom_period', true);

        $wrapperClass = array(
            'lmfwc_subscription_renewal_interval_type'   => '',
            'lmfwc_subscription_renewal_custom_interval' => '',
            'lmfwc_subscription_renewal_custom_period'   => ''
        );

        if ($renewalAction === 'extend_existing_license') {
            if ($renewalIntervalType === 'subscription') {
                $wrapperClass['lmfwc_subscription_renewal_custom_interval'] .= ' hidden';
                $wrapperClass['lmfwc_subscription_renewal_custom_period']   .= ' hidden';
            }
        } else {
            $wrapperClass['lmfwc_subscription_renewal_interval_type']   .= ' hidden';
            $wrapperClass['lmfwc_subscription_renewal_custom_interval'] .= ' hidden';
            $wrapperClass['lmfwc_subscription_renewal_custom_period']   .= ' hidden';
        }

        echo '</div><div class="options_group">';

        // Dropdown "lmfwc_subscription_renewal_action"
        woocommerce_wp_select(
            array(
                'id'            => 'lmfwc_subscription_renewal_action',
                'class'         => 'lmfwc_subscription_renewal_action',
                'label'         => __('On subscription renewal', 'license-manager-for-woocommerce'),
                'options'       => array(
                    'issue_new_license'       => __('Issue a new license key on each subscription renewal', 'license-manager-for-woocommerce'),
                    'extend_existing_license' => __('Extend the existing license on each subscription renewal', 'license-manager-for-woocommerce')
                ),
                'value' => $renewalAction
            )
        );

        // Dropdown "lmfwc_subscription_renewal_interval_type"
        woocommerce_wp_select(
            array(
                'id'            => 'lmfwc_subscription_renewal_interval_type',
                'class'         => 'lmfwc_subscription_renewal_interval_type',
                'wrapper_class' => $wrapperClass['lmfwc_subscription_renewal_interval_type'],
                'label'         => __('Extend by', 'license-manager-for-woocommerce'),
                'options'       => array(
                    'subscription' => __('WooCommerce Subscription interval', 'license-manager-for-woocommerce'),
                    'custom'       => __('Custom interval', 'license-manager-for-woocommerce')
                ),
                'value' => $renewalIntervalType
            )
        );

        // Number "lmfwc_subscription_renewal_custom_interval"
        woocommerce_wp_text_input(
            array(
                'id'                => 'lmfwc_subscription_renewal_custom_interval',
                'class'             => 'lmfwc_subscription_renewal_custom_interval',
                'wrapper_class'     => $wrapperClass['lmfwc_subscription_renewal_custom_interval'],
                'label'             => __('Interval', 'license-manager-for-woocommerce'),
                'value'             => $customInterval,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '1'
                )
            )
        );

        // Dropdown "lmfwc_subscription_renewal_custom_period"
        woocommerce_wp_select(
            array(
                'id'            => 'lmfwc_subscription_renewal_custom_period',
                'class'         => 'lmfwc_subscription_renewal_custom_period',
                'wrapper_class' => $wrapperClass['lmfwc_subscription_renewal_custom_period'],
                'label'         => __('Period', 'license-manager-for-woocommerce'),
                'options'       => array(
                    'hour'  => __('Hour(s)', 'license-manager-for-woocommerce'),
                    'day'   => __('Day(s)', 'license-manager-for-woocommerce'),
                    'week'  => __('Week(s)', 'license-manager-for-woocommerce'),
                    'month' => __('Month(s)', 'license-manager-for-woocommerce'),
                    'year'  => __('Year(s)', 'license-manager-for-woocommerce'),
                ),
                'value' => $customPeriod
            )
        );
    }

    /**
     * Stores the additionally created input fields in the database.
     *
     * @param int $postId
     */
    public function simpleProductSave($postId)
    {
        // Update the extend subscription flag, according to checkbox.
        if (array_key_exists('lmfwc_license_expiration_extendable_for_subscriptions', $_POST)) {
            update_post_meta($postId, 'lmfwc_license_expiration_extendable_for_subscriptions', 1);
        } else {
            update_post_meta($postId, 'lmfwc_license_expiration_extendable_for_subscriptions', 0);
        }

        // Update the subscription renewal action
        if (isset($_POST['lmfwc_subscription_renewal_action'])) {
            update_post_meta(
                $postId,
                'lmfwc_subscription_renewal_action',
                sanitize_text_field($_POST['lmfwc_subscription_renewal_action'])
            );
        }

        // Update the subscription renewal interval type
        if (isset($_POST['lmfwc_subscription_renewal_interval_type'])) {
            update_post_meta(
                $postId,
                'lmfwc_subscription_renewal_interval_type',
                sanitize_text_field($_POST['lmfwc_subscription_renewal_interval_type'])
            );
        }

        // Update the subscription renewal custom interval
        if (isset($_POST['lmfwc_subscription_renewal_custom_interval'])) {
            update_post_meta(
                $postId,
                'lmfwc_subscription_renewal_custom_interval',
                intval($_POST['lmfwc_subscription_renewal_custom_interval'])
            );
        }

        // Update the subscription renewal custom period
        if (isset($_POST['lmfwc_subscription_renewal_custom_period'])) {
            update_post_meta(
                $postId,
                'lmfwc_subscription_renewal_custom_period',
                sanitize_text_field($_POST['lmfwc_subscription_renewal_custom_period'])
            );
        }
    }

    /**
     * Adds integration specific fields to the variable product.
     *
     * @param int     $loop
     * @param array   $variationData
     * @param WP_Post $variation
     */
    public function variableProductDataPanel($loop, $variationData, $variation)
    {
        $productId = $variation->ID;

        if (!WC_Subscriptions_Product::is_subscription($productId)) {
            return;
        }

        $renewalAction       = get_post_meta($productId, 'lmfwc_subscription_renewal_action', true);
        $renewalIntervalType = get_post_meta($productId, 'lmfwc_subscription_renewal_interval_type', true);
        $customInterval      = get_post_meta($productId, 'lmfwc_subscription_renewal_custom_interval', true);
        $customPeriod        = get_post_meta($productId, 'lmfwc_subscription_renewal_custom_period', true);

        $wrapperClass = array(
            'lmfwc_subscription_renewal_interval_type'   => 'form-row form-row-full',
            'lmfwc_subscription_renewal_custom_interval' => 'form-field form-row form-row-first',
            'lmfwc_subscription_renewal_custom_period'   => 'form-field form-row form-row-last'
        );

        if ($renewalAction === 'extend_existing_license') {
            if ($renewalIntervalType === 'subscription') {
                $wrapperClass['lmfwc_subscription_renewal_custom_interval'] .= ' hidden';
                $wrapperClass['lmfwc_subscription_renewal_custom_period']   .= ' hidden';
            }
        } else {
            $wrapperClass['lmfwc_subscription_renewal_interval_type']   .= ' hidden';
            $wrapperClass['lmfwc_subscription_renewal_custom_interval'] .= ' hidden';
            $wrapperClass['lmfwc_subscription_renewal_custom_period']   .= ' hidden';
        }

        //echo '</div><div class="options_group">';

        // Dropdown "lmfwc_subscription_renewal_action"
        woocommerce_wp_select(
            array(
                'id'            => sprintf('lmfwc_subscription_renewal_action_%d', $loop),
                'class'         => 'lmfwc_subscription_renewal_action',
                'name'          => sprintf('lmfwc_subscription_renewal_action[%d]', $loop),
                'label'         => __('On subscription renewal', 'license-manager-for-woocommerce'),
                'options'       => array(
                    'issue_new_license'       => __('Issue a new license key on each subscription renewal', 'license-manager-for-woocommerce'),
                    'extend_existing_license' => __('Extend the existing license on each subscription renewal', 'license-manager-for-woocommerce')
                ),
                'value' => $renewalAction,
                'wrapper_class' => 'form-row form-row-full'
            )
        );

        // Dropdown "lmfwc_subscription_renewal_interval_type"
        woocommerce_wp_select(
            array(
                'id'            => sprintf('lmfwc_subscription_renewal_interval_type_%d', $loop),
                'class'         => 'lmfwc_subscription_renewal_interval_type',
                'wrapper_class' => $wrapperClass['lmfwc_subscription_renewal_interval_type'],
                'name'          => sprintf('lmfwc_subscription_renewal_interval_type[%d]', $loop),
                'label'         => __('Extend by', 'license-manager-for-woocommerce'),
                'options'       => array(
                    'subscription' => __('WooCommerce Subscription interval', 'license-manager-for-woocommerce'),
                    'custom'       => __('Custom interval', 'license-manager-for-woocommerce')
                ),
                'value' => $renewalIntervalType
            )
        );

        echo '<div>';

        // Number "lmfwc_subscription_renewal_custom_interval"
        woocommerce_wp_text_input(
            array(
                'id'                => sprintf('lmfwc_subscription_renewal_custom_interval_%d', $loop),
                'class'             => 'lmfwc_subscription_renewal_custom_interval',
                'wrapper_class'     => $wrapperClass['lmfwc_subscription_renewal_custom_interval'],
                'name'              => sprintf('lmfwc_subscription_renewal_custom_interval[%d]', $loop),
                'label'             => __('Interval', 'license-manager-for-woocommerce'),
                'value'             => $customInterval,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '1'
                )
            )
        );

        // Dropdown "lmfwc_subscription_renewal_custom_period"
        woocommerce_wp_select(
            array(
                'id'            => sprintf('lmfwc_subscription_renewal_custom_period_%d', $loop),
                'class'         => 'lmfwc_subscription_renewal_custom_period',
                'wrapper_class' => $wrapperClass['lmfwc_subscription_renewal_custom_period'],
                'name'          => sprintf('lmfwc_subscription_renewal_custom_period[%d]', $loop),
                'label'         => __('Period', 'license-manager-for-woocommerce'),
                'options'       => array(
                    'hour'  => __('Hour(s)', 'license-manager-for-woocommerce'),
                    'day'   => __('Day(s)', 'license-manager-for-woocommerce'),
                    'week'  => __('Week(s)', 'license-manager-for-woocommerce'),
                    'month' => __('Month(s)', 'license-manager-for-woocommerce'),
                    'year'  => __('Year(s)', 'license-manager-for-woocommerce'),
                ),
                'value' => $customPeriod
            )
        );

        echo '</div>';
    }

    /**
     * Saves the data from the product variation fields.
     *
     * @param int $variationId
     * @param int $i
     */
    public function variableProductSave($variationId, $i)
    {
        // Update the subscription renewal action
        if (isset($_POST['lmfwc_subscription_renewal_action'][$i])) {
            update_post_meta(
                $variationId,
                'lmfwc_subscription_renewal_action',
                sanitize_text_field($_POST['lmfwc_subscription_renewal_action'][$i])
            );
        }

        // Update the subscription renewal interval type
        if (isset($_POST['lmfwc_subscription_renewal_interval_type'][$i])) {
            update_post_meta(
                $variationId,
                'lmfwc_subscription_renewal_interval_type',
                sanitize_text_field($_POST['lmfwc_subscription_renewal_interval_type'][$i])
            );
        }

        // Update the subscription renewal custom interval
        if (isset($_POST['lmfwc_subscription_renewal_custom_interval'][$i])) {
            update_post_meta(
                $variationId,
                'lmfwc_subscription_renewal_custom_interval',
                intval($_POST['lmfwc_subscription_renewal_custom_interval'][$i])
            );
        }

        // Update the subscription renewal custom period
        if (isset($_POST['lmfwc_subscription_renewal_custom_period'][$i])) {
            update_post_meta(
                $variationId,
                'lmfwc_subscription_renewal_custom_period',
                sanitize_text_field($_POST['lmfwc_subscription_renewal_custom_period'][$i])
            );
        }
    }
}