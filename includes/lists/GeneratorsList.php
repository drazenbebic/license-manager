<?php

namespace LicenseManagerForWooCommerce\Lists;

use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Repositories\Resources\Generator as GeneratorResourceRepository;
use LicenseManagerForWooCommerce\Setup;
use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class GeneratorsList extends WP_List_Table
{
    /**
     * @var string
     */
    protected $table;

    /**
     * GeneratorsList constructor.
     */
    public function __construct()
    {
        global $wpdb;

        parent::__construct(
            array(
                'singular' => __('Generator', 'license-manager-for-woocommerce'),
                'plural'   => __('Generators', 'license-manager-for-woocommerce'),
                'ajax'     => false
            )
        );

        $this->table = $wpdb->prefix . Setup::GENERATORS_TABLE_NAME;
    }

    /**
     * Retrieves the generators from the database.
     * 
     * @param int $perPage    Default amount of generators per page
     * @param int $pageNumber Default page number
     * 
     * @return array
     */
    public function getGenerators($perPage = 20, $pageNumber = 1)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table}";
        $sql .= ' ORDER BY ' . (empty($_REQUEST['orderby']) ? 'id'   : esc_sql($_REQUEST['orderby']));
        $sql .= ' '          . (empty($_REQUEST['order'])   ? 'DESC' : esc_sql($_REQUEST['order']));
        $sql .= " LIMIT {$perPage}";
        $sql .= ' OFFSET ' . ($pageNumber - 1) * $perPage;

        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results;
    }

    /**
     * Retrieves the generator table row count.
     * 
     * @return int
     */
    private function getGeneratorCount()
    {
        global $wpdb;

        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }

    /**
     * Output in case no items exist.
     */
    public function no_items()
    {
        _e('No generators found.', 'license-manager-for-woocommerce');
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
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    /**
     * Name column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_name($item)
    {
        $products = apply_filters('lmfwc_get_assigned_products', $item['id']);
        $actions  = array();
        $title    = '<strong>' . $item['name'] . '</strong>';

        if (count($products) > 0) {
            $title .= sprintf(
                '<span class="lmfwc-badge info" title="%s">%d</span>',
                __('Number of products assigned to this generator', 'license-manager-for-woocommerce'),
                count($products)
            );
        }

        $actions['id'] = sprintf(__('ID: %d', 'license-manager-for-woocommerce'), intval($item['id']));

        if (!apply_filters('lmfwc_get_assigned_products', $item['id'])) {
            $actions['delete'] = sprintf(
                '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">%s</a>',
                AdminMenus::GENERATORS_PAGE,
                'delete',
                absint($item['id']),
                wp_create_nonce('delete'),
                __('Delete', 'license-manager-for-woocommerce')
            );
        }

        $actions['edit'] = sprintf(
            '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">%s</a>',
            AdminMenus::GENERATORS_PAGE,
            'edit',
            absint($item['id']),
            wp_create_nonce('edit'),
            __('Edit', 'license-manager-for-woocommerce')
        );

        return $title . $this->row_actions($actions);
    }

    /**
     * Character map column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_charset($item)
    {
        $charset = '';

        if ($item['charset']) {
            $charset = sprintf('<code>%s</code>', $item['charset']);
        }

        return $charset;
    }

    /**
     * Separator column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_separator($item)
    {
        $separator = '';

        if ($item['separator']) {
            $separator = sprintf('<code>%s</code>', $item['separator']);
        }

        return $separator;
    }

    /**
     * Prefix column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_prefix($item)
    {
        $prefix = '';

        if ($item['prefix']) {
            $prefix = sprintf('<code>%s</code>', $item['prefix']);
        }

        return $prefix;
    }

    /**
     * Suffix column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_suffix($item)
    {
        $suffix = '';

        if ($item['suffix']) {
            $suffix = sprintf('<code>%s</code>', $item['suffix']);
        }

        return $suffix;
    }

    /**
     * Expires in column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_expires_in($item)
    {
        $expiresIn = '';

        if (!$item['expires_in']) {
            return $expiresIn;
        }

        $expiresIn .= sprintf('%d %s', $item['expires_in'], __('day(s)', 'license-manager-for-woocommerce'));
        $expiresIn .= '<br>';
        $expiresIn .= sprintf('<small>%s</small>', __('After purchase', 'license-manager-for-woocommerce'));

        return $expiresIn;
    }

    /**
     * Default column value.
     * 
     * @param array  $item        Associative array of column name and value pairs
     * @param string $column_name Name of the current column
     * 
     * @return string
     */
    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     * Set the table columns.
     */
    public function get_columns()
    {
        return array(
            'cb'                  => '<input type="checkbox" />',
            'name'                => __('Name', 'license-manager-for-woocommerce'),
            'charset'             => __('Character map', 'license-manager-for-woocommerce'),
            'chunks'              => __('Number of chunks', 'license-manager-for-woocommerce'),
            'chunk_length'        => __('Chunk length', 'license-manager-for-woocommerce'),
            'times_activated_max' => __('Maximum activation count', 'license-manager-for-woocommerce'),
            'separator'           => __('Separator', 'license-manager-for-woocommerce'),
            'prefix'              => __('Prefix', 'license-manager-for-woocommerce'),
            'suffix'              => __('Suffix', 'license-manager-for-woocommerce'),
            'expires_in'          => __('Expires in', 'license-manager-for-woocommerce')
        );
    }

    /**
     * Defines sortable columns and their sort value.
     * 
     * @return array
     */
    public function get_sortable_columns()
    {
        return array(
            'name'                => array('name', true),
            'charset'             => array('charset', true),
            'chunks'              => array('chunks', true),
            'chunk_length'        => array('chunk_length', true),
            'times_activated_max' => array('times_activated_max', true),
            'expires_in'          => array('expires_in', true),
        );
    }

    /**
     * Defines items in the bulk action dropdown.
     * 
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => __('Delete', 'license-manager-for-woocommerce'),
        );

        return $actions;
    }

    /**
     * Handle bulk action requests.
     *
     * @throws Exception
     */
    private function processBulkActions()
    {
        $action = $this->current_action();

        switch ($action) {
            case 'delete':
                $this->verifyNonce('delete');
                $this->verifySelection();
                $this->deleteGenerators();
                break;
            default:
                break;
        }
    }

    /**
     * Initialization function.
     *
     * @throws Exception
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        $this->processBulkActions();

        $perPage     = $this->get_items_per_page('generators_per_page', 10);
        $currentPage = $this->get_pagenum();
        $totalItems  = $this->getGeneratorCount();

        $this->set_pagination_args(
            array(
                'total_items' => $totalItems,
                'per_page'    => $perPage,
                'total_pages' => ceil($totalItems / $perPage)
            )
        );

        $this->items = $this->getGenerators($perPage, $currentPage);
    }

    /**
     * Checks if the given nonce is valid.
     *
     * @param string $nonceAction The nonce to check
     *
     * @throws Exception
     */
    private function verifyNonce($nonceAction)
    {
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], $nonceAction) &&
            !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])
        ) {
            AdminNotice::error(__('The nonce is invalid or has expired.', 'license-manager-for-woocommerce'));
            wp_redirect(admin_url(sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE)));

            exit();
        }
    }

    /**
     * Makes sure that generators were selected for the bulk action.
     */
    private function verifySelection()
    {
        // No ID's were selected, show a warning and redirect
        if (!array_key_exists('id', $_REQUEST)) {
            $message = sprintf(esc_html__('No generators were selected.', 'license-manager-for-woocommerce'));
            AdminNotice::warning($message);

            wp_redirect(
                admin_url(
                    sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE)
                )
            );

            exit();
        }
    }

    /**
     * Bulk deletes the generators from the table by a single ID or an array of ID's.
     *
     * @throws Exception
     */
    private function deleteGenerators()
    {
        $selectedGenerators = (array)$_REQUEST['id'];
        $generatorsToDelete = array();

        foreach ($selectedGenerators as $generatorId) {
            if ($products = apply_filters('lmfwc_get_assigned_products', $generatorId)) {
                continue;
            } else {
                array_push($generatorsToDelete, $generatorId);
            }
        }

        $result = GeneratorResourceRepository::instance()->delete($generatorsToDelete);

        if ($result) {
            AdminNotice::success(sprintf(__('%d generator(s) permanently deleted.', 'license-manager-for-woocommerce'), $result));

            wp_redirect(
                admin_url(
                    sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE)
                )
            );
        }

        else {
            AdminNotice::error(__('There was a problem deleting the generators.', 'license-manager-for-woocommerce'));

            wp_redirect(
                admin_url(
                    sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE)
                )
            );
        }
    }
}
