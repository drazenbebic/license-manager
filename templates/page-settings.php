<?php

defined('ABSPATH') || exit;

/**
 * Available variables
 *
 * @var string $tab
 * @var string $urlGeneral
 * @var string $urlOrderStatus
 * @var string $urlRestApi
 */

?>

<div class="wrap lmfwc">

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo esc_url($urlGeneral); ?>" class="nav-tab <?=$tab === 'general' ? 'nav-tab-active' : '';?>">
            <span><?php esc_html_e('General', 'lmfwc');?></span>
        </a>
        <a href="<?php echo esc_url($urlOrderStatus); ?>" class="nav-tab <?=$tab === 'order_status' ? 'nav-tab-active' : '';?>">
            <span><?php esc_html_e('Order status', 'lmfwc');?></span>
        </a>
        <a href="<?php echo esc_url($urlRestApi); ?>" class="nav-tab <?=$tab === 'rest_api' ? 'nav-tab-active' : '';?>">
            <span><?php esc_html_e('REST API keys', 'lmfwc');?></span>
        </a>
    </nav>

    <?php if ($tab == 'general'): ?>

        <form action="<?php echo admin_url('options.php'); ?>" method="POST">
            <?php settings_fields('lmfwc_settings_group_general'); ?>
            <?php do_settings_sections('lmfwc_license_keys'); ?>
            <?php do_settings_sections('lmfwc_rest_api'); ?>
            <?php submit_button(); ?>
        </form>

    <?php elseif ($tab === 'order_status'): ?>

        <form action="<?php echo admin_url('options.php'); ?>" method="POST">
            <?php settings_fields('lmfwc_settings_group_order_status'); ?>
            <?php do_settings_sections('lmfwc_license_key_delivery'); ?>
            <?php submit_button(); ?>
        </form>

    <?php elseif ($tab === 'rest_api'): ?>

        <?php if ($action === 'list'): ?>

            <?php include_once 'settings/rest-api-list.php'; ?>

        <?php elseif ($action === 'show'): ?>

            <?php include_once 'settings/rest-api-show.php'; ?>

        <?php else: ?>

            <?php include_once 'settings/rest-api-key.php'; ?>

        <?php endif; ?>

    <?php endif; ?>

</div>