<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Orders List - Restaurant Red SaaS UI
 */
$afon_orders = get_posts([
    'post_type'      => 'food_order',
    'numberposts'    => 500, 
    'post_status'    => 'publish',
    'orderby'        => 'ID',
    'order'          => 'DESC',
]);
?>

<div class="wrap afon-wrap">
    <div class="afon-header-flex">
        <h1 class="afon-page-title"><?php esc_html_e('Live Orders List', 'text-domain'); ?></h1>
    </div>

    <table id="afon-orders-table" class="widefat afon-orders-table">
        <thead>
            <tr>
                <th width="80">Order</th>
                <th>Customer</th>
                <th width="30%">Items</th>
                <th>Total</th>
                <th>Status</th>
                <th width="280" class="afon-text-right">Management</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($afon_orders)) : ?>
                <?php foreach ($afon_orders as $afon_post) :
                    $afon_order_id = $afon_post->ID;
                    $afon_customer = get_post_meta($afon_order_id, 'customer_name', true);
                    $afon_items    = get_post_meta($afon_order_id, 'items', true);
                    $afon_total    = get_post_meta($afon_order_id, 'total_price', true);
                    $afon_status   = get_post_meta($afon_order_id, 'status', true) ?: 'pending';
                    
                    // Route Links
                    $afon_view_url  = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id . '&action=view');
                    $afon_edit_url  = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id . '&action=edit');
                    $afon_print_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id . '&action=print');
                ?>
                    <tr>
                        <td><strong class="afon-id-text">#<?php echo esc_html($afon_order_id); ?></strong></td>
                        <td>
                            <div class="afon-customer-name"><?php echo esc_html($afon_customer ?: 'Guest'); ?></div>
                            <code class="afon-id-code"><?php echo get_the_date('M j, g:i a', $afon_order_id); ?></code>
                        </td>
                        
                        <td>
                            <?php if (is_array($afon_items)) : 
                                $afon_display_items = array_slice($afon_items, 0, 3);
                                foreach ($afon_display_items as $afon_item) : ?>
                                <div class="afon-item-row">
                                    <span class="afon-qty-badge"><?php echo intval($afon_item['qty']); ?></span>
                                    <span class="afon-item-name"><?php echo esc_html($afon_item['name']); ?></span>
                                </div>
                            <?php endforeach; 
                                if(count($afon_items) > 3) echo '<small class="afon-more-items">+ ' . (count($afon_items)-3) . ' more items...</small>';
                            endif; ?>
                        </td>

                        <td>
                            <strong class="afon-price-text">
                                <?php echo number_format(floatval($afon_total), 2, '.', '') . ' €'; ?>
                            </strong>
                        </td>
                        
                        <td>
                            <span class="afon-status-badge afon-status-<?php echo esc_attr(strtolower($afon_status)); ?>">
                                <?php echo esc_html($afon_status); ?>
                            </span>
                        </td>

                        <td class="afon-text-right">
                            <a class="afon-btn-action" href="<?php echo esc_url($afon_view_url); ?>">
                                <span class="dashicons dashicons-visibility"></span> View
                            </a>

                            <a class="afon-btn-action" href="<?php echo esc_url($afon_edit_url); ?>">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </a>

                            <a class="afon-btn-action afon-btn-print" href="<?php echo esc_url($afon_print_url); ?>" target="_blank">
                                <span class="dashicons dashicons-printer"></span> Print
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>