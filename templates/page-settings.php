<?php defined('ABSPATH') || exit; ?>

<div class="wrap lmfwc">

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo esc_url($urlGeneral); ?>" class="nav-tab <?=$tab === 'general' ? 'nav-tab-active' : '';?>">
            <span><?php esc_html_e('General', 'license-manager-for-woocommerce');?></span>
        </a>
        <a href="<?php echo esc_url($urlRestApi); ?>" class="nav-tab <?=$tab === 'rest_api' ? 'nav-tab-active' : '';?>">
            <span><?php esc_html_e('REST API keys', 'license-manager-for-woocommerce');?></span>
        </a>
    </nav>

    <?php if ($tab == 'general'): ?>

        <form action="options.php" method="POST">
            <?php settings_fields('lmfwc_settings_group_general'); ?>
            <?php do_settings_sections('lmfwc_license_keys'); ?>
            <?php do_settings_sections('lmfwc_rest_api'); ?>
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