<?php
/**
 * Edit & View Order - Complete Master Version
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';
$afon_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$afon_order_id) {
    echo '<div class="notice notice-error"><p>Invalid Order Reference.</p></div>';
    return;
}

/*--------------------------------------------------------------
# 1. DELETE LOGIC
--------------------------------------------------------------*/
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    check_admin_referer('delete_order_' . $afon_order_id);
    $wpdb->delete($table_name, ['id' => $afon_order_id]);
    echo '<div class="updated notice is-dismissible"><p>Order deleted successfully.</p></div>';
    echo '<a href="?page=awesome_food_delivery&tab=orders" class="button">Back to Orders</a>';
    return;
}

/*--------------------------------------------------------------
# 2. DATABASE AUTO-REPAIR (Updated for Collection Charges/Discounts)
--------------------------------------------------------------*/
$required_columns = [
    'kitchen_notes'       => "TEXT NOT NULL AFTER `address`",
    'delivery_notes'      => "TEXT NOT NULL AFTER `kitchen_notes`",
    'tip_amount'          => "DECIMAL(10,2) DEFAULT '0.00' AFTER `delivery_fee`",
    'service_fee'         => "DECIMAL(10,2) DEFAULT '0.00' AFTER `tip_amount`",
    'bag_fee'             => "DECIMAL(10,2) DEFAULT '0.00' AFTER `service_fee`",
    'delivery_discount'   => "DECIMAL(10,2) DEFAULT '0.00' AFTER `tip_amount` ", // Ensure this matches your column name
    'collection_charge'   => "DECIMAL(10,2) DEFAULT '0.00' AFTER `bag_fee` ",    // ADDED
    'collection_discount' => "DECIMAL(10,2) DEFAULT '0.00' AFTER `collection_charge` ", // ADDED
    'delay_message'       => "TEXT NULL AFTER `scheduled_time`",
    'postcode'            => "VARCHAR(20) NULL AFTER `address`",
    'order_type'          => "VARCHAR(50) DEFAULT 'delivery' AFTER `order_status`",
    'display_id'          => "VARCHAR(50) NULL AFTER `id`",
    'payment_method'      => "VARCHAR(50) DEFAULT 'Cash' AFTER `total_price`",
    'payment_status'      => "VARCHAR(50) DEFAULT 'Unpaid' AFTER `payment_method`",
    'customer_email'      => "VARCHAR(100) NULL AFTER `phone`", 
    'customer_notes'      => "TEXT NULL AFTER `delivery_notes` text"
];

foreach ($required_columns as $col => $definition) {
    $check = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE '$col'");
    if (empty($check)) {
        $wpdb->query("ALTER TABLE `$table_name` ADD `$col` $definition;");
    }
}

