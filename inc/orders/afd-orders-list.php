<?php
/**
 * AWESOME FOOD DELIVERY - ULTIMATE MASTER DASHBOARD
 * Features: Accept Order triggers New Tab Print, Status Filters, & Date Range Search
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

// --- 0. PRINT BRIDGE HANDLER ---
if (isset($_GET['action']) && $_GET['action'] === 'print' && isset($_GET['order_id'])) {
    if (isset($_GET['type']) && $_GET['type'] === 'kitchen') {
        $print_file = plugin_dir_path(__FILE__) . 'admin/afd-orders-print-kitchen.php';
        if (file_exists($print_file)) {
            include($print_file);
            exit;
        }
    }
}

// --- 1. ACTION HANDLERS ---
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['order_id']) && isset($_GET['new_status'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = sanitize_text_field($_GET['new_status']);
    $update_data = ['order_status' => $new_status];
    
    if ($new_status === 'cooking') { 
        $update_data['order_date'] = current_time('mysql'); 
    }
    
    $wpdb->update($table_name, $update_data, ['id' => $order_id]);
    
    $redirect_url = 'admin.php?page=awesome_food_delivery&tab=orders';
    if ($new_status === 'cooking') {
        $redirect_url .= '&autoprint=' . $order_id;
    }
    
    echo "<script>window.location.href='$redirect_url';</script>";
    exit;
}

if (isset($_POST['afd_save_order'])) {
    $order_id = intval($_POST['order_id']);
    $old_order = $wpdb->get_row($wpdb->prepare("SELECT scheduled_time, order_date FROM $table_name WHERE id = %d", $order_id));
    $new_scheduled_time = sanitize_text_field($_POST['scheduled_time']);
    $new_order_date = $old_order->order_date;

    if ($old_order && $old_order->scheduled_time !== $new_scheduled_time) {
        $old_ts = strtotime($old_order->scheduled_time);
        $new_ts = strtotime($new_scheduled_time);
        if ($old_ts && $new_ts) {
            $diff_seconds = $new_ts - $old_ts;
            $new_order_date = date('Y-m-d H:i:s', strtotime($old_order->order_date) + $diff_seconds);
        }
    }

    $wpdb->update($table_name, [
        'full_name'      => sanitize_text_field($_POST['full_name']),
        'phone'           => sanitize_text_field($_POST['phone']),
        'address'         => sanitize_textarea_field($_POST['address']),
        'notes'           => sanitize_textarea_field($_POST['notes']),
        'scheduled_time' => $new_scheduled_time,
        'delay_message'  => sanitize_textarea_field($_POST['delay_message']),
        'order_date'     => $new_order_date,
    ], ['id' => $order_id]);
    
    echo "<div class='updated'><p>Order Details Updated Successfully.</p></div>";
}

// --- 2. EDIT PAGE VIEW ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['order_id'])) {
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['order_id'])));
    if ($order) { ?>
        <div class="wrap afd-dashboard">
            <h1>Edit Order #<?php echo $order->display_id; ?></h1>
            <a href="admin.php?page=awesome_food_delivery&tab=orders" class="button">← Back</a>
            <form method="post" action="">
                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                <div style="background:#fff; padding:25px; border:1px solid #ccd0d4; border-radius:8px; margin-top:20px; max-width:800px;">
                    <table class="form-table">
                        <tr><th>Scheduled Time</th><td><input type="text" name="scheduled_time" class="regular-text" style="font-weight:900; color:#d63638;" value="<?php echo esc_attr($order->scheduled_time); ?>"></td></tr>
                        <tr><th>Name</th><td><input type="text" name="full_name" class="regular-text" value="<?php echo esc_attr($order->full_name); ?>"></td></tr>
                        <tr><th>Phone</th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($order->phone); ?>"></td></tr>
                        <tr><th>Address</th><td><textarea name="address" rows="3" class="large-text"><?php echo esc_textarea($order->address); ?></textarea></td></tr>
                        <tr><th>Kitchen Notes</th><td><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea($order->notes); ?></textarea></td></tr>
                    </table>
                    <button type="submit" name="afd_save_order" class="button button-primary">SAVE CHANGES</button>
                </div>
            </form>
        </div>
    <?php return; }
}

// --- 3. DASHBOARD MAIN LOGIC ---
$prep_time = intval(get_option('afd_cooking_time', 20));
$afon_orders = $wpdb->get_results("SELECT *, DATE(order_date) as date_only FROM $table_name ORDER BY id DESC LIMIT 1000");
$server_now = current_time('timestamp'); 
$alarm_trigger_count = 0;
?>

<style>
    :root { --clr-preorder: #8b5cf6; --clr-pending: #d63638; --clr-cooking: #f59e0b; --clr-rider: #3b82f6; --clr-completed: #46b450; }
    .afd-dashboard { margin-top: 20px; }
    #afd-alarm-unlock { background: #fffbeb; border: 1px solid #fef3c7; padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; cursor: pointer; font-weight: bold; color: #92400e; display: flex; align-items: center; justify-content: center; gap: 10px; }
    #afon-orders-table { width: 100% !important; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; border-collapse: collapse; }
    #afon-orders-table th { background: #f9f9f9; padding: 12px; border-bottom: 2px solid #ccd0d4; text-align: left; font-size: 11px; text-transform: uppercase; color: #666; }
    #afon-orders-table td { padding: 12px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; font-size: 20px;}
    .st-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; color: #fff; text-transform: uppercase; }
    .status-preorder { background: var(--clr-preorder); }
    .status-pending { background: var(--clr-pending); animation: afd-pulse 2s infinite; }
    .status-cooking { background: var(--clr-cooking); }
    .status-rider { background: var(--clr-rider); }
    .status-completed { background: var(--clr-completed); }
    .timer-box { font-family: monospace; font-weight: 700; color: #c45100; background: #fff8e5; padding: 4px 8px; border: 1px solid #ffb900; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px; }
    .timer-late { background: #fcf0f1; color: #d63638; border-color: #d63638; animation: afd-pulse 1s infinite; }
    .afd-filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; background:#fff; padding:15px; border:1px solid #ccd0d4; border-radius:8px; flex-wrap: wrap; }
    .afd-filter-bar select, .afd-filter-bar input { padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc; height: 35px; }
    .fd-action-btn { text-decoration: none; padding: 5px 8px; border: 1px solid #ccc; border-radius: 4px; color: #333; font-size: 18px; font-weight: bold; background: #fff; display: inline-flex; align-items: center; gap: 4px; }
    @keyframes afd-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    
    /* Ensure the date column is hidden but available for DataTables */
    .afd-hidden-date { display: none !important; }
