<?php
/**
 * AWESOME FOOD DELIVERY - OPTIMIZED DASHBOARD
 * Features: Permanent IDs, JS Redirects, Thermal Printing, Auto-Refresh
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

// --- 1. ACTION HANDLERS (Processing before HTML) ---

// ACTION: MARK AS COMPLETE
if (isset($_GET['action']) && $_GET['action'] === 'mark_complete' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $wpdb->update($table_name, ['order_status' => 'completed'], ['id' => $order_id]);
    
    $redirect_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders');
    echo "<script>window.location.href='$redirect_url';</script>";
    exit;
}

// ACTION: DELETE ORDER
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['order_id'])) {
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_order_' . $_GET['order_id'])) {
        $delete_id = intval($_GET['order_id']);
        $wpdb->delete($table_name, ['id' => $delete_id], ['%d']);
    }
}

// ACTION: THERMAL PRINT HANDLER
if (isset($_GET['action']) && $_GET['action'] === 'print' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $type = $_GET['type']; // 'kitchen' or 'customer'
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));
    
    if ($order) {
        $display_id = $order->display_id; // Using our NEW permanent column
        $items = json_decode($order->items_json, true);
        if (ob_get_length()) ob_clean(); 
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: "Courier New", Courier, monospace; width: 72mm; margin: 0 auto; padding: 10px; color: #000; }
                .text-center { text-align: center; }
                .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; text-align: center; }
                .main-id { font-size: 32px; font-weight: 900; }
                .type-badge { background: #000; color: #fff; padding: 5px; font-size: 18px; font-weight: bold; display: inline-block; margin-top: 5px; text-transform: uppercase; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .item-row td { padding: 8px 0; border-bottom: 1px dashed #000; vertical-align: top; }
                <?php if($type === 'kitchen'): ?>
                    .qty { font-size: 34px; font-weight: 900; width: 50px; }
                    .item-name { font-size: 22px; font-weight: bold; text-transform: uppercase; }
                <?php else: ?>
                    .qty { font-size: 18px; font-weight: bold; width: 30px; }
                    .item-name { font-size: 18px; font-weight: bold; }
                    .price { text-align: right; font-size: 18px; }
                    .total-row { font-size: 22px; font-weight: 900; border-top: 2px solid #000; }
                <?php endif; ?>
                .notes { background: #000; color: #fff; padding: 8px; margin-top: 10px; text-align: center; font-weight: bold; font-size: 18px; }
            </style>
        </head>
        <body onload="window.print();">
            <div class="header">
                <span class="main-id">#<?php echo $display_id; ?></span><br>
                <div class="type-badge"><?php echo esc_html($order->order_type); ?></div>
            </div>
            <div>
                <strong><?php echo esc_html($order->full_name); ?></strong><br>
                TEL: <?php echo esc_html($order->phone); ?><br>
                <?php if($order->order_type === 'delivery'): ?>ADDR: <?php echo esc_html($order->address); ?><?php endif; ?>
            </div>
            <table>
                <?php if(is_array($items)) : foreach($items as $item) : ?>
                    <tr class="item-row">
                        <td class="qty"><?php echo $item['qty']; ?>x</td>
                        <td class="item-name"><?php echo esc_html($item['name']); ?></td>
                        <?php if($type === 'customer'): ?>
                            <td class="price"><?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                <?php if($type === 'customer'): ?>
                    <tr class="total-row">
                        <td colspan="2" style="padding-top:10px;">TOTAL</td>
                        <td class="price" style="padding-top:10px;"><?php echo number_format($order->total_price, 2); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
            <?php if(!empty($order->notes)): ?>
                <div class="notes">NOTE: <?php echo strtoupper(esc_html($order->notes)); ?></div>
            <?php endif; ?>
            <div class="text-center" style="margin-top:20px; font-size:12px;">
                *** <?php echo ($type === 'kitchen') ? 'KITCHEN' : 'CUSTOMER'; ?> COPY ***
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- 2. DATA FETCHING ---
$afon_orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1000");
$wp_time_format = get_option('time_format');
?>

<style>
    :root { --res-primary: #d63638; --res-success: #46b450; --res-border: #ccd0d4; }
    .afd-dashboard { margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    #afon-orders-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; width: 100%; border-collapse: collapse; }
    #afon-orders-table th { background: #fafafa; padding: 15px; font-weight: 700; text-transform: uppercase; font-size: 11px; text-align: left; border-bottom: 2px solid #f0f0f1; }
    #afon-orders-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
    
    .status-pending { background: var(--res-primary) !important; color: #fff !important; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; animation: afd-blink 1s infinite; display: inline-block; }
    .status-completed { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; display: inline-block; }
    @keyframes afd-blink { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }

    .fd-btn { padding: 6px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #dcdcde; background: #fff; color: #2c3338; margin-left: 2px; }
    .fd-btn-complete { background: var(--res-success); color: #fff; border-color: #389040; }
    .fd-btn-complete:hover { background: #389040; color: #fff; }
</style>

<div class="wrap afd-dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="font-weight: 800; font-size: 24px; margin: 0;">Order Management</h1>
        <div id="refresh-timer" style="font-size: 12px; color: #666; background: #eee; padding: 5px 12px; border-radius: 20px;">
            Auto-refresh in: <span id="timer-count">30</span>s
        </div>
    </div>
    
    <table id="afon-orders-table" class="widefat">
        <thead>
            <tr>
                <th width="160">Order ID</th>
                <th>Customer</th>
                <th width="100">Status</th>
                <th width="480" style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($afon_orders as $order) :
                $order_id = $order->id;
                $display_id = $order->display_id; // Simple & permanent!
                $status = strtolower($order->order_status);
                $base_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id);
            ?>
                <tr>
                    <td><strong>#<?php echo esc_html($display_id); ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($order->full_name); ?></strong><br>
                        <span style="font-size:11px; color:#888;"><?php echo date($wp_time_format, strtotime($order->order_date)); ?></span>
                    </td>
                    <td><span class="status-<?php echo $status; ?>"><?php echo strtoupper($status); ?></span></td>
                    <td style="text-align: right;">
                        <?php if($status !== 'completed'): ?>
                            <a class="fd-btn fd-btn-complete" href="<?php echo esc_url($base_url . '&action=mark_complete'); ?>" onclick="return confirm('Complete Order #<?php echo $display_id; ?>?')">
                                <span class="dashicons dashicons-yes"></span> Complete
                            </a>
                        <?php endif; ?>
                        
                        <a class="fd-btn" href="<?php echo esc_url($base_url . '&action=view'); ?>"><span class="dashicons dashicons-visibility"></span> View</a>
                        <a class="fd-btn" href="<?php echo esc_url($base_url . '&action=edit'); ?>"><span class="dashicons dashicons-edit"></span> Edit</a>
                        <a class="fd-btn" href="<?php echo esc_url($base_url . '&action=print&type=customer'); ?>" target="_blank"><span class="dashicons dashicons-printer"></span> Receipt</a>
                        <a class="fd-btn" href="<?php echo esc_url($base_url . '&action=print&type=kitchen'); ?>" target="_blank"><span class="dashicons dashicons-carrot"></span> Kitchen</a>
                        
                        <a class="fd-btn" style="color:var(--res-primary);" href="<?php echo wp_nonce_url($base_url . '&action=delete', 'delete_order_' . $order_id); ?>" onclick="return confirm('Delete #<?php echo $display_id; ?> permanently?')">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($){ 
    // 1. DataTables Init
    if ($.fn.DataTable) {
        $('#afon-orders-table').DataTable({ "order": [[0, "desc"]], "pageLength": 25 }); 
    }

    // 2. Auto Refresh Logic (30 Seconds)
    var timeLeft = 30;
    var timer = setInterval(function(){
        timeLeft--;
        $('#timer-count').text(timeLeft);
        if(timeLeft <= 0){
            location.reload();
        }
    }, 1000);
});
</script>