/*--------------------------------------------------------------
# 3. SAVE LOGIC (Fixed for Collection & Persistence)
--------------------------------------------------------------*/
if (isset($_POST['afon_update_order'])) {
    check_admin_referer('afon_update_order_action', 'afon_update_order_nonce');

    $afon_updated_items = [];
    if (isset($_POST['afon_items_name']) && is_array($_POST['afon_items_name'])) {
        foreach ($_POST['afon_items_name'] as $idx => $name) {
            if (!empty($name)) {
                $afon_updated_items[] = [
                    'name'   => sanitize_text_field($name),
                    'qty'    => intval($_POST['afon_items_qty'][$idx]),
                    'price'  => floatval($_POST['afon_items_price'][$idx]), 
                    'vPrice' => floatval($_POST['afon_items_vprice'][$idx]),
                    'vName'  => isset($_POST['afon_items_vname'][$idx]) ? sanitize_text_field($_POST['afon_items_vname'][$idx]) : ''
                ];
            }
        }
    }

    $mins_input = intval($_POST['afon_scheduled_time']);
    $prep_time_setting = intval(get_option('afd_cooking_time', 20)); 
    $new_anchor_ts = current_time('timestamp') + (($mins_input - $prep_time_setting) * 60);
    $new_order_date = date('Y-m-d H:i:s', $new_anchor_ts);

    $update_data = [
        'kitchen_notes'       => isset($_POST['afon_kitchen_notes']) ? sanitize_textarea_field($_POST['afon_kitchen_notes']) : '',
        'delivery_notes'      => isset($_POST['afon_delivery_notes']) ? sanitize_textarea_field($_POST['afon_delivery_notes']) : '',
        'delay_message'       => isset($_POST['afon_delay_message']) ? sanitize_textarea_field($_POST['afon_delay_message']) : '', 
        'order_status'        => sanitize_text_field($_POST['afon_status']),
        'order_type'          => sanitize_text_field($_POST['afon_order_type']),
        'items_json'          => json_encode($afon_updated_items),
        'delivery_charge'     => floatval($_POST['afon_delivery_fee'] ?? 0),
        'service_fee'         => floatval($_POST['afon_service_fee'] ?? 0),
        'bag_fee'             => floatval($_POST['afon_bag_fee'] ?? 0),
        'collection_charge'   => floatval($_POST['afon_collection_charge'] ?? 0), // SAVING COLLECTION CHARGE
        'collection_discount' => floatval($_POST['afon_collection_discount'] ?? 0), // SAVING COLLECTION DISCOUNT
        'delivery_discount'   => floatval($_POST['afon_discount_amt'] ?? 0),
        'tip_amount'          => floatval($_POST['afon_tip_amount']),
        'total_price'         => floatval($_POST['afon_total_price']), 
        'scheduled_time'      => $mins_input,
        'order_date'          => $new_order_date,
        'payment_method'      => sanitize_text_field($_POST['afon_payment_method']),
        'payment_status'      => sanitize_text_field($_POST['afon_payment_status']),
        'customer_notes'      => isset($_POST['afon_customer_notes']) ? sanitize_textarea_field($_POST['afon_customer_notes']) : '',
    ];

    $wpdb->update($table_name, $update_data, ['id' => $afon_order_id]);
    echo '<div class="updated notice is-dismissible"><p>Order updated successfully.</p></div>';
    
    // Refresh $order object to show new values immediately
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
}

/*--------------------------------------------------------------
# 4. DATA RETRIEVAL & MAPPING
--------------------------------------------------------------*/
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
if (!$order) { echo '<div class="notice notice-error"><p>Order not found.</p></div>'; return; }

$afon_items = json_decode($order->items_json, true) ?: [];
$display_id = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;
$order_type = isset($order->order_type) ? strtolower($order->order_type) : 'delivery';
$server_now = current_time('timestamp');

// --- Fetch User Meta for Address ---
$customer_id = $order->customer_id;
$u_flat      = get_user_meta($customer_id, 'fd_flat_no', true);
$u_building  = get_user_meta($customer_id, 'fd_building', true);
$u_door      = get_user_meta($customer_id, 'fd_door_no', true);
$u_road      = get_user_meta($customer_id, 'fd_road_name', true);
$u_postcode  = get_user_meta($customer_id, 'fd_user_postcode', true);
$u_notes     = get_user_meta($customer_id, 'address', true);

// Construct Full Address String for Clipboard
$full_address_string = trim(implode(', ', array_filter([
    $u_flat ? 'Flat ' . $u_flat : '',
    $u_door ? 'Door ' . $u_door : '',
    $u_building,
    $u_road,
    $order->postcode ? $order->postcode : $u_postcode,
    $u_notes
])));

// Global settings
$service_fee_val = (float)get_option('afd_service_charge', '0.00');
$bag_fee_val = (float)get_option('afd_bag_charge', '0.00');
$discount_pct = (float)get_option('afd_restaurant_discount', '0.00');
$prep_mins = intval(get_option('afd_cooking_time', 20));

// Timer Calculation
$expiry_timestamp = strtotime($order->order_date) + ($prep_mins * 60);

// Base URLs
$base_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $afon_order_id);
$delete_url = wp_nonce_url($base_url . '&action=delete', 'delete_order_' . $afon_order_id);
$invoice_url = $base_url . '&action=print&type=customer';
?>

