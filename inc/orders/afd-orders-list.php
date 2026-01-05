<?php
/**
 * AWESOME FOOD DELIVERY - PREMIUM MASTER DASHBOARD
 * Features: Original Layout, Pre-Order Workflow, Thermal Printing, Auto-Refresh, & Live Timer
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

// --- 1. ACTION HANDLERS ---

// Updated Serial Status Workflow (Now includes Pre-Order support)
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['order_id']) && isset($_GET['new_status'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = sanitize_text_field($_GET['new_status']);
    $wpdb->update($table_name, ['order_status' => $new_status], ['id' => $order_id]);
    
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

// ACTION: THERMAL PRINT HANDLER (Updated with Scheduled Time)
if (isset($_GET['action']) && $_GET['action'] === 'print' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $type = $_GET['type']; 
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));
    
    if ($order) {
        $display_id = $order->display_id; 
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
                .time-box { border: 2px solid #000; padding: 5px; font-size: 18px; font-weight: 900; margin-top: 10px; display: block; }
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
                <div class="time-box">TIME: <?php echo strtoupper(esc_html($order->scheduled_time)); ?></div>
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
                        <?php if($type === 'customer'): ?><td class="price"><?php echo number_format($item['price'] * $item['qty'], 2); ?></td><?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                <?php if($type === 'customer'): ?>
                    <tr class="total-row"><td colspan="2" style="padding-top:10px;">TOTAL</td><td class="price" style="padding-top:10px;"><?php echo number_format($order->total_price, 2); ?></td></tr>
                <?php endif; ?>
            </table>
            <?php if(!empty($order->notes)): ?><div class="notes">NOTE: <?php echo strtoupper(esc_html($order->notes)); ?></div><?php endif; ?>
            <div class="text-center" style="margin-top:20px; font-size:12px;">*** <?php echo strtoupper($type); ?> COPY ***</div>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- 2. DATA PREP ---
$prep_time = intval(get_option('afd_cooking_time', 20));
$afon_orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1000");
$wp_time_format = get_option('time_format');
$server_now = current_time('timestamp'); 
?>

<style>
    :root { 
        --clr-preorder: #8b5cf6;
        --clr-pending: #d63638; 
        --clr-cooking: #f59e0b; 
        --clr-rider: #3b82f6; 
        --clr-delivered: #46b450; 
        --clr-dark: #2c3338;
        --clr-border: #ccd0d4;
    }

    .afd-dashboard { margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: var(--clr-dark); }
    
    #afon-orders-table { background: #fff; border: 1px solid var(--clr-border); border-radius: 8px; overflow: hidden; border-collapse: separate; border-spacing: 0; width: 100%; }
    #afon-orders-table th { background: #f9f9f9; padding: 15px; font-weight: 700; text-transform: uppercase; font-size: 11px; color: #646970; border-bottom: 2px solid var(--clr-border); text-align: left; }
    #afon-orders-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
    
    /* STATUS BADGES */
    .st-badge { padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; color: #fff; text-transform: uppercase; display: inline-block; }
    .status-preorder { background: var(--clr-preorder); }
    .status-pending { background: var(--clr-pending); animation: afd-pulse 2s infinite; }
    .status-cooking { background: var(--clr-cooking); }
    .status-rider { background: var(--clr-rider); }
    .status-completed { background: var(--clr-delivered); }

    /* ACTION BUTTONS */
    .btn-wrap { display: flex; gap: 5px; justify-content: flex-end; align-items: center; }
    
    .fd-action-btn {
        display: inline-flex; align-items: center; gap: 6px; 
        padding: 6px 12px; border-radius: 4px; border: 1px solid var(--clr-border);
        background: #fff; color: #2c3338; transition: 0.2s; cursor: pointer;
        text-decoration: none; font-size: 13px; font-weight: 600;
    }
    .fd-action-btn:hover { background: #f6f7f7; border-color: #a7aaad; color: #2271b1; }
    
    /* WORKFLOW BUTTONS */
    .btn-workflow { background: #fff; border-color: #2271b1; color: #2271b1; }
    .btn-workflow:hover { background: #f0f6ff; border-color: #2271b1; color: #135e96; }
    .btn-preorder-accept { border-color: var(--clr-preorder); color: var(--clr-preorder); }

    /* DELETE ICON */
    .btn-delete { color: #d63638; padding: 6px 10px; }
    .btn-delete:hover { background: #fcf0f1; border-color: #d63638; }

    /* TIMER */
    .timer-box { font-family: monospace; font-weight: 700; font-size: 14px; color: var(--clr-cooking); background: #fff8e5; padding: 4px 10px; border-radius: 4px; border: 1px solid #ffb900; display: inline-flex; align-items: center; gap: 5px; }
    .timer-late { background: #fcf0f1; color: var(--clr-pending); border-color: var(--clr-pending); animation: afd-pulse 1s infinite; }

    @keyframes afd-pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
</style>

<div class="wrap afd-dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="font-weight: 800; font-size: 24px; margin: 0;">Order Management</h1>
        <div style="font-size: 12px; color: #666; background: #eee; padding: 5px 12px; border-radius: 20px;">
            Auto-refresh in: <span id="timer-count" style="font-weight:bold;">30</span>s
        </div>
    </div>
    
    <table id="afon-orders-table" class="widefat">
        <thead>
            <tr>
                <th width="120">ID</th>
                <th>Customer</th>
                <th width="120">Status</th>
                <th width="140">Cooking Time</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($afon_orders as $order) :
                $st = strtolower($order->order_status);
                $otime = strtotime($order->order_date);
                $expiry = $otime + ($prep_time * 60);
                $url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order->id);
            ?>
                <tr>
                    <td><strong style="font-size:15px;">#<?php echo $order->display_id; ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($order->full_name); ?></strong><br>
                        <span style="font-size:11px; color:#d63638; font-weight:bold;">⏰ <?php echo strtoupper($order->scheduled_time); ?></span>
                        <span style="font-size:11px; color:#888;"> • <?php echo strtoupper($order->order_type); ?></span>
                    </td>
                    <td><span class="st-badge status-<?php echo $st; ?>"><?php echo $st; ?></span></td>
                    
                    <td>
                        <?php if ($st === 'cooking') : ?>
                            <div class="timer-box live-timer-js" data-expiry="<?php echo $expiry; ?>">
                                <span class="dashicons dashicons-clock" style="font-size:16px; margin-top:2px;"></span>
                                <span class="time-string">00:00</span>
                            </div>
                        <?php else: ?>
                            <span style="color:#ccc; padding-left:10px;">--:--</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="btn-wrap">
                            <?php if ($st === 'preorder') : ?>
                                <a class="fd-action-btn btn-workflow btn-preorder-accept" href="<?php echo $url . '&action=update_status&new_status=pending'; ?>">
                                    <span class="dashicons dashicons-calendar-alt"></span> Accept Pre-Order
                                </a>
                            <?php elseif ($st === 'pending') : ?>
                                <a class="fd-action-btn btn-workflow" href="<?php echo $url . '&action=update_status&new_status=cooking'; ?>">
                                    <span class="dashicons dashicons-carrot"></span> Start Cooking
                                </a>
                            <?php elseif ($st === 'cooking') : ?>
                                <a class="fd-action-btn btn-workflow" href="<?php echo $url . '&action=update_status&new_status=rider'; ?>">
                                    <span class="dashicons dashicons-id"></span> Hand to Rider
                                </a>
                            <?php elseif ($st === 'rider') : ?>
                                <a class="fd-action-btn btn-workflow" href="<?php echo $url . '&action=update_status&new_status=completed'; ?>">
                                    <span class="dashicons dashicons-yes-alt"></span> Mark Complete
                                </a>
                            <?php endif; ?>

                            <a class="fd-action-btn" href="<?php echo $url . '&action=view'; ?>">
                                <span class="dashicons dashicons-visibility"></span> View
                            </a>
                            
                            <a class="fd-action-btn" href="<?php echo $url . '&action=edit'; ?>">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </a>

                            <a class="fd-action-btn" href="<?php echo $url . '&action=print&type=customer'; ?>" target="_blank">
                                <span class="dashicons dashicons-printer"></span> Receipt
                            </a>

                            <a class="fd-action-btn" href="<?php echo $url . '&action=print&type=kitchen'; ?>" target="_blank">
                                <span class="dashicons dashicons-carrot"></span> Kitchen
                            </a>

                            <a class="fd-action-btn btn-delete" href="<?php echo wp_nonce_url($url . '&action=delete', 'delete_order_' . $order->id); ?>" onclick="return confirm('Delete order?')">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($){ 
    if ($.fn.DataTable) {
        $('#afon-orders-table').DataTable({ "order": [[0, "desc"]], "pageLength": 50 });
    }

    var sT = <?php echo $server_now; ?>, bT = Math.floor(Date.now() / 1000), gap = sT - bT;

    function updateLiveClocks() {
        var now = Math.floor(Date.now() / 1000) + gap;
        $('.live-timer-js').each(function() {
            var diff = parseInt($(this).data('expiry')) - now;
            if (diff <= 0) {
                $(this).addClass('timer-late').find('.time-string').text("LATE");
            } else {
                var m = Math.floor(diff / 60), s = diff % 60;
                $(this).find('.time-string').text((m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s));
            }
        });
    }
    setInterval(updateLiveClocks, 1000);
    updateLiveClocks();

    var timeLeft = 30;
    setInterval(function(){
        timeLeft--; $('#timer-count').text(timeLeft);
        if(timeLeft <= 0) location.reload();
    }, 1000);
});
</script>