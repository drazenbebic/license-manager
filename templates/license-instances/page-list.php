<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php esc_html_e('License instance keys', 'lmfwc'); ?></h1>
<a class="page-title-action" href="<?php echo esc_url($addLicenseInstanceUrl); ?>">
    <span><?php esc_html_e('Add new', 'lmfwc');?></span>
</a>
<hr class="wp-header-end">

<form method="post" id="lmfwc-license-table">
    <?php
        $licenseInstances->prepare_items();
        $licenseInstances->views();
        $licenseInstances->search_box(__( 'Search license instance key', 'lmfwc' ), 'instance_key');
        $licenseInstances->display();
    ?>
</form>

<span class="lmfwc-txt-copied-to-clipboard" style="display: none"><?php esc_html_e('Copied to clipboard', 'lmfwc'); ?></span>