<div class="edit-order-wrap">
    <form method="post" id="master-edit-form">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <div style="background:#fff; padding:12px 20px; border-radius:12px; border:1px solid var(--clr-border); box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <span style="font-size:11px; font-weight:700; color:#94a3b8; display:block; text-transform:uppercase;">Order Reference</span>
                <span style="font-size:26px; font-weight:900; color:var(--clr-primary);">#<?php echo esc_html($display_id); ?></span>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="<?php echo $invoice_url; ?>" target="_blank" class="btn-action">
                    <span class="dashicons dashicons-printer"></span> Print Invoice
                </a>
                <a href="?page=awesome_food_delivery&tab=orders" class="btn-v">← Back to Orders</a>
                <button type="button" id="add-new-item" class="btn-v" style="color:var(--clr-blue); border-color: var(--clr-blue);">+ Add Item</button>
            </div>
        </div>

        <div class="view-grid">
            <div class="main-column">
                <div class="view-card">
                    <div class="view-card-header"><h2>Order Line Items</h2></div>
                    <div class="view-card-body" style="padding:0;">
                        <table class="view-table" id="items-table">
                            <thead>
                                <tr><th>Item Description</th><th>Variant</th><th width="120">Price</th><th width="80">Qty</th><th width="100" style="text-align:right;">Subtotal</th><th width="40"></th></tr>
                            </thead>
                            <tbody>
    <?php if(!empty($afon_items)): foreach($afon_items as $item): 

        $vPrice = isset($item['vPrice']) ? (float)$item['vPrice'] : 0;
        $basePrice = isset($item['price']) ? (float)$item['price'] : 0;
        $qty = intval($item['qty']);
        $row_total = ($basePrice + $vPrice) * $qty;
    ?>
        <tr class="item-row">
            <td><input type="text" name="afon_items_name[]" value="<?php echo esc_attr($item['name']); ?>" class="edit-input"></td>
            
            <td>
    <div style="display:flex; flex-direction:column; gap:5px;">
        <input type="text" name="afon_items_vname[]" value="<?php echo esc_attr($item['vName'] ?? ''); ?>" placeholder="Variant (e.g. Large)" class="edit-input" style="font-size:12px; padding:5px;">
        <div class="input-with-symbol">
            <input type="number" step="1" name="afon_items_vprice[]" value="<?php echo number_format($vPrice, 2, '.', ''); ?>" class="edit-input variant-trigger">
        </div>
    </div>
</td>

            <td>
                <div class="input-with-symbol">
                    <span>£</span>
                    <input type="number" step="1" name="afon_items_price[]" value="<?php echo number_format($basePrice, 2, '.', ''); ?>" class="edit-input price-trigger">
                </div>
            </td>

            <td><input type="number" name="afon_items_qty[]" value="<?php echo $qty; ?>" class="edit-input qty-trigger"></td>

            <td style="text-align:right; font-weight:700;">
                £<span class="row-subtotal"><?php echo number_format($row_total, 2, '.', ''); ?></span>
            </td>

            <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:red; cursor:pointer;"></span></td>
        </tr>
    <?php endforeach; endif; ?>
</tbody>
                        </table>

                        <div style="padding:25px; background:#fcfcfd; border-top:1px solid var(--clr-border);">
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:20px;">
                                <?php if ($order_type === 'delivery') : ?>
    <div id="delivery-fee-wrapper">
        <label class="field-label">Delivery Fee</label>
        <div class="input-with-symbol">
            <span>£</span>
            <input type="number" step="1" name="afon_delivery_fee" 
                   value="<?php echo number_format($order->delivery_charge, 2, '.', ''); ?>" 
                   class="edit-input charge-trigger">
        </div>
    </div>

<?php elseif ($order_type === 'collection' || $order_type === 'pickup') : ?>
    <div id="collection-fee-wrapper"> <label class="field-label">Collection Fee</label>
        <div class="input-with-symbol">
            <span>£</span>
            <input type="number" step="1" name="afon_collection_fee" 
                   value="<?php echo number_format($order->collection_charge, 2, '.', ''); ?>" 
                   class="edit-input charge-trigger">
        </div>
    </div>
<?php endif; ?>
                                <div>
    <label class="field-label">Service Fee</label>
    <div class="input-with-symbol">
        <span>£</span>
        <input type="number" step="0.01" name="afon_service_fee" id="afon_service_fee" value="<?php echo number_format($order->service_fee, 2, '.', ''); ?>" class="edit-input charge-trigger">
    </div>
