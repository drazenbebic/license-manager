<?php

namespace LicenseManagerForWooCommerce\Lists;

use DateTime;
use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use LicenseManagerForWooCommerce\Repositories\Resources\LicenseInstance as LicenseInstanceResourceRepository;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use LicenseManagerForWooCommerce\Settings;
use LicenseManagerForWooCommerce\Setup;
use WP_List_Table;
use WP_User;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class LicenseInstancesList extends WP_List_Table
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
    protected $tableJoin;

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
                'singular' => __('License instance key', 'lmfwc'),
                'plural'   => __('License instance keys', 'lmfwc'),
                'ajax'     => false
            )
        );

        $this->table      = $wpdb->prefix . Setup::LICENSE_INSTANCES_TABLE_NAME;
        $this->tableJoin  = $wpdb->prefix . Setup::LICENSES_TABLE_NAME;
        $this->dateFormat = get_option('date_format');
        $this->timeFormat = get_option('time_format');
        $this->gmtOffset  = get_option('gmt_offset');
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
                $item['license_id'],
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
                $item['license_id'],
                self::SPINNER_URL
            );
        }

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

        // Filter by license key
        $actions['filter_key'] = sprintf(
            '<a href="%s">%s</a>',
            admin_url(
				sprintf(
					'admin.php?page=%s&license_id=%d',
					AdminMenus::LICENSE_INSTANCES_PAGE,
					intval($item['license_id'])
                )
            ),
            __('List only this key', 'lmfwc')
        );

        return $title . $this->row_actions($actions);
    }

    /**
     * License instance key column.
     * 
     * @param array $item Associative array of column name and value pairs
     * 
     * @return string
     */
    public function column_instance_key($item)
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
                apply_filters('lmfwc_decrypt', $item['instance_key'])
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
                sprintf(
                    'admin.php?page=%s&action=edit&id=%d',
                    AdminMenus::LICENSE_INSTANCES_PAGE,
                    intval($item['id'])
                ),
                'lmfwc_edit_license_instance_key'
            ),
            __('Edit', 'lmfwc')
        );

        // Hide/Show
        $actions['show'] = sprintf(
            '<a class="lmfwc-license-instance-key-show" data-id="%d">%s</a>',
            $item['id'],
            __('Show', 'lmfwc')
        );
        $actions['hide'] = sprintf(
            '<a class="lmfwc-license-instance-key-hide" data-id="%d">%s</a>',
            $item['id'],
            __('Hide', 'lmfwc')
        );

        // Deactivate
        $actions['deactivate'] = sprintf(
            '<a href="%s">%s</a>',
            admin_url(
                sprintf(
                    'admin.php?page=%s&action=deactivate&id=%d&_wpnonce=%s',
                    AdminMenus::LICENSE_INSTANCES_PAGE,
                    intval($item['id']),
                    wp_create_nonce('deactivate')
                )
            ),
            __('Deactivate', 'lmfwc')
        );

        return $title . $this->row_actions($actions);
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
            'id'          => array('id', true),
            'license_key' => array('license_key', true),
            'created'     => array('created_at', true),
            'updated'     => array('updated_at', true)
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
            'deactivate' => __('Deactivate', 'lmfwc')
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
            case 'deactivate':
                $this->deactivateLicenseInstanceKeys();
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

        $perPage     = $this->get_items_per_page('lmfwc_license_instances_per_page', 10);
        $currentPage = $this->get_pagenum();
        $totalItems  = $this->getLicenseInstanceKeyCount();

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalItems / $perPage)
        ));

        $this->items = $this->getLicenseInstanceKeys($perPage, $currentPage);
    }

    /**
     * Retrieves the licenses from the database.
     * 
     * @param int $perPage    Default amount of licenses per page
     * @param int $pageNumber Default page number
     * 
     * @return array
     */
    private function getLicenseInstanceKeys($perPage = 20, $pageNumber = 1)
    {
        global $wpdb;

        $sql = "SELECT li.*, l.license_key FROM {$this->table} as li inner join {$this->tableJoin} as l on (li.license_id=l.id) WHERE 1 = 1";

        // Applies the search box filter
        if (array_key_exists('s', $_REQUEST) && $_REQUEST['s']) {
            $sql .= $wpdb->prepare(
                ' AND instance_hash = %s',
                apply_filters('lmfwc_hash', sanitize_text_field($_REQUEST['s']))
            );
        }

        if (array_key_exists('license_id', $_REQUEST) && $_REQUEST['license_id']) {
            $sql .= $wpdb->prepare(
                ' AND license_id = %d',
                absint($_REQUEST['license_id'])
            );
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
    private function getLicenseInstanceKeyCount()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1 = 1";

        if (array_key_exists('s', $_REQUEST) && $_REQUEST['s']) {
            $sql .= $wpdb->prepare(
                ' AND instance_hash = %s',
                apply_filters('lmfwc_hash', sanitize_text_field($_REQUEST['s']))
            );
        }

        if (array_key_exists('license_id', $_REQUEST) && $_REQUEST['license_id']) {
            $sql .= $wpdb->prepare(
                ' AND license_id = %d',
                absint($_REQUEST['license_id'])
            );
        }

        return $wpdb->get_var($sql);
    }

    /**
     * Output in case no items exist.
     */
    public function no_items()
    {
        _e('No license instance keys found.', 'lmfwc');
    }

    /**
     * Set the table columns.
     */
    public function get_columns()
    {
        $columns = array(
            'cb'           => '<input type="checkbox" />',
            'instance_key' => __('Instance key', 'lmfwc'),
            'license_key'  => __('License key', 'lmfwc'),
            'created'      => __('Created', 'lmfwc'),
            'updated'      => __('Updated', 'lmfwc')
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
                admin_url(sprintf('admin.php?page=%s', AdminMenus::LICENSE_INSTANCES_PAGE))
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
            $message = sprintf(esc_html__('No license instance keys were selected.', 'lmfwc'));
            AdminNotice::warning($message);

            wp_redirect(
                admin_url(
                    sprintf('admin.php?page=%s', AdminMenus::LICENSE_INSTANCES_PAGE)
                )
            );

            exit();
        }
    }

    /**
     * Removes the license key(s) permanently from the database.
     *
     * @throws Exception
     */
    private function deactivateLicenseInstanceKeys()
    {
        $this->verifyNonce('deactivate');
        $this->verifySelection();

		$num_deactivated = 0;
		$ids = (array)($_REQUEST['id']);
		foreach( $ids as $licenseInstanceId ) {
			/** @var LicenseInstanceResourceRepository $licenseInstance */
			$licenseInstance = LicenseInstanceResourceRepository::instance()->find($licenseInstanceId);
			if ( $licenseInstance ) {
				$licenseId = $licenseInstance->getLicenseID();

				/** @var LicenseResourceModel $license */
				$license = LicenseResourceRepository::instance()->find(absint($licenseId));
				if ($license) {
					// check activated times if license key has been changed
					$timesActivated    = absint( $license->getTimesActivated() );
					
					LicenseResourceRepository::instance()->update(
						$licenseId,
						array(
							"times_activated" => max(0, $timesActivated-1)
						)
					);
					
					LicenseInstanceResourceRepository::instance()->deleteBy(array('id' => (array)($licenseInstanceId)));
					
					$num_deactivated++;
				}
			}
		}

        $message = sprintf(esc_html__('%d license instance key(s) deactivated.', 'lmfwc'), $num_deactivated);

        // Set the admin notice
        AdminNotice::success($message);

        // Redirect and exit
        wp_redirect(
            admin_url(
                sprintf('admin.php?page=%s', AdminMenus::LICENSE_INSTANCES_PAGE)
            )
        );

        exit();
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