<?php

namespace LicenseManagerForWooCommerce\Lists;

use DateTime;
use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use LicenseManagerForWooCommerce\Settings;
use LicenseManagerForWooCommerce\Setup;
use WC_Product;
use WP_List_Table;
use WP_User;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class LicensesList extends WP_List_Table
{
    /**
     * Path to spinner image.
     */
    const SPINNER_URL = '/wp-admin/images/loading.gif';

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $dateFormat;

    /**
     * @var string
     */
    protected $timeFormat;

    /**
     * @var string
     */
    protected $gmtOffset;

    /**
     * LicensesList constructor.
     */
    public function __construct()
    {
        global $wpdb;

        parent::__construct(
            array(
                'singular' => __('License key', 'lmfwc'),
                'plural'   => __('License keys', 'lmfwc'),
                'ajax'     => false
            )
        );

        $this->table      = $wpdb->prefix . Setup::LICENSES_TABLE_NAME;
        $this->dateFormat = get_option('date_format');
        $this->timeFormat = get_option('time_format');
        $this->gmtOffset  = get_option('gmt_offset');
    }

    /**
     * Creates the different status filter links at the top of the table.
     *
     * @return array
     */
    protected function get_views()
    {
        $statusLinks = array();
        $current     = !empty($_REQUEST['status']) ? $_REQUEST['status'] : 'all';

        // All link
        $class = $current == 'all' ? ' class="current"' :'';
        $allUrl = remove_query_arg('status');
        $statusLinks['all'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            $allUrl,
            $class,
            __('All', 'lmfwc'),
            LicenseResourceRepository::instance()->count()
        );

        // Sold link
        $class = $current == LicenseStatus::SOLD ? ' class="current"' :'';
        $soldUrl = esc_url(add_query_arg('status', LicenseStatus::SOLD));
        $statusLinks['sold'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            $soldUrl,
            $class,
            __('Sold', 'lmfwc'),
            LicenseResourceRepository::instance()->countBy(array('status' => LicenseStatus::SOLD))
        );

        // Delivered link
        $class = $current == LicenseStatus::DELIVERED ? ' class="current"' :'';
        $deliveredUrl = esc_url(add_query_arg('status', LicenseStatus::DELIVERED));
        $statusLinks['delivered'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            $deliveredUrl,
            $class,
            __('Delivered', 'lmfwc'),
            LicenseResourceRepository::instance()->countBy(array('status' => LicenseStatus::DELIVERED))
        );

        // Active link
        $class = $current == LicenseStatus::ACTIVE ? ' class="current"' :'';
        $activeUrl = esc_url(add_query_arg('status', LicenseStatus::ACTIVE));
        $statusLinks['active'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            $activeUrl,
            $class,
            __('Active', 'lmfwc'),
            LicenseResourceRepository::instance()->countBy(array('status' => LicenseStatus::ACTIVE))
        );

        // Inactive link
        $class = $current == LicenseStatus::INACTIVE ? ' class="current"' :'';
        $inactiveUrl = esc_url(add_query_arg('status', LicenseStatus::INACTIVE));
        $statusLinks['inactive'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            $inactiveUrl,
            $class,
            __('Inactive', 'lmfwc'),
            LicenseResourceRepository::instance()->countBy(array('status' => LicenseStatus::INACTIVE))
        );

        return $statusLinks;
    }

    /**
     * Adds the order and product filters to the licenses list.
     *
     * @param string $which
     */
    protected function extra_tablenav($which)
    {
        if ($which === 'top') {
            echo '<div class="alignleft actions">';
            $this->orderDropdown();
            $this->productDropdown();
            submit_button(__('Filter', 'lmfwc'), '', 'filter-action', false);
            echo '</div>';
        }
    }

    /**
     * Displays the order dropdown filter.
     */
    public function orderDropdown() {

        global $wpdb;

        $orders = array();
        $results = $wpdb->get_results(
            "SELECT DISTINCT `order_id` FROM {$this->table} WHERE `order_id` IS NOT NULL;",
            ARRAY_A
        );

        foreach ($results as $result) {
            if (!$orderId = intval($result['order_id'])) {
                continue;
            }

            if (!$order = wc_get_order($orderId)) {
                continue;
            }

            array_push($orders, array(
                'value' => $orderId,
                'label' => $order->get_formatted_billing_full_name()
            ));
        }

        if (count($orders) === 0) {
            return $orders;
        }

        $selectedOrder = isset($_REQUEST['order-id']) ? $_REQUEST['order-id'] : '';
        ?>
            <label for="filter-by-order-id" class="screen-reader-text">
                <span><?php _e('Filter by order', 'lmfwc'); ?></span>
            </label>
            <select name="order-id" id="filter-by-order-id">
                <option <?php selected($selectedOrder, ''); ?> value=""><?php _e('All orders', 'lmfwc'); ?></option>
                <?php
                foreach ($orders as $order) {
                    printf(
                        '<option%1$s value="%2$s">%3$s</option>',
                        selected($selectedOrder, $order['value'], false),
                        esc_attr($order['value']),
                        esc_html('#' . $order['value'] . ' ' . $order['label'])
                    );
                }
                ?>
            </select>
        <?php
    }

    /**
     * Displays the product dropdown filter.
     */
    public function productDropdown() {
        global $wpdb;

        $products = array();
        $results  = $wpdb->get_results(
            "SELECT DISTINCT `product_id` FROM {$this->table} WHERE `product_id` IS NOT NULL;",
            ARRAY_A
        );

        foreach ($results as $result) {
            if (!$productId = intval($result['product_id'])) {
                continue;
            }

            /** @var $product WC_Product */
            if (!$product = wc_get_product($productId)) {
                continue;
            }

            array_push(
                $products,
                array(
                    'value' => $productId,
                    'label' => sprintf('%s', $product->get_name())
                )
            );
        }

        if (count($products) === 0) {
            return $products;
        }

        $selectedProduct = isset($_REQUEST['product-id']) ? $_REQUEST['product-id'] : '';
        ?>
        <label for="filter-by-product-id" class="screen-reader-text">
            <span><?php _e('Filter by product', 'lmfwc'); ?></span>
        </label>
        <select name="product-id" id="filter-by-product-id">
            <option <?php selected($selectedProduct, ''); ?> value=""><?php _e('All products', 'lmfwc'); ?></option>
            <?php
            foreach ($products as $product) {
                printf(
                    '<option%1$s value="%2$s">%3$s</option>',
                    selected($selectedProduct, $product['value'], false),
                    esc_attr($product['value']),
                    esc_html('#' . $product['value'] . ' ' . $product['label'])
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Checkbox column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * License key column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_license_key($item)
    {
        if (Settings::get('lmfwc_hide_license_keys')) {
            $title = '<code class="lmfwc-placeholder empty"></code>';
            $title .= sprintf(
                '<img class="lmfwc-spinner" data-id="%d" src="%s">',
                $item['id'],
                self::SPINNER_URL
            );
        }

        else {
            $title = sprintf(
                '<code class="lmfwc-placeholder">%s</code>',
                apply_filters('lmfwc_decrypt', $item['license_key'])
            );
            $title .= sprintf(
                '<img class="lmfwc-spinner" data-id="%d" src="%s">',
                $item['id'],
                self::SPINNER_URL
            );
        }

        // ID
        $actions['id'] = sprintf(__('ID: %d', 'lmfwc'), intval($item['id']));

        // Edit
        $actions['edit'] = sprintf(
            '<a href="%s">%s</a>',
            admin_url(
                wp_nonce_url(
                    sprintf(
                        'admin.php?page=%s&action=edit&id=%d',
                        AdminMenus::LICENSES_PAGE,
                        intval($item['id'])
                    ),
                    'lmfwc_edit_license_key'
                )
            ),
            __('Edit', 'lmfwc')
        );

        // Hide/Show
        $actions['show'] = sprintf(
            '<a class="lmfwc-license-key-show" data-id="%d">%s</a>',
            $item['id'],
            __('Show', 'lmfwc')
        );
        $actions['hide'] = sprintf(
            '<a class="lmfwc-license-key-hide" data-id="%d">%s</a>',
            $item['id'],
            __('Hide', 'lmfwc')
        );

        // Activate, Deactivate
        if ($item['status'] != LicenseStatus::SOLD
            && $item['status'] != LicenseStatus::DELIVERED
        ) {
            if ($item['status'] != LicenseStatus::ACTIVE) {
                $actions['activate'] = sprintf(
                    '<a href="%s">%s</a>',
                    admin_url(
                        sprintf(
                            'admin.php?page=%s&action=activate&id=%d&_wpnonce=%s',
                            AdminMenus::LICENSES_PAGE,
                            intval($item['id']),
                            wp_create_nonce('activate')
                        )
                    ),
                    __('Activate', 'lmfwc')
                );
            }

            if ($item['status'] != LicenseStatus::INACTIVE) {
                $actions['deactivate'] = sprintf(
                    '<a href="%s">%s</a>',
                    admin_url(
                        sprintf(
                            'admin.php?page=%s&action=deactivate&id=%d&_wpnonce=%s',
                            AdminMenus::LICENSES_PAGE,
                            intval($item['id']),
                            wp_create_nonce('deactivate')
                        )
                    ),
                    __('Deactivate', 'lmfwc')
                );
            }
        }

        // Manage instances
        $actions['manage_instances'] = sprintf(
            '<a href="%s">%s</a>',
            admin_url(
				sprintf(
					'admin.php?page=%s&license_id=%d',
					AdminMenus::LICENSE_INSTANCES_PAGE,
					intval($item['id'])
                )
            ),
            __('Manage instances', 'lmfwc')
        );

        // Delete
        $actions['delete'] = sprintf(
            '<a href="%s">%s</a>',
            admin_url(
                sprintf(
                    'admin.php?page=%s&action=delete&id=%d&_wpnonce=%s',
                    AdminMenus::LICENSES_PAGE,
                    intval($item['id']),
                    wp_create_nonce('delete')
                )
            ),
            __('Delete', 'lmfwc')
        );

        return $title . $this->row_actions($actions);
    }

    /**
     * Order ID column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_order_id($item)
    {
        $html = '';

        if ($order = wc_get_order($item['order_id'])) {
            $html = sprintf(
                '<a href="%s" target="_blank">#%s</a>',
                get_edit_post_link($item['order_id']),
                $order->get_order_number()
            );
        }

        return $html;
    }

    /**
     * Product ID column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_product_id($item)
    {
        $html = '';

        /** @var WC_Product $product */
        if ($product = wc_get_product($item['product_id'])) {

            if ($parentId = $product->get_parent_id()) {
                $html = sprintf(
                    '<span>#%s - %s</span>',
                    $product->get_id(),
                    $product->get_name()
                );

                if ($parent = wc_get_product($parentId)) {
                    $html .= sprintf(
                        '<br><small>%s <a href="%s" target="_blank">#%s - %s</a></small>',
                        __('Variation of', 'lmfwc'),
                        get_edit_post_link($parent->get_id()),
                        $parent->get_id(),
                        $parent->get_name()
                    );
                }
            } else {
                $html = sprintf(
                    '<a href="%s" target="_blank">#%s - %s</a>',
                    get_edit_post_link($item['product_id']),
                    $product->get_id(),
                    $product->get_name()
                );
            }
        }

        return $html;
    }

    /**
     * Activation column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_activation($item)
    {
        $html = '';

        if ($item['times_activated_max'] === null) {
            $timesActivatedMax = null;
        } else {
            $timesActivatedMax = intval($item['times_activated_max']);
        }

        if ($item['times_activated'] === null) {
            $timesActivated = null;
        } else {
            $timesActivated = intval($item['times_activated']);
        }

        if ($timesActivatedMax === null) {
            return sprintf(
                '<div class="lmfwc-status %s"><small>%d</small> / <b>%s</b></div>',
                'activation done',
                intval($timesActivated),
                '&infin;'
            );
        }

        if ($timesActivated == $timesActivatedMax) {
            $icon = '<span class="dashicons dashicons-yes"></span>';
            $status = 'activation done';
        } else {
            $icon = '';
            $status = 'activation pending';
        }

        if ($timesActivated || $timesActivatedMax) {
            $html = sprintf(
                '<div class="lmfwc-status %s">%s <small>%d</small> / <b>%d</b></div>',
                $status,
                $icon,
                $timesActivated,
                $timesActivatedMax
            );
        }

        return $html;
    }

    /**
     * Created column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @throws Exception
     * @return string
     */
    public function column_created($item)
    {
        $html = '';

        if ($item['created_at']) {
            $offsetSeconds = floatval($this->gmtOffset) * 60 * 60;
            $timestamp     = strtotime($item['created_at']) + $offsetSeconds;
            $result        = date('Y-m-d H:i:s', $timestamp);
            $date          = new DateTime($result);

            $html .= sprintf(
                '<span>%s <b>%s, %s</b></span>',
                __('at', 'lmfwc'),
                $date->format($this->dateFormat),
                $date->format($this->timeFormat)
            );
        }

        if ($item['created_by']) {
            /** @var WP_User $user */
            $user = get_user_by('id', $item['created_by']);

            if ($user instanceof WP_User) {
                if (current_user_can('manage_options')) {
                    $html .= sprintf(
                        '<br>%s <a href="%s">%s</a>',
                        __('by', 'lmfwc'),
                        get_edit_user_link($user->ID),
                        $user->display_name
                    );
                }

                else {
                    $html .= sprintf(
                        '<br><span>%s %s</span>',
                        __('by', 'lmfwc'),
                        $user->display_name
                    );
                }
            }
        }

        return $html;
    }

    /**
     * Updated column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @throws Exception
     * @return string
     */
    public function column_updated($item)
    {
        $html = '';

        if ($item['updated_at']) {
            $offsetSeconds = floatval($this->gmtOffset) * 60 * 60;
            $timestamp     = strtotime($item['updated_at']) + $offsetSeconds;
            $result        = date('Y-m-d H:i:s', $timestamp);
            $date          = new DateTime($result);

            $html .= sprintf(
                '<span>%s <b>%s, %s</b></span>',
                __('at', 'lmfwc'),
                $date->format($this->dateFormat),
                $date->format($this->timeFormat)
            );
        }

        if ($item['updated_by']) {
            /** @var WP_User $user */
            $user = get_user_by('id', $item['updated_by']);

            if ($user instanceof WP_User) {
                if (current_user_can('manage_options')) {
                    $html .= sprintf(
                        '<br>%s <a href="%s">%s</a>',
                        __('by', 'lmfwc'),
                        get_edit_user_link($user->ID),
                        $user->display_name
                    );
                }

                else {
                    $html .= sprintf(
                        '<br><span>%s %s</span>',
                        __('by', 'lmfwc'),
                        $user->display_name
                    );
                }
            }
        }

        return $html;
    }

    /**
     * Expires at column.
     * 
     * @param array $item Associative array of column name and value pairs
     *
     * @throws Exception
     * @return string
     */
    public function column_expires_at($item)
    {
        if (!$item['expires_at']) {
            return '';
        }

        $offsetSeconds      = floatval($this->gmtOffset) * 60 * 60;
        $timestampExpiresAt = strtotime($item['expires_at']) + $offsetSeconds;
        $timestampNow       = strtotime('now') + $offsetSeconds;
        $datetimeString     = date('Y-m-d H:i:s', $timestampExpiresAt);
        $date               = new DateTime($datetimeString);

        if ($timestampNow > $timestampExpiresAt) {
            return sprintf(
                '<span class="lmfwc-date lmfwc-status expired" title="%s">%s, %s</span><br>',
                __('Expired'),
                $date->format($this->dateFormat),
                $date->format($this->timeFormat)
            );
        }

        return sprintf(
            '<span class="lmfwc-date lmfwc-status">%s, %s</span>',
            $date->format($this->dateFormat),
            $date->format($this->timeFormat)
        );
    }

    /**
     * Valid for column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_valid_for($item)
    {
        $html = '';

        if ($item['valid_for']) {
            $html = sprintf(
                '<b>%d</b> %s<br><small>%s</small>',
                intval($item['valid_for']),
                __('day(s)', 'lmfwc'),
                __('After purchase', 'lmfwc')
            );
        }

        return $html;
    }

    /**
     * Status column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_status($item)
    {
        switch ($item['status']) {
            case LicenseStatus::SOLD:
                $status = sprintf(
                    '<div class="lmfwc-status sold"><span class="dashicons dashicons-yes"></span> %s</div>',
                    __('Sold', 'lmfwc')
                );
                break;
            case LicenseStatus::DELIVERED:
                $status = sprintf(
                    '<div class="lmfwc-status delivered"><span class="lmfwc-icons delivered"></span> %s</div>',
                    __('Delivered', 'lmfwc')
                );
                break;
            case LicenseStatus::ACTIVE:
                $status = sprintf(
                    '<div class="lmfwc-status active"><span class="dashicons dashicons-marker"></span> %s</div>',
                    __('Active', 'lmfwc')
                );
                break;
            case LicenseStatus::INACTIVE:
                $status = sprintf(
                    '<div class="lmfwc-status inactive"><span class="dashicons dashicons-marker"></span> %s</div>',
                    __('Inactive', 'lmfwc')
                );
                break;
            default:
                $status = sprintf(
                    '<div class="lmfwc-status unknown">%s</div>',
                    __('Unknown', 'lmfwc')
                );
                break;
        }

        return $status;
    }

    /**
     * Default column value.
     * 
     * @param array  $item       Associative array of column name and value pairs
     * @param string $columnName Name of the current column
     * 
     * @return string
     */
    public function column_default($item, $columnName)
    {
        return $item[$columnName];
    }

    /**
     * Defines sortable columns and their sort value.
     * 
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortableColumns = array(
            'id'         => array('id', true),
            'order_id'   => array('order_id', true),
            'product_id' => array('product_id', true),
            'expires_at' => array('expires_at', true),
            'status'     => array('status', true),
            'created'    => array('created_at', true),
            'updated'    => array('updated_at', true),
            'activation' => array('times_activated_max', true)
        );

        return $sortableColumns;
    }

    /**
     * Defines items in the bulk action dropdown.
     * 
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = array(
            'activate'          => __('Activate', 'lmfwc'),
            'deactivate'        => __('Deactivate', 'lmfwc'),
            'mark_as_sold'      => __('Mark as sold', 'lmfwc'),
            'mark_as_delivered' => __('Mark as delivered', 'lmfwc'),
            'delete'            => __('Delete', 'lmfwc'),
            'export_csv'        => __('Export (CSV)', 'lmfwc'),
            'export_pdf'        => __('Export (PDF)', 'lmfwc')
        );

        return $actions;
    }

    /**
     * Processes the currently selected action.
     */
    private function processBulkActions()
    {
        $action = $this->current_action();

        switch ($action) {
            case 'activate':
                $this->toggleLicenseKeyStatus(LicenseStatus::ACTIVE);
                break;
            case 'deactivate':
                $this->toggleLicenseKeyStatus(LicenseStatus::INACTIVE);
                break;
            case 'mark_as_sold':
                $this->toggleLicenseKeyStatus(LicenseStatus::SOLD);
                break;
            case 'mark_as_delivered':
                $this->toggleLicenseKeyStatus(LicenseStatus::DELIVERED);
                break;
            case 'delete':
                $this->deleteLicenseKeys();
                break;
            case 'export_pdf':
                $this->exportLicenseKeys('PDF');
                break;
            case 'export_csv':
                $this->exportLicenseKeys('CSV');
                break;
            default:
                break;
        }
    }

    /**
     * Initialization function.
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        $this->processBulkActions();

        $perPage     = $this->get_items_per_page('lmfwc_licenses_per_page', 10);
        $currentPage = $this->get_pagenum();
        $totalItems  = $this->getLicenseKeyCount();

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalItems / $perPage)
        ));

        $this->items = $this->getLicenseKeys($perPage, $currentPage);
    }

    /**
     * Retrieves the licenses from the database.
     * 
     * @param int $perPage    Default amount of licenses per page
     * @param int $pageNumber Default page number
     * 
     * @return array
     */
    private function getLicenseKeys($perPage = 20, $pageNumber = 1)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table} WHERE 1 = 1";

        // Applies the view filter
        if ($this->isViewFilterActive()) {
            $sql .= $wpdb->prepare(' AND status = %d', intval($_GET['status']));
        }

        // Applies the search box filter
        if (array_key_exists('s', $_REQUEST) && $_REQUEST['s']) {
            $sql .= $wpdb->prepare(
                ' AND hash = %s',
                apply_filters('lmfwc_hash', sanitize_text_field($_REQUEST['s']))
            );
        }

        // Applies the order filter
        if (isset($_REQUEST['order-id']) && is_numeric($_REQUEST['order-id'])) {
            $sql .= $wpdb->prepare(' AND order_id = %d', intval($_REQUEST['order-id']));
        }

        // Applies the product filter
        if (isset($_REQUEST['product-id']) && is_numeric($_REQUEST['product-id'])) {
            $sql .= $wpdb->prepare(' AND product_id = %d', intval($_REQUEST['product-id']));
        }

        $sql .= ' ORDER BY ' . (empty($_REQUEST['orderby']) ? 'id' : esc_sql($_REQUEST['orderby']));
        $sql .= ' '          . (empty($_REQUEST['order'])   ? 'DESC'  : esc_sql($_REQUEST['order']));
        $sql .= " LIMIT {$perPage}";
        $sql .= ' OFFSET ' . ($pageNumber - 1) * $perPage;

        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results;
    }

    /**
     * Retrieves the license key table row count.
     * 
     * @return int
     */
    private function getLicenseKeyCount()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1 = 1";

        if ($this->isViewFilterActive()) {
            $sql .= $wpdb->prepare(' AND status = %d', intval($_GET['status']));
        }

        if (isset($_REQUEST['order-id'])) {
            $sql .= $wpdb->prepare(' AND order_id = %d', intval($_REQUEST['order-id']));
        }

        if (array_key_exists('s', $_REQUEST) && $_REQUEST['s']) {
            $sql .= $wpdb->prepare(
                ' AND hash = %s',
                apply_filters('lmfwc_hash', sanitize_text_field($_REQUEST['s']))
            );
        }

        return $wpdb->get_var($sql);
    }

    /**
     * Output in case no items exist.
     */
    public function no_items()
    {
        _e('No license keys found.', 'lmfwc');
    }

    /**
     * Set the table columns.
     */
    public function get_columns()
    {
        $columns = array(
            'cb'          => '<input type="checkbox" />',
            'license_key' => __('License key', 'lmfwc'),
            'order_id'    => __('Order', 'lmfwc'),
            'product_id'  => __('Product', 'lmfwc'),
            'activation'  => __('Activation', 'lmfwc'),
            'expires_at'  => __('Expires at', 'lmfwc'),
            'valid_for'   => __('Valid for', 'lmfwc'),
            'status'      => __('Status', 'lmfwc'),
            'created'     => __('Created', 'lmfwc'),
            'updated'     => __('Updated', 'lmfwc')
        );

        return $columns;
    }

    /**
     * Checks if the given nonce is (still) valid.
     *
     * @param string $nonce The nonce to check
     * @throws Exception
     */
    private function verifyNonce($nonce)
    {
        $currentNonce = $_REQUEST['_wpnonce'];

        if (!wp_verify_nonce($currentNonce, $nonce)
            && !wp_verify_nonce($currentNonce, 'bulk-' . $this->_args['plural'])
        ) {
            AdminNotice::error(__('The nonce is invalid or has expired.', 'lmfwc'));
            wp_redirect(
                admin_url(sprintf('admin.php?page=%s', AdminMenus::LICENSES_PAGE))
            );

            exit();
        }
    }

    /**
     * Makes sure that license keys were selected for the bulk action.
     */
    private function verifySelection()
    {
        // No ID's were selected, show a warning and redirect
        if (!array_key_exists('id', $_REQUEST)) {
            $message = sprintf(esc_html__('No license keys were selected.', 'lmfwc'));
            AdminNotice::warning($message);

            wp_redirect(
                admin_url(
                    sprintf('admin.php?page=%s', AdminMenus::LICENSES_PAGE)
                )
            );

            exit();
        }
    }

    /**
     * Changes the license key status
     *
     * @param int $status
     * @throws Exception
     */
    private function toggleLicenseKeyStatus($status)
    {
        switch ($status) {
            case LicenseStatus::SOLD:
                $nonce = 'sell';
                break;
            case LicenseStatus::DELIVERED:
                $nonce = 'deliver';
                break;
            case LicenseStatus::ACTIVE:
                $nonce = 'activate';
                break;
            default:
                $nonce = 'deactivate';
                break;
        }

        $this->verifyNonce($nonce);
        $this->verifySelection();

        $licenseKeyIds = (array)$_REQUEST['id'];
        $count         = 0;

        foreach ($licenseKeyIds as $licenseKeyId) {
            LicenseResourceRepository::instance()->update($licenseKeyId, array('status' => $status));
            $count++;
        }

        // Set the admin notice, redirect and exit
        AdminNotice::success(sprintf(esc_html__('%d license key(s) updated successfully.', 'lmfwc'), $count));
        wp_redirect(admin_url(sprintf('admin.php?page=%s', AdminMenus::LICENSES_PAGE)));
        exit();
    }

    /**
     * Removes the license key(s) permanently from the database.
     *
     * @throws Exception
     */
    private function deleteLicenseKeys()
    {
        $this->verifyNonce('delete');
        $this->verifySelection();

        $result = LicenseResourceRepository::instance()->deleteBy(array('id' => (array)($_REQUEST['id'])));

        $message = sprintf(esc_html__('%d license key(s) permanently deleted.', 'lmfwc'), $result);

        // Set the admin notice
        AdminNotice::success($message);

        // Redirect and exit
        wp_redirect(
            admin_url(
                sprintf('admin.php?page=%s', AdminMenus::LICENSES_PAGE)
            )
        );

        exit();
    }

    /**
     * Initiates a file download of the exported licenses (PDF or CSV).
     *
     * @param string $type
     * @throws Exception
     */
    private function exportLicenseKeys($type)
    {
        $this->verifySelection();

        if ($type === 'PDF') {
            $this->verifyNonce('export_pdf');
            do_action('lmfwc_export_license_keys_pdf', (array)$_REQUEST['id']);
        }

        if ($type === 'CSV') {
            $this->verifyNonce('export_csv');
            do_action('lmfwc_export_license_keys_csv', (array)$_REQUEST['id']);
        }
    }

    /**
     * Checks if there are currently any license view filters active.
     * 
     * @return bool
     */
    private function isViewFilterActive()
    {
        if (array_key_exists('status', $_GET)
            && in_array($_GET['status'], LicenseStatus::$status)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Displays the search box.
     *
     * @param string $text
     * @param string $inputId
     */
    public function search_box($text, $inputId)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $inputId     = $inputId . '-search-input';
        $searchQuery = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';

        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="' . esc_attr( $inputId ) . '">' . esc_html( $text ) . ':</label>';
        echo '<input type="search" id="' . esc_attr($inputId) . '" name="s" value="' . esc_attr($searchQuery) . '" />';

        submit_button(
            $text, '', '', false,
            array(
                'id' => 'search-submit',
            )
        );

        echo '</p>';
    }
}