</div>

<div>
    <label class="field-label">Bag Fee</label>
    <div class="input-with-symbol">
        <span>£</span>
        <input type="number" step="0.01" name="afon_bag_fee" id="afon_bag_fee" value="<?php echo number_format($order->bag_fee, 2, '.', ''); ?>" class="edit-input charge-trigger">
    </div>
</div>
                                
                               <?php if ($order_type === 'delivery') : ?>
    <div id="discount-wrapper">
        <label class="field-label">Discount Value (£)</label>
        <div class="input-with-symbol">
            <span>£</span>
            <input type="number" step="0.01" name="afon_discount_amt" id="afon_discount_amt" 
                   value="<?php echo number_format($order->delivery_discount, 2, '.', ''); ?>" 
                   class="edit-input discount-trigger">
        </div>
    </div>
<?php else : ?>
    <div id="discount-wrapper">
        <label class="field-label">Discount Value (£)</label>
        <div class="input-with-symbol">
            <span>£</span>
            <input type="number" step="0.01" name="afon_discount_amt" id="afon_discount_amt" 
                   value="<?php echo number_format($order->collection_discount, 2, '.', ''); ?>" 
                   class="edit-input discount-trigger">
        </div>
    </div>
<?php endif; ?>
                                
                                <div><label class="field-label">Driver Tip</label><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_tip_amount" value="<?php echo number_format($order->tip_amount, 2, '.', ''); ?>" class="edit-input charge-trigger"></div></div>
                            </div>
                            
                            <div class="grand-total-box">
                                <label class="field-label">Final Amount to Pay</label>
                                <span style="font-size:32px; font-weight:900; color:var(--clr-primary);">£</span>
                                <input type="number" step="0.01" name="afon_total_price" id="final-total" class="final-price-input" value="<?php echo number_format(floatval($order->total_price), 2, '.', ''); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="view-card" style="border-left:4px solid #ef4444;">
                        <div class="view-card-header"><h2>Kitchen Notes (Internal)</h2></div>
                        <div class="view-card-body"><textarea name="afon_kitchen_notes" class="edit-input" rows="4"><?php echo esc_textarea($order->kitchen_notes); ?></textarea></div>
                    </div>
                    <div class="view-card" style="border-left:4px solid var(--clr-blue);">
                        <div class="view-card-header"><h2>Delivery Notes (For Rider)</h2></div>
                        <div class="view-card-body"><textarea name="afon_delivery_notes" class="edit-input" rows="4"><?php echo esc_textarea($order->delivery_notes); ?></textarea></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-column">
                <div class="view-card" style="border-top: 4px solid var(--clr-primary);">
                    <div class="view-card-header"><h2>Order Control</h2></div>
                    <div class="view-card-body">
                        <label class="field-label">Minutes until Due</label>
                        <input type="number" name="afon_scheduled_time" value="<?php echo intval($order->scheduled_time); ?>" class="edit-input" style="font-size:28px; font-weight:900; text-align:center; height:60px; color:var(--clr-primary); margin-bottom:15px;">
                        
                        <?php if (strtolower($order->order_status) === 'cooking') : ?>
                            <label class="field-label">Live Timer</label>
                            <div class="view-live-timer" id="view-timer-js" data-expiry="<?php echo $expiry_timestamp; ?>">
                                <span class="dashicons dashicons-clock"></span> 
                                <span class="time-string">--:--</span>
                            </div>
                            <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                        <?php endif; ?>

                        <label class="field-label">Order Status</label>
                        <select name="afon_status" class="edit-input" style="font-weight:700; height:45px; margin-bottom:20px;">
                            <option value="pending" <?php selected($order->order_status, 'pending'); ?>>🔴 PENDING</option>
                            <option value="cooking" <?php selected($order->order_status, 'cooking'); ?>>🟠 COOKING</option>
                            <option value="rider" <?php selected($order->order_status, 'rider'); ?>>🔵 RIDER ASSIGNED</option>
                            <option value="completed" <?php selected($order->order_status, 'completed'); ?>>🟢 COMPLETED</option>
                        </select>
                        
                        <label class="field-label">Order Type</label>
                        <select name="afon_order_type" class="edit-input" style="font-weight:700; height:45px; margin-bottom:20px;">
    
    <option value="delivery" <?php if($order_type === 'delivery') { echo 'selected'; } ?>>
        🚚 DELIVERY
    </option>

    <option value="collection" <?php if($order_type === 'collection') { echo 'selected'; } ?>>
        🏪 COLLECTION
    </option>
    
