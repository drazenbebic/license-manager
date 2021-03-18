<?php

namespace LicenseManagerForWooCommerce\Repositories\Resources;

use LicenseManagerForWooCommerce\Abstracts\ResourceRepository as AbstractResourceRepository;
use LicenseManagerForWooCommerce\Enums\ColumnType as ColumnTypeEnum;
use LicenseManagerForWooCommerce\Models\Resources\ProductInstalledOn as ProductInstalledOnResourceModel;

defined( 'ABSPATH' ) || exit;

class ProductInstalledOn extends AbstractResourceRepository {
	/**
	 * @var string
	 */
	const TABLE = 'lmfwc_products_installed_on';

	/**
	 * InstalledProduct constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table      = $wpdb->prefix . self::TABLE;
		$this->primaryKey = 'id';
		$this->model      = ProductInstalledOnResourceModel::class;
		$this->mapping    = [
			'product_name' => ColumnTypeEnum::LONGTEXT,
			'license_id'   => ColumnTypeEnum::BIGINT,
			'host'         => ColumnTypeEnum::VARCHAR,
			'last_ping'    => ColumnTypeEnum::DATETIME,
		];
	}
}