</style>

<div class="wrap afd-dashboard">
    <audio id="afdOrderAlarm" loop preload="auto"><source src="https://assets.mixkit.co/active_storage/sfx/2041/2041-preview.mp3" type="audio/mpeg"></audio>

    <div id="afd-alarm-unlock"><span class="dashicons dashicons-megaphone"></span> CLICK TO ENABLE SOUND NOTIFICATIONS</div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="font-weight: 900;">Order Master Dashboard</h1>
        <div style="background: #fff; padding: 5px 15px; border-radius: 20px; border: 1px solid #ccc; font-weight: bold;">
            Auto-Refresh: <span id="timer-count" style="color:red;">60</span>s
        </div>
    </div>

    <div class="afd-filter-bar">
        <div>
            <strong>Status:</strong>
            <select id="status-dropdown">
                <option value="">All Orders</option>
                <option value="preorder">Pre-orders</option>
                <option value="pending">Pending</option>
                <option value="cooking">Kitchen</option>
                <option value="rider">Ready for Collection</option>
                <option value="completed">Done (Completed)</option>
            </select>
        </div>
        <div id="date-filter-wrap" style="display: flex; gap: 10px; align-items: center;">
            <strong>Date From:</strong>
            <input type="date" id="date-from" value="<?php echo date('Y-m-d'); ?>">
            <strong>End Date:</strong>
            <input type="date" id="date-to" value="<?php echo date('Y-m-d'); ?>">
        </div>
    </div>

    <table id="afon-orders-table">
        <thead>
            <tr>
                <th width="80">ID</th>
                <th>Customer</th>
                <th width="100">Status</th>
                <th width="130">Kitchen Timer</th>
                <th class="afd-hidden-date">Date</th> <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($afon_orders as $order) : 
                $raw_st = strtolower(trim($order->order_status));
                $sched = strtolower(trim($order->scheduled_time));
                if (empty($raw_st) || $raw_st === 'pending' || $raw_st === 'preorder') {
                    $st = ($sched !== 'asap' && !empty($sched)) ? 'preorder' : 'pending';
                    $alarm_trigger_count++;
                } else { $st = $raw_st; }
                $expiry = strtotime($order->order_date) + ($prep_time * 60);
                $url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order->id);
            ?>
            <tr>
                <td><strong>#<?php echo $order->display_id; ?></strong></td>
                <td>
                    <strong><?php echo esc_html($order->full_name); ?></strong><br>
                    <span style="color:#d63638; font-weight:bold; font-size:11px;">⏰ <?php echo strtoupper($order->scheduled_time); ?></span>
                </td>
                <td><span class="st-badge status-<?php echo $st; ?>"><?php echo $st; ?></span></td>
                <td>
                    <?php if ($st === 'cooking') : ?>
                        <div class="timer-box live-timer-js" data-expiry="<?php echo $expiry; ?>">
                            <span class="dashicons dashicons-clock"></span> <span class="time-string">--:--</span>
                        </div>
                    <?php else: ?>--:--<?php endif; ?>
                </td>
                <td class="afd-hidden-date"><?php echo $order->date_only; ?></td>
                <td align="right">
                    <div style="display: flex; gap: 5px; justify-content: flex-end;">
                        <?php if ($st === 'pending' || $st === 'preorder') : ?>
                            <a class="fd-action-btn" style="color:#d63638; border-color:#d63638;" href="<?php echo $url . '&action=update_status&new_status=cooking'; ?>"><span class="dashicons dashicons-carrot"></span> Accept Order</a>
                        <?php elseif ($st === 'cooking') : ?>
                            <a class="fd-action-btn" style="color:#3b82f6; border-color:#3b82f6;" href="<?php echo $url . '&action=update_status&new_status=rider'; ?>"><span class="dashicons dashicons-external"></span> READY</a>
                        <?php elseif ($st === 'rider') : ?>
                            <a class="fd-action-btn" style="color:#46b450; border-color:#46b450;" href="<?php echo $url . '&action=update_status&new_status=completed'; ?>"><span class="dashicons dashicons-yes-alt"></span> DONE</a>
                        <?php endif; ?>
                        
                        <a class="fd-action-btn" href="<?php echo $url . '&action=edit'; ?>"><span class="dashicons dashicons-edit"></span></a>
                        <a class="fd-action-btn" href="<?php echo $url . '&action=print&type=kitchen'; ?>" target="_blank"><span class="dashicons dashicons-media-text"></span> Receipt</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($){
    // 1. DataTable Initialization
    var table = $('#afon-orders-table').DataTable({ 
        "order": [[0, "desc"]], 
        "pageLength": 100, 
        "dom": 'lfrtip' 
    });

    // 2. Custom Date Range Filtering for DataTable (Global)
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var min = $('#date-from').val();
            var max = $('#date-to').val();
            var dateVal = data[4]; // The 'Date' column index

            if (
                (min === "" && max === "") ||
                (min === "" && dateVal <= max) ||
                (min <= dateVal && max === "") ||
                (min <= dateVal && dateVal <= max)
            ) {
                return true;
            }
            return false;
        }
    );

    // Filter Logic Triggers
    $('#status-dropdown').on('change', function(){
        table.column(2).search($(this).val()).draw();
    });

    $('#date-from, #date-to').on('change', function(){
        table.draw();
    });

    // 3. AUTO-PRINT LOGIC
    const urlParams = new URLSearchParams(window.location.search);
    const autoPrintID = urlParams.get('autoprint');
    if (autoPrintID) {
        const printUrl = "admin.php?page=awesome_food_delivery&tab=orders&order_id=" + autoPrintID + "&action=print&type=kitchen";
        window.open(printUrl, '_blank');
        const cleanUrl = window.location.href.split('&autoprint=')[0];
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // 4. Kitchen Timer Sync Engine
    var sT = <?php echo $server_now; ?>, bT = Math.floor(Date.now() / 1000), gap = sT - bT;
    function updateClocks() {
        var now = Math.floor(Date.now() / 1000) + gap;
        $('.live-timer-js').each(function() {
            var diff = parseInt($(this).data('expiry')) - now;
            if (diff <= 0) { $(this).addClass('timer-late').find('.time-string').text("LATE"); }
            else { 
                var m = Math.floor(diff / 60), s = diff % 60; 
                $(this).find('.time-string').text((m < 10 ? "0"+m : m) + ":" + (s < 10 ? "0"+s : s)); 
            }
        });
    }
    setInterval(updateClocks, 1000); updateClocks();

    // 5. Audio Alarm System
    const audio = document.getElementById('afdOrderAlarm');
    const unlockBtn = document.getElementById('afd-alarm-unlock');
    const newOrders = <?php echo (int)$alarm_trigger_count; ?>;
    if (sessionStorage.getItem('afd_audio_active') === 'true') {
        unlockBtn.style.display = 'none';
        if (newOrders > 0) { audio.play().catch(e => { unlockBtn.style.display = 'flex'; }); }
    }
    unlockBtn.addEventListener('click', function() {
        sessionStorage.setItem('afd_audio_active', 'true');
        unlockBtn.style.display = 'none';
        if (newOrders > 0) { audio.play(); }
    });

    // 6. Global Auto-Refresh (30s)
    var refresh = 60;
    setInterval(function(){ 
        refresh--; 
        $('#timer-count').text(refresh); 
        if(refresh <= 0) location.reload(); 
    }, 1000);
});
</script>