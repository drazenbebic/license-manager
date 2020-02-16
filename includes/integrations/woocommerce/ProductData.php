<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce;

use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\Generator as GeneratorResourceRepository;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use WP_Error;

defined('ABSPATH') || exit;

class ProductData
{
    /**
     * @var string
     */
    const ADMIN_TAB_NAME   = 'license_manager_tab';

    /**
     * @var string
     */
    const ADMIN_TAB_TARGET = 'license_manager_product_data';

    /**
     * ProductData constructor.
     */
    public function __construct()
    {
        /**
         * @see https://www.proy.info/woocommerce-admin-custom-product-data-tab/
         */
        add_filter('woocommerce_product_data_tabs',   array($this, 'simpleProductLicenseManagerTab'));
        add_action('woocommerce_product_data_panels', array($this, 'simpleProductLicenseManagerPanel'));

        add_action(
            'woocommerce_product_after_variable_attributes',
            array($this, 'variableProductLicenseManagerFields'),
            10,
            3
        );

        add_action(
            'woocommerce_save_product_variation',
            array($this, 'variableProductLicenseManagerSaveAction'),
            10,
            2
        );

        // Change the product_data_tab icon
        add_action('admin_head', array($this, 'styleInventoryManagement'));

        add_action('save_post', array($this, 'savePost'), 10);
    }

    /**
     * Adds a product data tab for simple WooCommerce products.
     *
     * @param array $tabs
     *
     * @return mixed
     */
    public function simpleProductLicenseManagerTab($tabs)
    {
        $tabs[self::ADMIN_TAB_NAME] = array(
            'label'    => __('License Manager', 'license-manager-for-woocommerce'),
            'target'   => self::ADMIN_TAB_TARGET,
            'class'    => array('show_if_simple'),
            'priority' => 21
        );

        return $tabs;
    }

    /**
     * Displays the new fields inside the new product data tab.
     */
    public function simpleProductLicenseManagerPanel()
    {
        global $post;

        /** @var GeneratorResourceModel[] $generators */
        $generators        = GeneratorResourceRepository::instance()->findAll();
        $licensed          = get_post_meta($post->ID, 'lmfwc_licensed_product',                    true);
        $deliveredQuantity = get_post_meta($post->ID, 'lmfwc_licensed_product_delivered_quantity', true);
        $generatorId       = get_post_meta($post->ID, 'lmfwc_licensed_product_assigned_generator', true);
        $useGenerator      = get_post_meta($post->ID, 'lmfwc_licensed_product_use_generator',      true);
        $useStock          = get_post_meta($post->ID, 'lmfwc_licensed_product_use_stock',          true);
        $generatorOptions  = array('' => __('Please select a generator', 'license-manager-for-woocommerce'));

        if ($generators) {
            /** @var GeneratorResourceModel $generator */
            foreach ($generators as $generator) {
                $generatorOptions[$generator->getId()] = sprintf(
                    '(#%d) %s',
                    $generator->getId(),
                    $generator->getName()
                );
            }
        }

        echo sprintf(
            '<div id="%s" class="panel woocommerce_options_panel"><div class="options_group">',
            self::ADMIN_TAB_TARGET
        );

        echo '<input type="hidden" name="lmfwc_edit_flag" value="true" />';

        // Checkbox "lmfwc_licensed_product"
        woocommerce_wp_checkbox(
            array(
                'id'          => 'lmfwc_licensed_product',
                'label'       => __('Sell license keys', 'license-manager-for-woocommerce'),
                'description' => __('Sell license keys for this product', 'license-manager-for-woocommerce'),
                'value'       => $licensed,
                'cbvalue'     => 1,
                'desc_tip'    => false
            )
        );

        // Number "lmfwc_licensed_product_deliver_amount"
        woocommerce_wp_text_input(
            array(
                'id'                => 'lmfwc_licensed_product_delivered_quantity',
                'label'             => __('Delivered quantity', 'license-manager-for-woocommerce'),
                'value'             => $deliveredQuantity ? $deliveredQuantity : 1,
                'description'       => __('Defines the amount of license keys to be delivered upon purchase.', 'license-manager-for-woocommerce'),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '1'
                )
            )
        );

