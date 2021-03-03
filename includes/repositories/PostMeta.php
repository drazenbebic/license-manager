<?php

namespace LicenseManagerForWooCommerce\Repositories;

use Exception;

defined('ABSPATH') || exit;

class PostMeta
{
    /**
     * Adds all filters for interaction with the database table.
     */
    public function __construct()
    {
        add_filter('lmfwc_get_assigned_products', array($this, 'getAssignedProducts'), 10, 1);
    }

    /**
     * Retrieve assigned products for a specific generator.
     *
     * @param int $generatorId
     *
     * @return array
     * @throws Exception
     */
    public function getAssignedProducts($generatorId)
    {
        $cleanGeneratorId = $generatorId ? absint($generatorId) : null;

        if (!$cleanGeneratorId) {
            throw new Exception('Generator ID is invalid.');
        }

        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                    SELECT
                        post_id
                    FROM
                        {$wpdb->postmeta}
                    WHERE
                        1 = 1
                        AND meta_key = %s
                        AND meta_value = %d
                ",
                'lmfwc_licensed_product_assigned_generator',
                $cleanGeneratorId
            ),
            OBJECT
        );

        if ($results) {
            $products = [];

            foreach ($results as $row) {
                if (!$product = wc_get_product($row->post_id)) {
                    continue;
                }

                $products[] = $product;
            }
        } else {
            $products = [];
        }

        return $products;
    }

}