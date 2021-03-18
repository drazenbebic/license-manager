<?php

namespace LicenseManagerForWooCommerce\Lists;

use DateTime;
use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Models\Resources\ProductInstalledOn as ProductInstalledOnResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use LicenseManagerForWooCommerce\Repositories\Resources\ProductInstalledOn as ProductInstalledOnResourceRepository;
use LicenseManagerForWooCommerce\Settings;
use LicenseManagerForWooCommerce\Setup;
use WP_List_Table;
use wpdb;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ProductsInstalledOnList extends WP_List_Table {
	/**
	 * Path to spinner image.
	 */
	const SPINNER_URL = '/wp-admin/images/loading.gif';

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var wpdb
	 */
	private wpdb $wpdb;

	/**
	 * @var string
	 */
	protected $gmtOffset;

	/**
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * @var string
	 */
	protected $timeFormat;

	/**
	 * GeneratorsList constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		parent::__construct( [
			'singular' => __( 'Products installed on', 'license-manager-for-woocommerce' ),
			'plural'   => __( 'Products installed on', 'license-manager-for-woocommerce' ),
			'ajax'     => false
		] );

		$this->table      = $wpdb->prefix . Setup::PRODUCTS_INSTALLED_ON_TABLE_NAME;
		$this->gmtOffset  = get_option( 'gmt_offset' );
		$this->dateFormat = get_option( 'date_format' );
		$this->timeFormat = get_option( 'time_format' );
	}

	/**
	 * Retrieves the products installed on from the database
	 *
	 * @param int $perPage Default amount of products intalled on per page
	 * @param int $pageNumber Default page number
	 *
	 * @return array
	 */
	public function get_products_installed_on( $perPage = 20, $pageNumber = 1 ): array {
		$sql = "SELECT * FROM {$this->table}";
		$sql .= ' ORDER BY ' . ( empty( $_REQUEST['orderby'] ) ? 'id' : esc_sql( $_REQUEST['orderby'] ) );
		$sql .= ' ' . ( empty( $_REQUEST['order'] ) ? 'DESC' : esc_sql( $_REQUEST['order'] ) );
		$sql .= " LIMIT {$perPage}";
		$sql .= ' OFFSET ' . ( $pageNumber - 1 ) * $perPage;

		return $this->wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Retrieves the products installed on table row count
	 *
	 * @return int
	 */
	private function get_products_installed_on_count(): int {
		return $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}

	/**
	 * Output in case no items exist.
	 */
	public function no_items(): void {
		_e( 'No products installed on found.', 'license-manager-for-woocommerce' );
	}

	/**
	 * Default column value
	 *
	 * @param array $item Associative array of column name and value pairs
	 * @param string $column_name Name of the current column
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return $item[ $column_name ];
	}

	/**
	 * Checkbox column
	 *
	 * @param array $item Associative array of column name and value pairs
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', $item['id'] );
	}

	/**
	 * Product ID column
	 *
	 * @param array $item Associative array of column name and value pairs
	 *
	 * @return string
	 */
	public function column_product_id( array $item ): string {
		$html = '';

		if ( ! empty( $item['license_id'] ) ) {
			$license = LicenseResourceRepository::instance()->find( $item['license_id'] );

			if ( ! empty( $license ) ) {
				$productId = $license->getProductId();

				if ( $product = wc_get_product( $productId ) ) {
					if ( $parentId = $product->get_parent_id() ) {
						$html = sprintf( '<span>#%s - %s</span>', $product->get_id(), $product->get_name() );

						if ( $parent = wc_get_product( $parentId ) ) {
							$html .= sprintf( '<br><small>%s <a href="%s" target="_blank">#%s - %s</a></small>', __( 'Variation of', 'license-manager-for-woocommerce' ), get_edit_post_link( $parent->get_id() ), $parent->get_id(), $parent->get_name() );
						}
					} else {
						$html = sprintf( '<a href="%s" target="_blank">#%s - %s</a>', get_edit_post_link( $productId ), $product->get_id(), $product->get_name() );
					}
				}
			}
		} elseif ( ! empty( $item['product_name'] ) ) {
			$html = $item['product_name'];
		}

		// Delete
		$actions['delete'] = sprintf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=%s&action=delete&id=%d&_wpnonce=%s', AdminMenus::PRODUCTS_INSTALLED_ON_PAGE, (int) $item['id'], wp_create_nonce( 'delete' ) ) ), __( 'Delete', 'license-manager-for-woocommerce' ) );

		return $html . $this->row_actions( $actions );
	}

	/**
	 * Order ID column
	 *
	 * @param array $item Associative array of column name and value pairs
	 *
	 * @return string
	 */
	public function column_order_id( array $item ): string {
		$html = '';

		if ( ! empty( $item['license_id'] ) ) {
			$license = LicenseResourceRepository::instance()->find( $item['license_id'] );

			if ( ! empty( $license ) ) {
				$orderId = $license->getOrderId();

				if ( $order = wc_get_order( $orderId ) ) {
					$html = sprintf( '<a href="%s" target="_blank">#%s</a>', get_edit_post_link( $orderId ), $order->get_order_number() );
				}
			}
		}

		return $html;
	}

	/**
	 * License key column
	 *
	 * @param array $item Associative array of column name and value pairs
	 *
	 * @return string
	 */
	public function column_license_key( array $item ): string {
		if ( empty( $item['license_id'] ) ) {
			return '';
		}

		$license = LicenseResourceRepository::instance()->find( $item['license_id'] );

		if ( $license === null ) {
			return '';
		}

		if ( Settings::get( 'lmfwc_hide_license_keys' ) ) {
			$title = '<code class="lmfwc-placeholder empty"></code>';
			$title .= sprintf( '<img class="lmfwc-spinner" data-id="%d" src="%s">', $item['license_id'], self::SPINNER_URL );
		} else {
			$title = sprintf( '<code class="lmfwc-placeholder">%s</code>', $license->getDecryptedLicenseKey() );
			$title .= sprintf( '<img class="lmfwc-spinner" data-id="%d" src="%s">', $item['license_id'], self::SPINNER_URL );
		}

		// ID
		$actions['id'] = sprintf( __( 'ID: %d', 'license-manager-for-woocommerce' ), (int) $item['license_id'] );

		// Hide/Show
		$actions['show'] = sprintf( '<a class="lmfwc-license-key-show" data-id="%d">%s</a>', $item['license_id'], __( 'Show', 'license-manager-for-woocommerce' ) );
		$actions['hide'] = sprintf( '<a class="lmfwc-license-key-hide" data-id="%d">%s</a>', $item['license_id'], __( 'Hide', 'license-manager-for-woocommerce' ) );

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Host column.
	 *
	 * @param array $item Associative array of column name and value pairs
	 *
	 * @return string
	 */
	public function column_host( array $item ): string {
		$html = '';

		if ( ! empty( $item['host'] ) ) {
			$html = sprintf( '<a href="%s" target="_blank">%s</a>', $item['host'], $item['host'] );
		}

		return $html;
	}

	/**
	 * Last ping column.
	 *
	 * @param array $item Associative array of column name and value pairs
	 *
	 * @return string
	 * @throws Exception
	 */
	public function column_last_ping( array $item ): string {
		$html = '';

		if ( ! empty( $item['last_ping'] ) ) {
			$offsetSeconds = (float) $this->gmtOffset * 60 * 60;
			$timestamp     = strtotime( $item['last_ping'] ) + $offsetSeconds;
			$result        = date( 'Y-m-d H:i:s', $timestamp );
			$date          = new DateTime( $result );

			$html = sprintf( '<span>%s <b>%s, %s</b></span>', __( 'at', 'license-manager-for-woocommerce' ), $date->format( $this->dateFormat ), $date->format( $this->timeFormat ) );
		}

		return $html;
	}

	/**
	 * Set the table columns
	 */
	public function get_columns(): array {
		return [
			'cb'          => '<input type="checkbox" />',
			'product_id'  => __( 'Product', 'license-manager-for-woocommerce' ),
			'order_id'    => __( 'Order', 'license-manager-for-woocommerce' ),
			'license_key' => __( 'License key', 'license-manager-for-woocommerce' ),
			'host'        => __( 'Installed on', 'license-manager-for-woocommerce' ),
			'last_ping'   => __( 'Last ping', 'license-manager-for-woocommerce' )
		];
	}

	/**
	 * Defines sortable columns and their sort value
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return [
			'product_id'  => [ 'product_id', true ],
			'order_id'    => [ 'order_id', true ],
			'license_key' => [ 'license_key', true ],
			'last_ping'   => [ 'last_ping', true ]
		];
	}

	/**
	 * Initialization function
	 *
	 * @throws Exception
	 */
	public function prepare_items(): void {
		$this->_column_headers = $this->get_column_info();

		$this->processBulkActions();

		$per_page     = $this->get_items_per_page( 'products_installed_on_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_products_installed_on_count();

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			]
		);

		$this->items = $this->get_products_installed_on( $per_page, $current_page );
	}

	/**
	 * Processes the currently selected action.
	 */
	private function processBulkActions(): void {
		$action = $this->current_action();

		if ( $action === 'delete' ) {
			$this->deleteProductsInstalledOn();
		}
	}

	/**
	 * Removes the product(s) installed on permanently from the database
	 *
	 * @throws Exception
	 */
	private function deleteProductsInstalledOn(): void {
		$this->verifyNonce( 'delete' );
		$this->verifySelection();

		$productInstalledOnIds = (array) $_REQUEST['id'];
		$count                 = 0;

		foreach ( $productInstalledOnIds as $productInstalledOnId ) {
			/** @var ProductInstalledOnResourceModel $license */
			$productInstalledOn = ProductInstalledOnResourceRepository::instance()->find( $productInstalledOnId );

			if ( ! $productInstalledOn ) {
				continue;
			}

			$result = ProductInstalledOnResourceRepository::instance()->delete( (array) $productInstalledOnId );

			if ( $result ) {
				$count += $result;
			}
		}

		$message = sprintf( esc_html__( '%d product(s) installed on permanently deleted.', 'license-manager-for-woocommerce' ), $count );

		// Set the admin notice
		AdminNotice::success( $message );

		// Redirect and exit
		wp_redirect( admin_url( sprintf( 'admin.php?page=%s', AdminMenus::PRODUCTS_INSTALLED_ON_PAGE ) ) );
	}

	/**
	 * Checks if the given nonce is (still) valid
	 *
	 * @param string $nonce The nonce to check
	 *
	 * @throws Exception
	 */
	private function verifyNonce( string $nonce ): void {
		$currentNonce = $_REQUEST['_wpnonce'];

		if ( ! wp_verify_nonce( $currentNonce, $nonce ) && ! wp_verify_nonce( $currentNonce, 'bulk-' . $this->_args['plural'] ) ) {
			AdminNotice::error( __( 'The nonce is invalid or has expired.', 'license-manager-for-woocommerce' ) );
			wp_redirect( admin_url( sprintf( 'admin.php?page=%s', AdminMenus::PRODUCTS_INSTALLED_ON_PAGE ) ) );

			exit;
		}
	}

	/**
	 * Makes sure that license keys were selected for the bulk action
	 */
	private function verifySelection(): void {
		// No ID's were selected, show a warning and redirect
		if ( ! array_key_exists( 'id', $_REQUEST ) ) {
			$message = sprintf( esc_html__( 'No products installed on were selected.', 'license-manager-for-woocommerce' ) );
			AdminNotice::warning( $message );

			wp_redirect( admin_url( sprintf( 'admin.php?page=%s', AdminMenus::PRODUCTS_INSTALLED_ON_PAGE ) ) );

			exit;
		}
	}
}
