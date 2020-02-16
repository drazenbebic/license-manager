<?php

namespace LicenseManagerForWooCommerce\Lists;

use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Repositories\Resources\ApiKey as ApiKeyResourceRepository;
use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class APIKeyList extends WP_List_Table
{
    /**
     * Class constructor.
     */
    public function __construct() {
        parent::__construct(
            array(
                'singular' => __('Key', 'license-manager-for-woocommerce'),
                'plural'   => __('Keys', 'license-manager-for-woocommerce'),
                'ajax'     => false
            )
        );
    }

    /**
     * No items found text.
     */
    public function no_items()
    {
        _e('No keys found.', 'license-manager-for-woocommerce');
    }

    /**
     * Get list columns.
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'cb'            => '<input type="checkbox" />',
            'title'         => __('Description', 'license-manager-for-woocommerce'),
            'truncated_key' => __('Consumer key ending in', 'license-manager-for-woocommerce'),
            'user'          => __('User', 'license-manager-for-woocommerce'),
            'permissions'   => __('Permissions', 'license-manager-for-woocommerce'),
            'last_access'   => __('Last access', 'license-manager-for-woocommerce'),
        );
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
        return sprintf('<input type="checkbox" name="key[]" value="%1$s" />', $item['id']);
    }

    /**
     * Title column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @return string
     */
    public function column_title($item)
    {
        $keyId  = intval($item['id']);
        $url    = admin_url(sprintf('admin.php?page=%s&tab=rest_api&edit_key=%d', AdminMenus::SETTINGS_PAGE, $keyId));
        $userId = intval($item['user_id']);

        // Check if current user can edit other users or if it's the same user.
        $canEdit = current_user_can('edit_user', $userId) || get_current_user_id() === $userId;

        $output = '<strong>';

        if ($canEdit) {
            $output .= '<a href="' . esc_url($url) . '" class="row-title">';
        }

        if (empty($item['description'])) {
            $output .= esc_html__('API key', 'license-manager-for-woocommerce');
        }

        else {
            $output .= esc_html($item['description']);
        }

        if ($canEdit) {
            $output .= '</a>';
        }

        $output .= '</strong>';

        // Get actions.
        $actions = array(
            'id' => sprintf(__('ID: %d', 'license-manager-for-woocommerce'), $keyId),
        );

        if ($canEdit) {
            $actions['edit']  = '<a href="' . esc_url($url) . '">' . __('View/Edit', 'license-manager-for-woocommerce') . '</a>';
            $actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__('Revoke API key', 'license-manager-for-woocommerce') . '" href="' . esc_url(
                wp_nonce_url(
                    add_query_arg(
                        array(
                            'action' => 'revoke',
                            'key' => $keyId,
                        ),
                        admin_url(sprintf('admin.php?page=%s&tab=rest_api', AdminMenus::SETTINGS_PAGE))
                    ),
                    'revoke'
                )
            ) . '">' . esc_html__('Revoke', 'license-manager-for-woocommerce') . '</a>';
        }

        $rowActions = array();

        foreach ($actions as $action => $link) {
            $rowActions[] = '<span class="' . esc_attr($action) . '">' . $link . '</span>';
        }

        $output .= '<div class="row-actions">' . implode(' | ', $rowActions) . '</div>';

        return $output;
    }

    /**
     * Truncated consumer key column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @return string
     */
    public function column_truncated_key($item)
    {
        return '<code>&hellip;' . esc_html($item['truncated_key']) . '</code>';
    }

    /**
     * User column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @return string
     */
    public function column_user($item)
    {
        $user = get_user_by('id', $item['user_id']);

        if (!$user) {
            return '';
        }

        if (current_user_can('edit_user', $user->ID)) {
            return '<a href="' . esc_url(add_query_arg(array('user_id' => $user->ID), admin_url('user-edit.php'))) . '">' . esc_html($user->display_name) . '</a>';
        }

        return esc_html($user->display_name);
    }

    /**
     * Permissions column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @return string
     */
    public function column_permissions($item)
    {
        $permissionKey = $item['permissions'];
        $permissions = array(
            'read'       => __('Read', 'license-manager-for-woocommerce'),
            'write'      => __('Write', 'license-manager-for-woocommerce'),
            'read_write' => __('Read/Write', 'license-manager-for-woocommerce'),
        );

        if (isset($permissions[$permissionKey])) {
            return esc_html($permissions[$permissionKey]);
        }

        return '';
    }

    /**
     * Last access column.
     *
     * @param array $item Associative array of column name and value pairs
     *
     * @return string
     */
    public function column_last_access($item)
    {
        if (!empty($item['last_access'])) {
            $date = sprintf(
                __('%1$s at %2$s', 'license-manager-for-woocommerce'),
                date_i18n(wc_date_format(), strtotime($item['last_access'])),
                date_i18n(wc_time_format(), strtotime($item['last_access']))
            );

            return apply_filters('woocommerce_api_key_last_access_datetime', $date, $item['last_access']);
        }

        return __('Unknown', 'license-manager-for-woocommerce');
    }

    /**
     * Defines items in the bulk action dropdown.
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        if (!current_user_can('remove_users')) {
            return array();
        }

        return array(
            'revoke' => __('Revoke', 'license-manager-for-woocommerce'),
        );
    }

    /**
     * Handle bulk action requests.
     *
     * @throws Exception
     */
    private function processBulkActions()
    {
        if (!$action = $this->current_action()) {
            return;
        }

        if (!current_user_can('remove_users')) {
            return;
        }

        switch ($action) {
            case 'revoke':
                $this->verifyNonce('revoke');
                $this->verifySelection();
                $this->revokeKeys();
                break;
            default:
                break;
        }
    }

    /**
     * Search box.
     *
     * @param string $text    Button text
     * @param string $inputId Input ID
     */
    public function search_box($text, $inputId)
    {
        if (empty($_REQUEST['s']) && ! $this->has_items()) {
            return;
        }

        $inputId     = $inputId . '-search-input';
        $searchQuery = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';

        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="' . esc_attr($inputId) . '">' . esc_html($text) . ':</label>';
        echo '<input type="search" id="' . esc_attr($inputId) . '" name="s" value="' . esc_attr($searchQuery) . '" />';

        submit_button(
            $text, '', '', false,
            array(
                'id' => 'search-submit',
            )
        );

        echo '</p>';
    }

    /**
     * Prepare table list items.
     */
    public function prepare_items()
    {
        global $wpdb;

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        $this->processBulkActions();

        $perPage     = $this->get_items_per_page('lmfwc_keys_per_page');
        $currentPage = $this->get_pagenum();
        $offset      = 0;

        if (1 < $currentPage) {
            $offset = $perPage * ($currentPage - 1);
        }

        $search = '';

        if (!empty($_REQUEST['s'])) {
            $search = "AND description LIKE '%" . esc_sql($wpdb->esc_like(wc_clean(wp_unslash($_REQUEST['s'])))) . "%' ";
        }

        // Get the API keys.
        $keys = $wpdb->get_results("
            SELECT
                id, user_id, description, permissions, truncated_key, last_access
            FROM
                {$wpdb->prefix}lmfwc_api_keys
            WHERE
                1=1
                {$search}"
            . $wpdb->prepare('ORDER BY id DESC LIMIT %d OFFSET %d;', $perPage, $offset), ARRAY_A
        );

        $count = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}lmfwc_api_keys WHERE 1 = 1 {$search};");

        $this->items = $keys;

        // Set the pagination.
        $this->set_pagination_args(
            array(
                'total_items' => $count,
                'per_page'    => $perPage,
                'total_pages' => ceil($count / $perPage),
            )
        );
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
        if (
            !wp_verify_nonce($_REQUEST['_wpnonce'], $nonceAction) &&
            !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])
        ) {
            AdminNotice::error(__('The nonce is invalid or has expired.', 'license-manager-for-woocommerce'));
            wp_redirect(
                admin_url(sprintf('admin.php?page=%s', AdminMenus::GENERATORS_PAGE))
            );

            exit();
        }
    }

    /**
     * Makes sure that generators were selected for the bulk action.
     */
    private function verifySelection()
    {
        // No ID's were selected, show a warning and redirect
        if (!array_key_exists('key', $_REQUEST)) {
            $message = sprintf(esc_html__('No API keys were selected.', 'license-manager-for-woocommerce'));
            AdminNotice::warning($message);

            wp_redirect(
                admin_url(
                    sprintf('admin.php?page=%s&tab=rest_api', AdminMenus::SETTINGS_PAGE)
                )
            );

            exit();
        }
    }

    /**
     * Permanently deletes API keys from the table.
     *
     * @throws Exception
     */
    private function revokeKeys()
    {
        if ($count = ApiKeyResourceRepository::instance()->delete((array)$_REQUEST['key'])) {
            AdminNotice::success(sprintf(__('%d API key(s) permanently revoked.', 'license-manager-for-woocommerce'), $count));
        }

        else {
            AdminNotice::error(__('There was a problem revoking the API key(s).', 'license-manager-for-woocommerce'));
        }

        wp_redirect(sprintf('admin.php?page=%s&tab=rest_api', AdminMenus::SETTINGS_PAGE));
        exit();
    }

}