<?php
if (!defined('ABSPATH')) exit;

/**
 * Live Orders List - Restaurant Red SaaS UI (Matches Items List Style)
 */
$afon_orders = get_posts([
    'post_type'      => 'food_order',
    'numberposts'    => 500, 
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<style>
    :root { 
        --res-primary: #d63638; 
        --res-dark: #1d2327;    
        --res-border: #ccd0d4; 
    }

    /* Container & Table Style */
    .afd-dashboard { margin-top: 20px; }
    #afon-orders-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.05); border-collapse: collapse; width: 100%; }
    #afon-orders-table thead th { background: #fafafa; padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; }
    #afon-orders-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
    
    /* Order Specific Styles */
    .afon-id-badge { background: #f6f7f7; color: var(--res-dark); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--res-border); font-family: monospace; font-weight: 700; }
    .afon-item-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .afon-qty-badge { background: #f1f1f1; color: #333; padding: 1px 6px; border-radius: 4px; font-weight: 700; font-size: 11px; border: 1px solid #ddd; }
    
    /* Status Badges */
    .afon-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-pending { background: #fff8e5; color: #856404; border: 1px solid #ffeeba; }
    .status-completed { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .status-cancelled { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* SaaS Management Buttons */
    .fd-btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #dcdcde; background: #fff; color: #2c3338; white-space: nowrap; }
    .fd-btn:hover { border-color: var(--res-primary); color: var(--res-primary); background: #fff9f9; }
    
    .fd-btn-print:hover { background: #1d2327 !important; color: #fff !important; border-color: #1d2327 !important; }

    /* DataTable Search Style */
    .dataTables_wrapper .dataTables_filter { float: none; text-align: left; }
    .dataTables_wrapper .dataTables_filter input { border: 1px solid #c3c4c7; border-radius: 4px; padding: 8px 12px; margin-bottom: 20px; width: 300px; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--res-primary); box-shadow: 0 0 0 1px var(--res-primary); outline: none; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--res-primary) !important; color: #fff !important; border: 1px solid var(--res-primary) !important; border-radius: 4px; }
</style>

<div class="wrap afd-dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin:0; font-weight: 700;"><?php esc_html_e('Live Orders List', 'text-domain'); ?></h1>
    </div>

    <table id="afon-orders-table" class="widefat">
        <thead>
            <tr>
                <th width="100">Order ID</th>
                <th>Customer</th>
                <th width="30%">Items Summary</th>
                <th>Total</th>
                <th>Status</th>
                <th width="280" style="text-align: right;">Management</th>
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
                    
                    $afon_view_url  = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id . '&action=view');
                    $afon_edit_url  = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id . '&action=edit');
                    $afon_print_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id . '&action=print');
                ?>
                    <tr>
                        <td><span class="afon-id-badge">#<?php echo esc_html($afon_order_id); ?></span></td>
                        <td>
                            <strong style="font-size:14px; color: var(--res-dark);"><?php echo esc_html($afon_customer ?: 'Guest'); ?></strong><br>
                            <span style="color: #a7aaad; font-size: 11px;"><?php echo get_the_date('M j, g:i a', $afon_order_id); ?></span>
                        </td>
                        
                        <td>
                            <?php if (is_array($afon_items)) : 
                                $afon_display_items = array_slice($afon_items, 0, 3);
                                foreach ($afon_display_items as $afon_item) : 
                                    $item_name = !empty($afon_item['name']) ? $afon_item['name'] : (!empty($afon_item['item_name']) ? $afon_item['item_name'] : 'Item');
                                ?>
                                <div class="afon-item-row">
                                    <span class="afon-qty-badge"><?php echo intval($afon_item['qty']); ?>x</span>
                                    <span style="font-size: 13px; font-weight: 500;"><?php echo esc_html($item_name); ?></span>
                                </div>
                            <?php endforeach; 
                                if(count($afon_items) > 3) echo '<small style="color:var(--res-primary); font-weight:bold;">+ ' . (count($afon_items)-3) . ' more...</small>';
                            endif; ?>
                        </td>

                        <td>
                            <strong style="font-size:16px; color: var(--res-primary);">
                                <?php echo number_format(floatval($afon_total), 2, '.', '') . ' €'; ?>
                            </strong>
                        </td>
                        
                        <td>
                            <span class="afon-status status-<?php echo esc_attr(strtolower($afon_status)); ?>">
                                <?php echo esc_html($afon_status); ?>
                            </span>
                        </td>

                        <td style="text-align: right;">
                            <a class="fd-btn" href="<?php echo esc_url($afon_view_url); ?>">
                                <span class="dashicons dashicons-visibility" style="font-size:16px; margin-top:3px;"></span> View
                            </a>
                            <a class="fd-btn" href="<?php echo esc_url($afon_edit_url); ?>">
                                <span class="dashicons dashicons-edit" style="font-size:16px; margin-top:3px;"></span> Edit
                            </a>
                            <a class="fd-btn fd-btn-print" href="<?php echo esc_url($afon_print_url); ?>" target="_blank">
                                <span class="dashicons dashicons-printer" style="font-size:16px; margin-top:3px;"></span> Print
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($){
    $('#afon-orders-table').DataTable({
        "pageLength": 20,
        "order": [[0, "desc"]], // Newest first
        "language": {
            "search": "",
            "searchPlaceholder": "Search orders (ID, Customer)...",
            "paginate": { "next": "→", "previous": "←" }
        },
        "dom": '<"top"f>rt<"bottom"ip><"clear">',
        "columnDefs": [
            { "orderable": false, "targets": [2, 5] }
        ]
    });
});
</script>