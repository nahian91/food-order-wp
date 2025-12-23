<?php
if (!defined('ABSPATH')) exit;

/**
 * Handle Order Deletion
 */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['order_id'])) {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_order_' . $_GET['order_id'])) {
        wp_die('Security check failed.');
    }
    if (!current_user_can('delete_posts')) {
        wp_die('You do not have permission to delete this.');
    }
    $delete_id = intval($_GET['order_id']);
    if (get_post_type($delete_id) === 'food_order') {
        wp_delete_post($delete_id, true);
        echo '<div class="notice notice-success is-dismissible"><p>Order deleted successfully.</p></div>';
    }
}

/**
 * Fetch Orders
 */
$afon_orders = get_posts([
    'post_type'      => 'food_order',
    'numberposts'    => 500, 
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

// Get Global WordPress Date/Time Formats
$wp_date_format = get_option('date_format');
$wp_time_format = get_option('time_format');
?>

<style>
    :root { 
        --res-primary: #d63638; 
        --res-dark: #1d2327;    
        --res-border: #ccd0d4; 
    }

    .afd-dashboard { margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    #afon-orders-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.05); border-collapse: collapse; width: 100%; }
    #afon-orders-table thead th { background: #fafafa; padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; text-align: left; }
    #afon-orders-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
    
    .afon-id-badge { background: #f6f7f7; color: var(--res-dark); padding: 5px 10px; border-radius: 4px; border: 1px solid var(--res-border); font-family: 'Courier New', monospace; font-weight: 800; font-size: 12px; white-space: nowrap; }
    .afon-customer-name { font-size: 14px; font-weight: 700; color: var(--res-dark); }
    .afon-order-time { color: #a7aaad; font-size: 11px; margin-top: 2px; display: block; }
    
    .afon-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; border: 1px solid transparent; }
    .status-pending { background: #fff8e5; color: #856404; border-color: #ffeeba; animation: afd-blink-status 1.5s infinite; }
    .status-completed { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
    .status-cancelled { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

    @keyframes afd-blink-status { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

    .fd-btn { padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s; border: 1px solid #dcdcde; background: #fff; color: #2c3338; }
    .fd-btn:hover { border-color: var(--res-primary); color: var(--res-primary); background: #fff9f9; }
    
    /* Action Specific Styles */
    .fd-btn-kitchen { background: #f0f6ff; color: #2271b1; border-color: #c2d7ef; }
    .fd-btn-kitchen:hover { background: #2271b1 !important; color: #fff !important; }
    .fd-btn-delete:hover { background: #fef2f2; border-color: var(--res-primary); color: var(--res-primary); }

    .dataTables_wrapper .dataTables_filter { text-align: left; margin-bottom: 20px; }
    .dataTables_wrapper .dataTables_filter input { border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px 15px; width: 350px; outline: none; }
</style>

<div class="wrap afd-dashboard">
    <div style="margin-bottom: 25px;">
        <h1 style="margin:0; font-weight: 800; font-size: 24px;">Order Management</h1>
    </div>

    <table id="afon-orders-table" class="widefat">
        <thead>
            <tr>
                <th width="180">Order ID</th>
                <th>Customer Name</th>
                <th width="120">Status</th>
                <th width="400" style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($afon_orders)) : ?>
                <?php foreach ($afon_orders as $afon_post) :
                    $order_id = $afon_post->ID;
                    $display_id = get_the_title($order_id);

                    // Fetch Meta
                    $customer = get_post_meta($order_id, 'customer_name', true) ?: 'Guest Order #' . $order_id;
                    $status = get_post_meta($order_id, 'status', true) ?: 'pending';
                    $status_slug = strtolower($status);
                    
                    // Navigation URLs
                    $base_url   = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id);
                    $view_url   = $base_url . '&action=view';
                    $edit_url   = $base_url . '&action=edit';
                    $print_customer_url = $base_url . '&action=print&type=customer';
                    $print_kitchen_url  = $base_url . '&action=print&type=kitchen';
                    
                    $delete_url = wp_nonce_url(
                        $base_url . '&action=delete',
                        'delete_order_' . $order_id
                    );
                ?>
                    <tr>
                        <td data-order="<?php echo esc_attr($order_id); ?>">
                            <span class="afon-id-badge"><?php echo esc_html($display_id); ?></span>
                        </td>
                        <td>
                            <div class="afon-customer-name"><?php echo esc_html($customer); ?></div>
                            <span class="afon-order-time">
                                <?php echo get_the_date($wp_date_format, $order_id); ?> at <?php echo get_the_date($wp_time_format, $order_id); ?>
                            </span>
                        </td>
                        <td>
                            <span class="afon-status status-<?php echo esc_attr($status_slug); ?>">
                                <?php echo esc_html(ucfirst($status_slug)); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a class="fd-btn" href="<?php echo esc_url($view_url); ?>"><span class="dashicons dashicons-visibility"></span> View</a>
                            <a class="fd-btn" href="<?php echo esc_url($edit_url); ?>"><span class="dashicons dashicons-edit"></span> Edit</a>
                            
                            <a class="fd-btn" href="<?php echo esc_url($print_customer_url); ?>" target="_blank" title="Print Customer Receipt">
                                <span class="dashicons dashicons-printer"></span> Receipt
                            </a>

                            <a class="fd-btn fd-btn-kitchen" href="<?php echo esc_url($print_kitchen_url); ?>" target="_blank" title="Print Kitchen Ticket">
                                <span class="dashicons dashicons-carrot"></span> Kitchen
                            </a>
                            
                            <a class="fd-btn fd-btn-delete" 
                               href="<?php echo esc_url($delete_url); ?>" 
                               onclick="return confirm('Warning: This will permanently delete <?php echo esc_js($display_id); ?>. Continue?')">
                                 <span class="dashicons dashicons-trash"></span>
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
    if ($.fn.DataTable) {
        $('#afon-orders-table').DataTable({
            "pageLength": 15,
            "order": [[0, "desc"]], 
            "dom": '<"top"f>rt<"bottom"ip><"clear">',
            "language": {
                "search": "",
                "searchPlaceholder": "Search orders (ID, Name, Date)..."
            },
            "columnDefs": [ 
                { "orderable": false, "targets": [3] }
            ]
        });
    }
});
</script>