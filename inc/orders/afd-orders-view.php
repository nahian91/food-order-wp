<?php
/**
 * Final View Order - Complete Feature Set
 * Includes: Live Kitchen Timer, Address Mapping, Split Kitchen/Delivery Notes, and Charges Summary.
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';
$order_id   = intval($_GET['order_id']);

// 1. DATA FETCHING
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));

if (!$order) {
    echo '<div class="notice notice-error"><p>Order not found.</p></div>';
    return;
}

// 2. DATA MAPPING & SETTINGS
$display_id  = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;
$afon_status = strtolower($order->order_status ?: 'pending');
$afon_items  = json_decode($order->items_json, true);
$customer_id = $order->customer_id;
$order_type  = strtolower($order->order_type);
$server_now  = current_time('timestamp'); 

// Fetch Profile Address from User Meta
$meta_flat     = get_user_meta($customer_id, 'fd_flat_no', true);
$meta_building = get_user_meta($customer_id, 'fd_building', true);
$meta_door     = get_user_meta($customer_id, 'fd_door_no', true);
$meta_road     = get_user_meta($customer_id, 'fd_road_name', true);
$meta_postcode = get_user_meta($customer_id, 'fd_user_postcode', true);

// Get Live Setting for Discount
$rest_discount_pct = (float)get_option('afd_restaurant_discount', '0.00');
$discount_amount   = ($order->subtotal * $rest_discount_pct) / 100;

// Get Charges from Order Table
$delivery_charge  = (float)$order->delivery_charge;
$service_fee   = (float)$order->service_fee;
$bag_fee       = (float)$order->bag_fee;
$tip_amount    = (float)$order->tip_amount;
$prep_mins     = intval(get_option('afd_cooking_time', 20));

// Timer Calculation (Anchor: order_date)
$expiry_timestamp = strtotime($order->order_date) + ($prep_mins * 60);

// Delete URL
$delete_url = wp_nonce_url(admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id . '&action=delete'), 'delete_order_' . $order_id);
$nav_addr = ($meta_flat ? "Flat $meta_flat, " : "") . ($meta_door ? "$meta_door " : "") . ($meta_road ? "$meta_road, " : "") . ($meta_postcode ?: $order->postcode);
?>

<style>
    :root { 
        --res-red: #d63638; 
        --res-dark: #1d2327; 
        --res-border: #ccd0d4; 
        --res-success: #16a34a;
        --res-warning: #ca8a04;
        --res-blue: #2271b1;
    }
    .view-order-wrap { margin: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .view-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
    .id-badge-large { background: #fff; border: 1px solid var(--res-border); padding: 12px 24px; border-radius: 12px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    
    .timer-container { display: flex; align-items: center; gap: 15px; margin-top: 15px; }
    .view-live-timer { 
        background: #fff8e5; border: 2px solid #ffb900; color: #c45100; padding: 8px 20px; 
        border-radius: 8px; font-family: monospace; font-size: 20px; font-weight: 900; 
        display: inline-flex; align-items: center; gap: 8px; 
    }
    .view-live-timer.timer-late { background: #fcf0f1; color: #d63638; border-color: #d63638; animation: afd-pulse 1s infinite; }
    
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--res-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .view-card-header { padding: 18px 25px; border-bottom: 1px solid #f0f0f1; background: #fafafa; display: flex; align-items: center; justify-content: space-between; }
    .view-card-header h2 { margin: 0; font-size: 14px; font-weight: 700; color: var(--res-dark); text-transform: uppercase; }
    .view-card-body { padding: 25px; }

    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8f9fa; color: #646970; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .view-table td { padding: 15px; border-bottom: 1px solid #f0f0f1; font-size: 14px; }
    .qty-badge { background: var(--res-dark); color: #fff; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-right: 8px; }

    .summary-list { padding: 20px 25px; background: #fdfdfd; border-top: 1px solid #eee; }
    .summary-line { display: flex; justify-content: flex-end; margin-bottom: 8px; font-size: 14px; color: #555; }
    .summary-line label { width: 220px; text-align: right; margin-right: 25px; color: #646970; font-weight: 500; }
    .summary-line span { width: 110px; text-align: right; font-weight: 700; color: #1d2327; }
    .summary-line.grand-total { margin-top: 15px; padding-top: 15px; border-top: 2px solid #1d2327; }
    .summary-line.grand-total label { font-size: 18px; font-weight: 800; color: var(--res-dark); }
    .summary-line.grand-total span { font-size: 26px; font-weight: 900; color: var(--res-red); }

    .info-block { margin-bottom: 20px; }
    .info-block label { display: block; font-size: 11px; color: #646970; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
    .info-block p { margin: 0; font-size: 14px; font-weight: 600; color: #1d2327; }

    .addr-pill { background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
    .addr-row { display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding: 6px 0; font-size: 13px; }
    .order-status-tag { padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: 800; background: var(--res-red); color: #fff; }
    .type-box { display: block; width: 100%; text-align: center; padding: 10px; border-radius: 8px; font-weight: 800; font-size: 14px; margin-top: 10px; }
    .type-box.delivery { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .type-box.pickup { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
    
    .btn-action { text-decoration: none; padding: 10px 15px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #ccd0d4; background: #fff; color: #2c3338; cursor: pointer; transition: 0.2s; }
    @keyframes afd-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
</style>

<div class="view-order-wrap">
    
    <div class="view-header">
        <div>
            <div class="id-badge-large">
                <span style="color:#646970; font-size:11px; font-weight:700; text-transform:uppercase;">Order Ref</span><br>
                <span style="font-weight:900; color:var(--res-red); font-size:24px;">#<?php echo esc_html($display_id); ?></span>
            </div>

            <div class="timer-container">
                <?php if ($afon_status === 'cooking') : ?>
                    <div class="view-live-timer" id="view-timer-js" data-expiry="<?php echo $expiry_timestamp; ?>">
                        <span class="dashicons dashicons-clock"></span> 
                        <span class="time-string">--:--</span>
                    </div>
                <?php endif; ?>
                <div style="font-size:13px; color:#646970;">
                    Placed: <?php echo date('M j, Y @ g:i a', strtotime($order->order_date)); ?><br>
                    <strong>Scheduled: <?php echo strtoupper($order->scheduled_time); ?></strong>
                </div>
            </div>
        </div>
        <div>
            <span class="order-status-tag"><?php echo strtoupper(esc_html($afon_status)); ?></span>
        </div>
    </div>

    <div class="view-grid">
        <div class="main-column">
            <div class="view-card">
                <div class="view-card-header"><h2>Order Items Break-down</h2></div>
                <div class="view-card-body" style="padding:0;">
                    <table class="view-table">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th width="100">Price</th>
                                <th width="80">Qty</th>
                                <th width="120" style="text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($afon_items)) : foreach ($afon_items as $item) : ?>
                                <tr>
                                    <td><span class="qty-badge"><?php echo $item['qty']; ?></span> <strong><?php echo esc_html($item['name']); ?></strong></td>
                                    <td>£<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['qty']; ?></td>
                                    <td style="text-align:right; font-weight:700;">£<?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-list">
                    <div class="summary-line"><label>Subtotal (£)</label><span>£<?php echo number_format($order->subtotal, 2); ?></span></div>
                    
                    <?php if($rest_discount_pct > 0): ?>
                    <div class="summary-line" style="color:var(--res-success);"><label>Restaurant Discount (<?php echo $rest_discount_pct; ?>%)</label><span>-£<?php echo number_format($discount_amount, 2); ?></span></div>
                    <?php endif; ?>

                    <div class="summary-line"><label>Service Charge (£)</label><span>£<?php echo number_format($service_fee, 2); ?></span></div>
                    
                    <?php if($order_type === 'delivery'): ?>
                    <div class="summary-line"><label>Delivery Charge (£)</label><span>£<?php echo number_format($delivery_charge, 2); ?></span></div>
                    <?php endif; ?>

                    <div class="summary-line"><label>Bag Charge (£)</label><span>£<?php echo number_format($bag_fee, 2); ?></span></div>
                    
                    <?php if($tip_amount > 0): ?>
                    <div class="summary-line" style="color:var(--res-blue);"><label>Driver Tip (£)</label><span>£<?php echo number_format($tip_amount, 2); ?></span></div>
                    <?php endif; ?>

                    <div class="summary-line grand-total"><label>Grand Total</label><span>£<?php echo number_format($order->total_price, 2); ?></span></div>
                </div>
            </div>

            <?php if(!empty($order->kitchen_notes)): ?>
                <div class="view-card" style="border-left: 5px solid #ef4444; background: #fffefe;">
                    <div class="view-card-header" style="background: #fef2f2;">
                        <h2 style="color: #991b1b;"><span class="dashicons dashicons-food" style="margin-right:8px;"></span>Notes for Kitchen</h2>
                    </div>
                    <div class="view-card-body">
                        <div style="font-size:16px; color:#1d2327; line-height:1.6; font-weight: 600;">
                            <?php echo nl2br(esc_html($order->kitchen_notes)); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($order_type === 'delivery' && !empty($order->delivery_notes)): ?>
                <div class="view-card" style="border-left: 5px solid #3b82f6; background: #f8fafc;">
                    <div class="view-card-header" style="background: #eff6ff;">
                        <h2 style="color: #1e40af;"><span class="dashicons dashicons-location-alt" style="margin-right:8px;"></span>Notes for Delivery</h2>
                    </div>
                    <div class="view-card-body">
                        <div style="font-size:15px; color:#1d2327; line-height:1.6;">
                            <?php echo nl2br(esc_html($order->delivery_notes)); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <div class="sidebar-column">

       
    <div class="view-card">
        <div class="view-card-header"><h2>Customer Details</h2></div>
        <div class="view-card-body">
            <?php 
            // 1. Initialize variables with Order data as the default
            $display_name  = $order->full_name;
            $display_phone = $order->phone;
            $display_email = $order->email;

            // 2. If customer_id > 0, try to override with official Customer Profile data
            if (!empty($order->customer_id) && $order->customer_id > 0) {
                $user_id = $order->customer_id;
                
                // Fetching standard WP User Meta (adjust keys if you use custom ones)
                $meta_first = get_user_meta($user_id, 'first_name', true);
                $meta_last  = get_user_meta($user_id, 'last_name', true);
                $meta_phone = get_user_meta($user_id, 'billing_phone', true); // Common key
                $user_data  = get_userdata($user_id);

                if (!empty($meta_first)) { $display_name = $meta_first . ' ' . $meta_last; }
                if (!empty($meta_phone)) { $display_phone = $meta_phone; }
                if (!empty($user_data->user_email)) { $display_email = $user_data->user_email; }
            }
            ?>

            <div class="info-block">
                <label>Full Name</label>
                <p><?php echo esc_html($display_name ?: 'Guest'); ?></p>
            </div>
            
            <div class="info-block">
                <label>Phone Number</label>
                <p style="color:var(--res-red); font-size:18px; font-weight:800;">
                    <?php echo esc_html($display_phone ?: '-'); ?>
                </p>
            </div>
            
            <div class="info-block">
                <label>Email Address</label>
                <p><?php echo esc_html($display_email ?: '-'); ?></p>
            </div>
            
            <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

            <div class="info-block">
                <label>Payment Method</label>
                <p style="text-transform:uppercase; color:var(--res-success); font-weight:800;">
                    <?php echo esc_html($order->payment_method); ?> (<?php echo esc_html($order->payment_status); ?>)
                </p>
                <div class="type-box <?php echo esc_attr($order->order_type); ?>">
                    <span class="dashicons dashicons-<?php echo ($order->order_type === 'delivery') ? 'location' : 'store'; ?>" style="font-size:18px; margin-top:2px; vertical-align: middle;"></span>
                    <?php echo strtoupper(esc_html($order->order_type)); ?>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

            <div class="info-block">
                <label>Location Details</label>
                <?php if ($order->order_type === 'delivery'): ?>
                <div class="addr-pill">
                    <?php 
                    $address_fields = [
                        'Flat No.'  => $order->flat_no,
                        'Building'  => $order->building_name,
                        'Door No.'  => $order->door_no,
                        'Road Name' => $order->road_name,
                    ];

                    foreach ($address_fields as $label => $value) : ?>
                        <?php if (!empty($value) && $value !== 'null') : ?>
                        <div class="addr-row">
                            <span><?php echo $label; ?></span> 
                            <strong><?php echo esc_html($value); ?></strong>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="addr-row" style="border:0; margin-top:10px;">
                        <span>Postcode</span> 
                        <span style="font-size:18px; color:var(--res-red); font-weight:900;">
                            <?php echo strtoupper(esc_html($order->postcode)); ?>
                        </span>
                    </div>
                </div>
                <?php else: ?>
                    <div style="text-align:center; padding:15px; border: 2px dashed #fbbf24; border-radius:12px; background:#fffdf2;">
                        <span class="dashicons dashicons-store" style="font-size:30px; color:#b45309;"></span>
                        <div style="font-weight:800; color:#b45309; margin-top:5px;">STORE PICKUP</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<script>
jQuery(document).ready(function($){
    var serverTime = <?php echo $server_now; ?>;
    var browserTime = Math.floor(Date.now() / 1000);
    var timeGap = serverTime - browserTime;

    function updateViewClock() {
        var now = Math.floor(Date.now() / 1000) + timeGap;
        var timerEl = $('#view-timer-js');
        if (timerEl.length) {
            var expiry = parseInt(timerEl.data('expiry'));
            var diff = expiry - now;
            if (diff <= 0) {
                timerEl.addClass('timer-late').find('.time-string').text("LATE");
            } else {
                var m = Math.floor(diff / 60);
                var s = diff % 60;
                timerEl.find('.time-string').text((m < 10 ? "0"+m : m) + ":" + (s < 10 ? "0"+s : s));
            }
        }
    }
    setInterval(updateViewClock, 1000);
    updateViewClock();
});

function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    alert("Address copied!");
}
</script>