</select>

                        <button type="submit" name="afon_update_order" class="btn-save">💾 SAVE ALL CHANGES</button>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Payment</h2></div>
                    <div class="view-card-body">
                        <div class="info-grid">
                            <div><label class="field-label">Method</label><select name="afon_payment_method" class="edit-input"><option value="Cash" <?php selected($order->payment_method, 'Cash'); ?>>Cash</option><option value="Card" <?php selected($order->payment_method, 'Card'); ?>>Card</option><option value="Online" <?php selected($order->payment_method, 'Online'); ?>>Online</option></select></div>
                            <div><label class="field-label">Status</label><select name="afon_payment_status" class="edit-input"><option value="Unpaid" <?php selected($order->payment_status, 'Unpaid'); ?>>Unpaid</option><option value="Paid" <?php selected($order->payment_status, 'Paid'); ?>>Paid</option></select></div>
                        </div>
                    </div>
                </div>

                <div class="view-card">
    <div class="view-card-header"><h2>Customer Details</h2></div>
    <div class="view-card-body">
        <?php 
        // 1. Set Defaults from the Order Object
        $final_name  = $order->full_name;
        $final_phone = $order->phone;
        
        // 2. Address Fallbacks (u_ variables)
        // If the 'u_' variables are empty, use the $order object columns
        $disp_flat     = !empty($u_flat)     ? $u_flat     : ($order->flat_no ?? '-');
        $disp_door     = !empty($u_door)     ? $u_door     : ($order->door_no ?? '-');
        $disp_building = !empty($u_building) ? $u_building : ($order->building_name ?? '-');
        $disp_road     = !empty($u_road)     ? $u_road     : ($order->road_name ?? '-');
        $disp_post     = !empty($order->postcode) ? $order->postcode : ($u_postcode ?? '-');

        // 3. Optional: Override with Customer Meta ONLY if customer_id is not 0
        if (!empty($order->customer_id) && $order->customer_id > 0) {
            $user_meta_phone = get_user_meta($order->customer_id, 'billing_phone', true);
            $user_data       = get_userdata($order->customer_id);
            
            if (!empty($user_data->display_name)) { $final_name = $user_data->display_name; }
            if (!empty($user_meta_phone)) { $final_phone = $user_meta_phone; }
        }
        ?>

        <label class="field-label">Name</label>
        <div class="readonly-text" style="margin-bottom:10px;">
            <?php echo esc_html($final_name ?: 'Guest'); ?>
        </div>
        
        <label class="field-label">Phone</label>
        <div class="readonly-text" style="margin-bottom:10px; color:var(--clr-blue); font-weight:700;">
            <?php echo esc_html($final_phone ?: '-'); ?>
        </div>

        <label class="field-label">Email</label>
        <div class="readonly-text" style="margin-bottom:10px;font-weight:700;">
            <?php echo esc_html($order->email); ?>
        </div>
        
        <div class="type-box <?php echo esc_attr($order_type); ?>">
            <?php echo strtoupper(esc_html($order_type)); ?>
        </div>

        <?php if ($order_type === 'delivery'): ?>
        <div class="addr-pill">
            <div class="addr-row"><span>Flat:</span> <span class="addr-val"><?php echo esc_html($disp_flat); ?></span></div>
            <div class="addr-row"><span>Door:</span> <span class="addr-val"><?php echo esc_html($disp_door); ?></span></div>
            <div class="addr-row"><span>Building:</span> <span class="addr-val"><?php echo esc_html($disp_building); ?></span></div>
            <div class="addr-row"><span>Road:</span> <span class="addr-val"><?php echo esc_html($disp_road); ?></span></div>
            <div class="addr-row"><span>Postcode:</span> <span class="addr-val" style="font-weight:900; color:var(--clr-primary);"><?php echo strtoupper(esc_html($disp_post)); ?></span></div>
            
            <button type="button" onclick="copyToClipboard('<?php echo esc_js($full_address_string); ?>')" class="btn-action" style="width:100%; margin-top:10px; justify-content:center;">
                📋 Copy Full Address
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

                <div class="view-card" style="border:1px solid #f5c2c7; background:#fff8f8;">
                    <div class="view-card-body" style="text-align:center;">
                        <a href="<?php echo $delete_url; ?>" class="btn-action btn-delete" style="width:100%; justify-content:center;" onclick="return confirm('Delete this order permanently?')">
                            <span class="dashicons dashicons-trash"></span> Delete Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {

    /*--------------------------------------------------------------
    # 1. TIMER LOGIC (Server-Synced)
    --------------------------------------------------------------*/
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
                var m = Math.floor(diff / 60), s = diff % 60;
                timerEl.find('.time-string').text((m < 10 ? "0"+m : m) + ":" + (s < 10 ? "0"+s : s));
            }
        }
    }
    setInterval(updateViewClock, 1000); 
    updateViewClock();

    /*--------------------------------------------------------------
    # 2. CALCULATION LOGIC
    --------------------------------------------------------------*/
    function calculate() {
        let itemsSubtotal = 0;

        // Loop through each item row
        $('.item-row').each(function() {
            let basePrice    = parseFloat($(this).find('.price-trigger').val()) || 0;
            let variantPrice = parseFloat($(this).find('.variant-trigger').val()) || 0;
            let qty          = parseFloat($(this).find('.qty-trigger').val()) || 0;

            let rowTotal = (basePrice + variantPrice) * qty;
            $(this).find('.row-subtotal').text(rowTotal.toFixed(2));
            itemsSubtotal += rowTotal;
        });

        // Determine Active Charge (Checks which field exists in the DOM)
        let deliveryFee   = parseFloat($('input[name="afon_delivery_fee"]').val()) || 0;
        let collectionFee = parseFloat($('input[name="afon_collection_fee"]').val()) || 0;
        
        // If delivery exists, use it; otherwise use collection
        let activeCharge = ($('input[name="afon_delivery_fee"]').length > 0) ? deliveryFee : collectionFee;

        // Determine Active Discount
        let activeDiscount = parseFloat($('#afon_discount_amt').val()) || 0;

        // Fixed Fees
        let service = parseFloat($('#afon_service_fee').val()) || 0;
        let bag     = parseFloat($('#afon_bag_fee').val()) || 0;
        let tip     = parseFloat($('input[name="afon_tip_amount"]').val()) || 0;

        // Final Total Calculation
        // Formula: (Items - Discount) + Charge + Service + Bag + Tip
        let finalTotal = (itemsSubtotal - activeDiscount) + activeCharge + service + bag + tip;
        
        if (finalTotal < 0) finalTotal = 0;

        // Update the Read-Only Total Field
        $('#final-total').val(finalTotal.toFixed(2));
    }

    /*--------------------------------------------------------------
    # 3. EVENT LISTENERS
    --------------------------------------------------------------*/
    
    // Listen for any input changes in the entire calculation area
    $(document).on('input change', '.price-trigger, .variant-trigger, .qty-trigger, .charge-trigger, .discount-trigger', function() { 
        calculate(); 
    });

    // Handle Adding New Items
    $('#add-new-item').click(function() {
        let row = `
        <tr class="item-row">
            <td><input type="text" name="afon_items_name[]" class="edit-input" placeholder="Item name"></td>
            <td>
                <div class="input-with-symbol">
                    <span>+£</span>
                    <input type="number" step="1" name="afon_items_vprice[]" value="0.00" class="edit-input variant-trigger">
                </div>
            </td>
            <td>
                <div class="input-with-symbol">
                    <span>£</span>
                    <input type="number" step="1" name="afon_items_price[]" value="0.00" class="edit-input price-trigger">
                </div>
            </td>
            <td><input type="number" name="afon_items_qty[]" value="1" class="edit-input qty-trigger"></td>
            <td style="text-align:right; font-weight:700;">£<span class="row-subtotal">0.00</span></td>
            <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:red; cursor:pointer;"></span></td>
        </tr>`;
        $('#items-table tbody').append(row);
        calculate();
    });

    // Handle Removing Items
    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) { 
            $(this).closest('tr').remove(); 
            calculate(); 
        }
    });

    /*--------------------------------------------------------------
    # 4. INITIALIZATION
    --------------------------------------------------------------*/
    calculate();

});