        echo '</div><div class="options_group">';

        // Checkbox "lmfwc_licensed_product_use_generator"
        woocommerce_wp_checkbox(
            array(
                'id'          => 'lmfwc_licensed_product_use_generator',
                'label'       => __('Generate license keys', 'license-manager-for-woocommerce'),
                'description' => __('Automatically generate license keys with each sold product', 'license-manager-for-woocommerce'),
                'value'       => $useGenerator,
                'cbvalue'     => 1,
                'desc_tip'    => false
            )
        );

        // Dropdown "lmfwc_licensed_product_assigned_generator"
        woocommerce_wp_select(
            array(
                'id'      => 'lmfwc_licensed_product_assigned_generator',
                'label'   => __('Assign generator', 'license-manager-for-woocommerce'),
                'options' => $generatorOptions,
                'value'   => $generatorId
            )
        );

        echo '</div><div class="options_group">';

        // Checkbox "lmfwc_licensed_product_use_stock"
        woocommerce_wp_checkbox(
            array(
                'id'          => 'lmfwc_licensed_product_use_stock',
                'label'       => __('Sell from stock', 'license-manager-for-woocommerce'),
                'description' => __('Sell license keys from the available stock.', 'license-manager-for-woocommerce'),
                'value'       => $useStock,
                'cbvalue'     => 1,
                'desc_tip'    => false
            )
        );

        echo sprintf(
            '<p class="form-field"><label>%s</label><span class="description">%d %s</span></p>',
            __('Available', 'license-manager-for-woocommerce'),
            LicenseResourceRepository::instance()->countBy(
                array(
                    'product_id' => $post->ID,
                    'status' => LicenseStatus::ACTIVE
                )
            ),
            __('License key(s) in stock and available for sale', 'license-manager-for-woocommerce')
        );

