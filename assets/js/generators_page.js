jQuery(function($) {
    'use strict';

    let generateLicenseKeysProduct = $('select#generate__product');
    let generateLicenseKeysOrder   = $('select#generate__order');

    const productDropdownSearchConfig = {
        ajax: {
            cache: true,
            delay: 500,
            url: ajaxurl,
            method: 'GET',
            dataType: 'json',
            data: function(params) {
                return {
                    action: 'woocommerce_json_search_products_and_variations',
                    security: security.productSearch,
                    term: params.term
                };
            },
            processResults: function(data) {
                let terms = [];

                if (data) {
                    $.each(data, function(id, text) {
                        terms.push({ id: id, text: text });
                    });
                }

                return {
                    results: terms
                };
            }
        },
        placeholder: i18n.placeholderSearchProducts,
        minimumInputLength: 1,
        allowClear: true
    };
    const orderDropdownSearchConfig = {
        ajax: {
            cache: true,
            delay: 500,
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: function(params) {
                return {
                    action: 'lmfwc_dropdown_order_search',
                    security: security.orderSearch,
                    term: params.term,
                    page: params.page
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;

                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            }
        },
        placeholder: i18n.placeholderSearchOrders,
        minimumInputLength: 1,
        allowClear: true
    };

    if (generateLicenseKeysProduct) {
        generateLicenseKeysProduct.select2(productDropdownSearchConfig);
    }

    if (generateLicenseKeysOrder) {
        generateLicenseKeysOrder.select2(orderDropdownSearchConfig);
    }
});