/*--------------------------------------------------------------
# 5. UTILITY FUNCTIONS
--------------------------------------------------------------*/
function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    alert("Address copied to clipboard!");
}
</script>

<style>
    :root { --clr-primary: #ef4444; --clr-blue: #2563eb; --clr-border: #e2e8f0; --clr-dark: #1e293b; --res-red: #d63638; }
    .edit-order-wrap { margin: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .view-grid { display: grid; grid-template-columns: 1fr 280px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--clr-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .view-card-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid var(--clr-border); display: flex; align-items: center; justify-content: space-between; }
    .view-card-header h2 { margin: 0; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
    .view-card-body { padding: 20px; }
    .edit-input { width: 100%; border: 1px solid var(--clr-border); border-radius: 8px; padding: 10px; font-size: 14px; }
    label.field-label { font-size: 11px; font-weight: 800; color: #64748b; display: block; margin-bottom: 6px; text-transform: uppercase; }
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px; background: #f8fafc; font-size: 11px; border-bottom: 1px solid var(--clr-border); color: #64748b; }
    .view-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
    .input-with-symbol { position: relative; display: flex; align-items: center; }
    .input-with-symbol span { position: absolute; left: 12px; font-weight: 700; color: #94a3b8; }
    .input-with-symbol input { padding-left: 28px; font-weight: 600; }
    .btn-v { text-decoration: none; padding: 8px 15px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid #ccd0d4; background: #fff; }
    .btn-save { background: var(--clr-primary); color: #fff; border: none; width: 100%; height: 50px; font-size: 15px; border-radius: 10px; cursor: pointer; font-weight: 700;}
    .btn-action { text-decoration: none; padding: 10px 15px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #ccd0d4; background: #fff; color: #2c3338; cursor: pointer; }
    .btn-delete { color: var(--res-red); border-color: #f5c2c7; background: #fff8f8; }
    .grand-total-box { text-align: right; border-top: 2px solid var(--clr-dark); padding-top: 15px; }
    .final-price-input { font-size: 32px; font-weight: 900; color: var(--clr-primary); text-align: right; border: none; background: transparent; width: 180px; pointer-events: none; }
    .addr-pill { background: #f1f5f9; padding: 15px; border-radius: 10px; border: 1px solid var(--clr-border); margin-top: 10px; font-size: 13px;}
    .addr-row { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding: 6px 0; }
    .addr-val { font-weight: 500; text-align: right; }
    .type-box { display: block; width: 100%; text-align: center; padding: 10px; border-radius: 8px; font-weight: 800; font-size: 14px; margin-top: 10px; }
    .type-box.delivery { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .type-box.pickup { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
    .view-live-timer { background: #fff8e5; border: 2px solid #ffb900; color: #c45100; padding: 10px 20px; border-radius: 8px; font-family: monospace; font-size: 20px; font-weight: 900; display: inline-flex; align-items: center; gap: 8px; margin-top:10px; }
    .view-live-timer.timer-late { background: #fcf0f1; color: #d63638; border-color: #d63638; animation: afd-pulse 1s infinite; }
    @keyframes afd-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .readonly-text { font-size: 14px; font-weight: 600; color: #1e293b; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; word-break: break-all; }
        body { font-family: sans-serif; padding: 20px; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; }
        .invoice-box table { width: 100%; text-align: left; border-collapse: collapse; margin-top: 20px; }
        .invoice-box table td, .invoice-box table th { padding: 10px; border-bottom: 1px solid #eee; }
        .invoice-box table th { background: #f9f9f9; }
        .total-row { font-weight: bold; font-size: 1.2em; border-top: 2px solid #333; }
        .no-print { background: #2271b1; color: white; padding: 10px 20px; border: none; cursor: pointer; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 20px;}
        @media print { .no-print { display: none; } }
    </style>