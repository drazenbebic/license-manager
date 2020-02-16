<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php esc_html_e('License keys', 'license-manager-for-woocommerce'); ?></h1>
<a class="page-title-action" href="<?php echo esc_url($addLicenseUrl); ?>">
    <span><?php esc_html_e('Add new', 'license-manager-for-woocommerce');?></span>
</a>
<a class="page-title-action" href="<?php echo esc_url($importLicenseUrl); ?>">
    <span><?php esc_html_e('Import', 'license-manager-for-woocommerce');?></span>
</a>
<hr class="wp-header-end">

<?php  ?>

<form method="post" id="lmfwc-license-table">
    <?php
        $licenses->prepare_items();
        $licenses->views();
        $licenses->search_box(__( 'Search license key', 'license-manager-for-woocommerce' ), 'license_key');
        $licenses->display();
    ?>
</form>

<span class="lmfwc-txt-copied-to-clipboard" style="display: none"><?php esc_html_e('Copied to clipboard', 'license-manager-for-woocommerce'); ?></span>