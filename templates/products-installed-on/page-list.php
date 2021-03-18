<?php

use LicenseManagerForWooCommerce\Lists\GeneratorsList;

defined( 'ABSPATH' ) || exit;

/**
 * @var string $addGeneratorUrl
 * @var string $generateKeysUrl
 * @var GeneratorsList $products_installed_on
 */

?>
<h1 class="wp-heading-inline"><?= esc_html__( 'Products installed on', 'license-manager-for-woocommerce' ) ?></h1>
<p>
    <b><?= esc_html__( 'Important', 'license-manager-for-woocommerce' ) ?>:</b>
    <span><?= esc_html__( 'Products will appear here, after they are installed on a customer website. If the license key is empty, the product is not officially registered in the license manager or the ping went wrong.', 'license-manager-for-woocommerce' ) ?></span>
</p>
<hr class="wp-header-end">
<form method="post">
	<?php
	$products_installed_on->prepare_items();
	$products_installed_on->display();
	?>
</form>