        echo '</div></div>';
    }

    /**
     * Adds an icon to the new data tab.
     *
     * @see https://docs.woocommerce.com/document/utilising-the-woocommerce-icon-font-in-your-extensions/
     * @see https://developer.wordpress.org/resource/dashicons/
     */
    public function styleInventoryManagement()
    {
        echo sprintf(
            '<style>#woocommerce-product-data ul.wc-tabs li.%s_options a:before { font-family: %s; content: "%s"; }</style>',
            self::ADMIN_TAB_NAME,
            'dashicons',
            '\f160'
        );
    }

    /**
     * Hook which triggers when the WooCommerce Product is being saved or updated.
     *
     * @param int $postId
     */
    public function savePost($postId)
    {
        // This is not a product.
        if (!array_key_exists('post_type', $_POST)
            || $_POST['post_type'] != 'product'
            || !array_key_exists('lmfwc_edit_flag', $_POST)
        ) {
            return;
        }

        // Update licensed product flag, according to checkbox.
        if (array_key_exists('lmfwc_licensed_product', $_POST)) {
            update_post_meta($postId, 'lmfwc_licensed_product', 1);
        }

        else {
            update_post_meta($postId, 'lmfwc_licensed_product', 0);
        }

        // Update delivered quantity, according to field.
        $deliveredQuantity = absint($_POST['lmfwc_licensed_product_delivered_quantity']);

        update_post_meta(
            $postId,
            'lmfwc_licensed_product_delivered_quantity',
            $deliveredQuantity ? $deliveredQuantity : 1
        );

        // Update the use stock flag, according to checkbox.
        if (array_key_exists('lmfwc_licensed_product_use_stock', $_POST)) {
            update_post_meta($postId, 'lmfwc_licensed_product_use_stock', 1);
        }

        else {
            update_post_meta($postId, 'lmfwc_licensed_product_use_stock', 0);
        }

        // Update the assigned generator id, according to select field.
        update_post_meta(
            $postId,
            'lmfwc_licensed_product_assigned_generator',
            intval($_POST['lmfwc_licensed_product_assigned_generator'])
        );

        // Update the use generator flag, according to checkbox.
        if (array_key_exists('lmfwc_licensed_product_use_generator', $_POST)) {
            // You must select a generator if you wish to assign it to the product.
            if (!$_POST['lmfwc_licensed_product_assigned_generator']) {
                $error = new WP_Error(2, __('Assign a generator if you wish to sell automatically generated licenses for this product.', 'license-manager-for-woocommerce'));

                set_transient('lmfwc_error', $error, 45);
                update_post_meta($postId, 'lmfwc_licensed_product_use_generator', 0);
                update_post_meta($postId, 'lmfwc_licensed_product_assigned_generator', 0);
            }

            else {
                update_post_meta($postId, 'lmfwc_licensed_product_use_generator', 1);
            }
        }

        else {
            update_post_meta($postId, 'lmfwc_licensed_product_use_generator', 0);
            update_post_meta($postId, 'lmfwc_licensed_product_assigned_generator', 0);
        }
    }

    /**
     * Adds the new product data fields to variable WooCommerce Products.
     *
     * @param $loop
     * @param $variationData
     * @param $variation
     */
    public function variableProductLicenseManagerFields($loop, $variationData, $variation)
    {
        /** @var GeneratorResourceModel[] $generators */
        $generators        = GeneratorResourceRepository::instance()->findAll();
        $productId         = $variation->ID;
        $licensed          = get_post_meta($productId, 'lmfwc_licensed_product',                    true);
        $deliveredQuantity = get_post_meta($productId, 'lmfwc_licensed_product_delivered_quantity', true);
        $generatorId       = get_post_meta($productId, 'lmfwc_licensed_product_assigned_generator', true);
        $useGenerator      = get_post_meta($productId, 'lmfwc_licensed_product_use_generator',      true);
        $useStock          = get_post_meta($productId, 'lmfwc_licensed_product_use_stock',          true);
        $generatorOptions  = array('' => __('Please select a generator', 'license-manager-for-woocommerce'));

        /** @var GeneratorResourceModel $generator */
        foreach ($generators as $generator) {
            $generatorOptions[$generator->getId()] = sprintf(
                '(#%d) %s',
                $generator->getId(),
                $generator->getName()
            );
        }

        echo '<div class="panel woocommerce_options_panel" style="width: 100%;"><div class="options_group">';

        echo sprintf('<strong>%s</strong>', __('License Manager for WooCommerce', 'license-manager-for-woocommerce'));

        echo '<input type="hidden" name="lmfwc_edit_flag" value="true" />';

        // Checkbox "lmfwc_licensed_product"
        woocommerce_wp_checkbox(
            array(
                'id'          => 'lmfwc_licensed_product',
                'label'       => __('Sell license key(s)', 'license-manager-for-woocommerce'),
                'description' => __('Sell license keys for this variation', 'license-manager-for-woocommerce'),
                'value'       => $licensed,
                'cbvalue'     => 1,
                'desc_tip'    => false
            )
        );

        // Number "lmfwc_licensed_product_deliver_amount"
        woocommerce_wp_text_input(
            array(
                'id'                => 'lmfwc_licensed_product_delivered_quantity',
                'label'             => __('Delivered quantity', 'license-manager-for-woocommerce'),
                'value'             => $deliveredQuantity ? $deliveredQuantity : 1,
                'description'       => __('Defines the amount of license keys to be delivered upon purchase.', 'license-manager-for-woocommerce'),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '1'
                )
            )
        );

        echo '</div><div class="options_group">';

        // Checkbox "lmfwc_licensed_product_use_generator"
        woocommerce_wp_checkbox(
            array(
                'id'          => 'lmfwc_licensed_product_use_generator',
                'label'       => __('Generate license keys', 'license-manager-for-woocommerce'),
                'description' => __('Automatically generate license keys with each sold variation', 'license-manager-for-woocommerce'),
                'value'       => $useGenerator,
                'cbvalue'     => 1,
                'desc_tip'    => false
            )
        );

        // Dropdown "lmfwc_licensed_product_assigned_generator"
        woocommerce_wp_select(
            array(
                'id'      => 'lmfwc_licensed_product_assigned_generator',
                'label'   => __('Assign generator', 'license-manager-for-woocommerce'),
                'options' => $generatorOptions,
                'value'   => $generatorId
            )
        );

        echo '</div><div class="options_group">';

        // Checkbox "lmfwc_licensed_product_use_stock"
        woocommerce_wp_checkbox(
            array(
                'id'          => 'lmfwc_licensed_product_use_stock',
                'label'       => __('Sell from stock', 'license-manager-for-woocommerce'),
                'description' => __('Sell license keys from the available stock.', 'license-manager-for-woocommerce'),
                'value'       => $useStock,
                'cbvalue'     => 1,
                'desc_tip'    => false
            )
        );

        echo sprintf(
            '<p class="form-field"><label>%s</label><span class="description">%d %s</span></p>',
            __('Available', 'license-manager-for-woocommerce'),
            LicenseResourceRepository::instance()->countBy(
                array(
                    'product_id' => $productId,
                    'status' => LicenseStatus::ACTIVE
                )
            ),
            __('License key(s) in stock and available for sale.', 'license-manager-for-woocommerce')
        );

        echo '</div></div>';
    }

    /**
     * Saves the data from the product variation fields.
     *
     * @param int $variationId
     * @param int $i
     */
    public function variableProductLicenseManagerSaveAction($variationId, $i)
    {
        // Update licensed product flag, according to checkbox.
        if (array_key_exists('lmfwc_licensed_product', $_POST)) {
            update_post_meta($variationId, 'lmfwc_licensed_product', 1);
        }

        else {
            update_post_meta($variationId, 'lmfwc_licensed_product', 0);
        }

        // Update delivered quantity, according to field.
        $deliveredQuantity = absint($_POST['lmfwc_licensed_product_delivered_quantity']);

        update_post_meta(
            $variationId,
            'lmfwc_licensed_product_delivered_quantity',
            $deliveredQuantity ? $deliveredQuantity : 1
        );

        // Update the use stock flag, according to checkbox.
        if (array_key_exists('lmfwc_licensed_product_use_stock', $_POST)) {
            update_post_meta($variationId, 'lmfwc_licensed_product_use_stock', 1);
        }

        else {
            update_post_meta($variationId, 'lmfwc_licensed_product_use_stock', 0);
        }

        // Update the assigned generator id, according to select field.
        update_post_meta(
            $variationId,
            'lmfwc_licensed_product_assigned_generator',
            intval($_POST['lmfwc_licensed_product_assigned_generator'])
        );

        // Update the use generator flag, according to checkbox.
        if (array_key_exists('lmfwc_licensed_product_use_generator', $_POST)) {
            // You must select a generator if you wish to assign it to the product.
            if (!$_POST['lmfwc_licensed_product_assigned_generator']) {
                $error = new WP_Error(2, __('Assign a generator if you wish to sell automatically generated licenses for this product.', 'license-manager-for-woocommerce'));

                set_transient('lmfwc_error', $error, 45);
                update_post_meta($variationId, 'lmfwc_licensed_product_use_generator', 0);
                update_post_meta($variationId, 'lmfwc_licensed_product_assigned_generator', 0);
            }

            else {
                update_post_meta($variationId, 'lmfwc_licensed_product_use_generator', 1);
            }
        }

        else {
            update_post_meta($variationId, 'lmfwc_licensed_product_use_generator', 0);
            update_post_meta($variationId, 'lmfwc_licensed_product_assigned_generator', 0);
        }
    }
}