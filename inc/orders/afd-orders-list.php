<?php
/**
 * AWESOME FOOD DELIVERY - MASTER ORDERS DASHBOARD
 * Features: Action Labels, Persistent Alarm, Audio Unlocker, and Kitchen Timers
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

// --- 1. ACTION HANDLERS ---

// UPDATE STATUS
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['order_id']) && isset($_GET['new_status'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = sanitize_text_field($_GET['new_status']);
    $update_data = ['order_status' => $new_status];
    
    if ($new_status === 'cooking') { 
        $update_data['order_date'] = current_time('mysql'); 
    }
    
    $wpdb->update($table_name, $update_data, ['id' => $order_id]);
    echo "<script>window.location.href='admin.php?page=awesome_food_delivery&tab=orders';</script>";
    exit;
}

// SAVE EDITED ORDER
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
        'phone'          => sanitize_text_field($_POST['phone']),
        'address'        => sanitize_textarea_field($_POST['address']),
        'notes'          => sanitize_textarea_field($_POST['notes']),
        'scheduled_time' => $new_scheduled_time,
        'delay_message'  => sanitize_textarea_field($_POST['delay_message']),
        'order_date'     => $new_order_date,
    ], ['id' => $order_id]);
    
    echo "<div class='updated'><p>Order Updated Successfully.</p></div>";
}

// DELETE ORDER
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['order_id'])) {
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_order_' . $_GET['order_id'])) {
        $wpdb->delete($table_name, ['id' => intval($_GET['order_id'])]);
        echo "<script>window.location.href='admin.php?page=awesome_food_delivery&tab=orders';</script>";
        exit;
    }
}

// --- 2. VIEW / EDIT LOGIC ---
if (isset($_GET['action']) && ($_GET['action'] === 'view' || $_GET['action'] === 'edit') && isset($_GET['order_id'])) {
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['order_id'])));
    $is_edit = ($_GET['action'] === 'edit');
    if ($order) { ?>
        <div class="wrap afd-dashboard">
            <h1><?php echo $is_edit ? 'Edit Order' : 'View Details'; ?> #<?php echo $order->display_id; ?></h1>
            <a href="admin.php?page=awesome_food_delivery&tab=orders" class="button">← Back to Orders</a>
            <form method="post" action="">
                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                <div style="background:#fff; padding:25px; border:1px solid #ccd0d4; border-radius:8px; margin-top:20px;">
                    <table class="form-table">
                        <tr><th>Scheduled Time</th><td><input type="text" name="scheduled_time" class="regular-text" style="font-weight:900; color:#d63638;" value="<?php echo esc_attr($order->scheduled_time); ?>" <?php echo $is_edit?'':'readonly'; ?>></td></tr>
                        <tr><th>Customer Name</th><td><input type="text" name="full_name" class="regular-text" value="<?php echo esc_attr($order->full_name); ?>" <?php echo $is_edit?'':'readonly'; ?>></td></tr>
                        <tr><th>Phone</th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($order->phone); ?>" <?php echo $is_edit?'':'readonly'; ?>></td></tr>
                        <tr><th>Address</th><td><textarea name="address" rows="4" class="large-text" <?php echo $is_edit?'':'readonly'; ?>><?php echo esc_textarea($order->address); ?></textarea></td></tr>
                        <tr><th>Notes</th><td><textarea name="notes" rows="3" class="large-text" <?php echo $is_edit?'':'readonly'; ?>><?php echo esc_textarea($order->notes); ?></textarea></td></tr>
                    </table>
                    <?php if($is_edit): ?><button type="submit" name="afd_save_order" class="button button-primary">SAVE CHANGES</button><?php endif; ?>
                </div>
            </form>
        </div>
    <?php return; }
}

// --- 3. MAIN DASHBOARD DATA ---
$prep_time = intval(get_option('afd_cooking_time', 20));
$pending_orders_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE order_status = 'pending'");
$afon_orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 500");
$server_now = current_time('timestamp'); 
?>

<style>
    :root { 
        --afd-bg: #f8fafc;
        --afd-accent: #6366f1;
        --clr-pending: #d63638; 
        --clr-cooking: #f59e0b; 
        --clr-rider: #3b82f6; 
        --clr-completed: #46b450; 
    }
    
    .afd-dashboard { margin-top: 20px; font-family: 'Inter', sans-serif; }

    /* Audio Unlocker Bar - Identical to Dashboard Tab */
    #afd-audio-unlock {
        background: #fffbeb; border: 1px solid #fef3c7; padding: 15px;
        margin-bottom: 25px; border-radius: 14px; display: flex; align-items: center; justify-content: center; 
        gap: 12px; font-weight: 700; color: #92400e; cursor: pointer; transition: 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    #afd-audio-unlock:hover { background: #fef3c7; transform: translateY(-1px); }

    #afon-orders-table { width: 100%; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; border-collapse: separate; border-spacing: 0; }
    #afon-orders-table th { background: #f9f9f9; padding: 15px; border-bottom: 2px solid #ccd0d4; text-align: left; font-size: 11px; text-transform: uppercase; color: #666; }
    #afon-orders-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
    
    .st-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; color: #fff; text-transform: uppercase; }
    .status-pending { background: var(--clr-pending); animation: afd-pulse 2s infinite; }
    .status-cooking { background: var(--clr-cooking); }
    .status-rider { background: var(--clr-rider); }
    .status-completed { background: var(--clr-completed); }

    .timer-box { font-family: monospace; font-weight: 700; font-size: 14px; color: #c45100; background: #fff8e5; padding: 4px 10px; border: 1px solid #ffb900; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px; }
    .timer-late { background: #fcf0f1; color: #d63638; border-color: #d63638; animation: afd-pulse 1s infinite; }
    
    .afd-filter-bar { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; background:#fff; padding:15px; border:1px solid #ccc; border-radius:12px; }
    .filter-btn { padding: 8px 15px; border-radius: 8px; border: 1px solid #ccc; background: #fff; cursor: pointer; font-size: 12px; font-weight: bold; }
    .filter-btn.active { background: var(--afd-accent); color: #fff; border-color: var(--afd-accent); }
    
    .fd-action-btn { text-decoration: none; padding: 6px 12px; border: 1px solid #ccc; border-radius: 6px; color: #333; font-size: 11px; font-weight: bold; background: #fff; display: inline-flex; align-items: center; gap: 5px; }
    .fd-action-btn:hover { border-color: var(--afd-accent); color: var(--afd-accent); }
    
    @keyframes afd-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
</style>

<div class="wrap afd-dashboard">
    <audio id="afdOrderAlarm" preload="auto" loop>
        <source src="https://assets.mixkit.co/active_storage/sfx/2041/2041-preview.mp3" type="audio/mpeg">
    </audio>

    <div id="afd-audio-unlock">
        <span class="dashicons dashicons-megaphone"></span> 
        CLICK HERE TO ENABLE LIVE AUDIO ALARMS FOR NEW ORDERS
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="font-weight: 900; font-size: 28px; margin:0;">Orders Management</h1>
            <p style="color:#64748b; margin:5px 0 0;">Manage your kitchen flow and delivery statuses in real-time.</p>
        </div>
        <div style="background: #fff; padding: 8px 20px; border-radius: 14px; border: 1px solid #ccc; font-size: 13px; font-weight: 800; display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-update" style="color:red; font-size:18px;"></span>
            AUTO-REFRESH: <span id="timer-count" style="color:red; width:20px; display:inline-block;">30</span>s
        </div>
    </div>

    <div class="afd-filter-bar">
        <strong style="font-size:13px;">Filter:</strong>
        <button class="filter-btn active" data-status="">All Orders</button>
        <button class="filter-btn" data-status="pending" style="color:var(--clr-pending);">Pending (<?php echo $pending_orders_count; ?>)</button>
        <button class="filter-btn" data-status="cooking">Kitchen</button>
        <button class="filter-btn" data-status="rider">Rider</button>
        <button class="filter-btn" data-status="completed">Completed</button>
    </div>

    <table id="afon-orders-table">
        <thead>
            <tr>
                <th width="90">Order ID</th>
                <th>Customer / Scheduled</th>
                <th width="110">Status</th>
                <th width="140">Cooking Timer</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($afon_orders as $order) : 
                $st = strtolower($order->order_status);
                $expiry = strtotime($order->order_date) + ($prep_time * 60);
                $url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order->id);
            ?>
            <tr>
                <td><strong>#<?php echo $order->display_id; ?></strong></td>
                <td>
                    <strong style="font-size:15px; display:block;"><?php echo esc_html($order->full_name); ?></strong>
                    <span style="color:var(--clr-pending); font-weight:800; font-size:11px; background:#fff1f2; padding:2px 6px; border-radius:4px; margin-top:4px; display:inline-block;">
                        ⏰ <?php echo strtoupper($order->scheduled_time); ?>
                    </span>
                </td>
                <td><span class="st-badge status-<?php echo $st; ?>"><?php echo $st; ?></span></td>
                <td>
                    <?php if ($st === 'cooking') : ?>
                        <div class="timer-box live-timer-js" data-expiry="<?php echo $expiry; ?>">
                            <span class="dashicons dashicons-clock"></span> <span class="time-string">--:--</span>
                        </div>
                    <?php else: ?><span style="color:#bbb; padding-left:10px; font-family:monospace;">--:--</span><?php endif; ?>
                </td>
                <td align="right">
                    <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                        <?php if ($st === 'pending') : ?>
                            <a class="fd-action-btn" style="color:var(--clr-pending); border-color:var(--clr-pending); background:#fff1f2;" href="<?php echo $url . '&action=update_status&new_status=cooking'; ?>"><span class="dashicons dashicons-carrot"></span> START COOK</a>
                        <?php elseif ($st === 'cooking') : ?>
                            <a class="fd-action-btn" style="color:var(--clr-rider); border-color:var(--clr-rider); background:#eff6ff;" href="<?php echo $url . '&action=update_status&new_status=rider'; ?>"><span class="dashicons dashicons-external"></span> READY</a>
                        <?php elseif ($st === 'rider') : ?>
                            <a class="fd-action-btn" style="color:var(--clr-completed); border-color:var(--clr-completed); background:#f0fdf4;" href="<?php echo $url . '&action=update_status&new_status=completed'; ?>"><span class="dashicons dashicons-yes-alt"></span> COMPLETE</a>
                        <?php endif; ?>
                        
                        <a class="fd-action-btn" href="<?php echo $url . '&action=view'; ?>"><span class="dashicons dashicons-visibility"></span> VIEW</a>
                        <a class="fd-action-btn" href="<?php echo $url . '&action=print&type=kitchen'; ?>" target="_blank"><span class="dashicons dashicons-media-text"></span> KITCHEN</a>
                        <a class="fd-action-btn" style="color:#666;" href="<?php echo wp_nonce_url($url . '&action=delete', 'delete_order_'.$order->id); ?>" onclick="return confirm('Permanently Delete Order?')"><span class="dashicons dashicons-trash"></span></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($){
    // 1. DataTable for Search/Pagination
    var table = $('#afon-orders-table').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 50,
        "language": { "search": "Search Orders:" }
    });

    // 2. Filter Status Action
    $('.filter-btn').on('click', function(){
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        table.column(2).search($(this).data('status')).draw();
    });

    // 3. Kitchen Timer Logic
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

    // 4. PERSISTENT ALARM LOGIC
    const audio = document.getElementById('afdOrderAlarm');
    const unlockBtn = document.getElementById('afd-audio-unlock');
    const pendingCount = <?php echo (int)$pending_orders_count; ?>;

    // Session check for audio permissions
    if (sessionStorage.getItem('afd_audio_enabled') === 'true') {
        unlockBtn.style.display = 'none';
        if (pendingCount > 0) {
            audio.play().catch(err => { 
                unlockBtn.style.display = 'flex'; 
            });
        }
    }

    // Unlock button listener
    unlockBtn.addEventListener('click', function() {
        sessionStorage.setItem('afd_audio_enabled', 'true');
        unlockBtn.style.display = 'none';
        
        if (pendingCount > 0) {
            audio.play();
        } else {
            // Confirm permission to browser
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                alert("Audio enabled! The alarm will ring when new orders arrive.");
            });
        }
    });

    // 5. Auto Refresh Countdown
    var refresh = 30;
    setInterval(function(){
        refresh--; $('#timer-count').text(refresh);
        if(refresh <= 0) location.reload();
    }, 1000);
});
</script>