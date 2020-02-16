<?php
/**
 * The template for the ordered license keys inside the delivery email (HTML).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/lmfwc-email-order-details.php.
 *
 * HOWEVER, on occasion I will need to update template files and you
 * (the developer) will need to copy the new files to your theme to
 * maintain compatibility. I try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 2.0.0
 */

defined('ABSPATH') || exit; ?>

<h2>
    <?php
    /* translators: %s: Order ID. */
    echo wp_kses_post(
        sprintf(
            __(
                '(Order #%s)', 'license-manager-for-woocommerce') . ' (<time datetime="%s">%s</time>)',
                $order->get_order_number(),
                $order->get_date_created()->format('c'),
                wc_format_datetime($order->get_date_created()
            )
        )
    );
    ?>
</h2>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <thead>
            <tr>
                <th class="td" scope="col" style="text-align: left;"><?php esc_html_e('Product', 'license-manager-for-woocommerce'); ?></th>
                <th class="td" scope="col" style="text-align: left;"><?php esc_html_e('Quantity', 'license-manager-for-woocommerce'); ?></th>
                <th class="td" scope="col" style="text-align: left;"><?php esc_html_e('Price', 'license-manager-for-woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            echo wc_get_email_order_items(
                $order,
                array(
                    'show_sku'      => false,
                    'show_image'    => false,
                    'image_size'    => array(32, 32),
                    'plain_text'    => $plain_text,
                    'sent_to_admin' => false,
                )
            );
            ?>
        </tbody>
        <tfoot>
            <?php
            $totals = $order->get_order_item_totals();

            if ($totals) {
                $i = 0;
                foreach ($totals as $total) {
                    $i++;
                    ?>
                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align: left; <?php echo ($i === 1) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post($total['label']); ?></th>
                        <td class="td" style="text-align: left; <?php echo ($i === 1) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post($total['value']); ?></td>
                    </tr>
                    <?php
                }
            }

            if ($order->get_customer_note()) {
                ?>
                <tr>
                    <th class="td" scope="row" colspan="2" style="text-align: left;"><?php esc_html_e('Note', 'license-manager-for-woocommerce'); ?>></th>
                    <td class="td" style="text-align: left;"><?php echo wp_kses_post(wptexturize($order->get_customer_note())); ?></td>
                </tr>
                <?php
            }
            ?>
        </tfoot>
    </table>
</div>