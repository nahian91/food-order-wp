<?php
/**
 * Edit Order - Complete Master Version
 * Features: 
 * - DB Auto-Repair (Fixes Undefined Property & Warning Errors)
 * - Live Financials (Items + Charges + Tips - Editable Discounts)
 * - Two-Way Discount Calculation (% <-> £)
 * - Kitchen Timer Sync
 * - Dual Notes (Internal & Delivery)
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
# 1. DATABASE AUTO-REPAIR
# Checks and creates missing columns to prevent PHP Warnings
--------------------------------------------------------------*/
$required_columns = [
    'delivery_notes' => "TEXT NULL AFTER `notes`",
    'tip_amount'     => "DECIMAL(10,2) DEFAULT '0.00' AFTER `delivery_fee`",
    'delay_message'  => "TEXT NULL AFTER `scheduled_time`"
];

foreach ($required_columns as $col => $definition) {
    $check = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE '$col'");
    if (empty($check)) {
        $wpdb->query("ALTER TABLE `$table_name` ADD `$col` $definition;");
    }
}

/*--------------------------------------------------------------
# 2. SAVE LOGIC
--------------------------------------------------------------*/
if (isset($_POST['afon_update_order'])) {
    check_admin_referer('afon_update_order_action', 'afon_update_order_nonce');

    // Process Order Items
    $afon_updated_items = [];
    if (isset($_POST['afon_items_name']) && is_array($_POST['afon_items_name'])) {
        foreach ($_POST['afon_items_name'] as $idx => $name) {
            if (!empty($name)) {
                $afon_updated_items[] = [
                    'name'  => sanitize_text_field($name),
                    'qty'   => intval($_POST['afon_items_qty'][$idx]),
                    'price' => floatval($_POST['afon_items_price'][$idx]) 
                ];
            }
        }
    }

    // Timer Sync Logic
    $mins_input = intval($_POST['afon_scheduled_time']);
    $prep_time_setting = intval(get_option('afd_cooking_time', 20)); 
    $new_anchor_ts = current_time('timestamp') + (($mins_input - $prep_time_setting) * 60);
    $new_order_date = date('Y-m-d H:i:s', $new_anchor_ts);

    $update_data = [
        'notes'          => sanitize_textarea_field($_POST['afon_notes']),
        'delivery_notes' => sanitize_textarea_field($_POST['afon_delivery_notes']),
        'delay_message'  => sanitize_textarea_field($_POST['afon_delay_message']), 
        'order_status'   => sanitize_text_field($_POST['afon_status']),
        'items_json'     => json_encode($afon_updated_items),
        'delivery_fee'   => floatval($_POST['afon_delivery_fee']),
        'tip_amount'     => floatval($_POST['afon_tip_amount']),
        'total_price'    => floatval($_POST['afon_total_price']), 
        'scheduled_time' => $mins_input,
        'order_date'     => $new_order_date,
    ];

    $wpdb->update($table_name, $update_data, ['id' => $afon_order_id]);
    echo '<div class="updated notice is-dismissible"><p>Order updated and database synced successfully.</p></div>';
}

/*--------------------------------------------------------------
# 3. DATA RETRIEVAL
--------------------------------------------------------------*/
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
if (!$order) { echo "Order not found."; return; }

$afon_items = json_decode($order->items_json, true) ?: [];
$display_id = !empty($order->display_id) ? $order->display_id : $order->id;

// Global settings
$service_fee_val = (float)get_option('afd_service_charge', '0.00');
$bag_fee_val     = (float)get_option('afd_bag_charge', '0.00');
$discount_pct    = (float)get_option('afd_restaurant_discount', '0.00');
?>

<style>
    :root { 
        --clr-primary: #ef4444; 
        --clr-blue: #2563eb; 
        --clr-border: #e2e8f0; 
        --clr-dark: #1e293b;
    }
    .edit-order-wrap { margin: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--clr-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .view-card-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid var(--clr-border); }
    .view-card-header h2 { margin: 0; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
    .view-card-body { padding: 20px; }
    
    .edit-input { width: 100%; border: 1px solid var(--clr-border); border-radius: 8px; padding: 10px; font-size: 14px; transition: border 0.2s; }
    .edit-input:focus { border-color: var(--clr-blue); outline: none; }
    
    label.field-label { font-size: 11px; font-weight: 800; color: #64748b; display: block; margin-bottom: 6px; text-transform: uppercase; }
    
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px; background: #f8fafc; font-size: 11px; border-bottom: 1px solid var(--clr-border); color: #64748b; }
    .view-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }

    .input-with-symbol { position: relative; display: flex; align-items: center; }
    .input-with-symbol span { position: absolute; left: 12px; font-weight: 700; color: #94a3b8; }
    .input-with-symbol input { padding-left: 28px; font-weight: 600; }

    .btn-v { text-decoration: none; padding: 8px 15px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; border: 1px solid #ccd0d4; background: #fff; }
    .btn-save { background: var(--clr-primary); color: #fff; border: none; width: 100%; height: 50px; font-size: 15px; border-radius: 10px; cursor: pointer; font-weight: 700;}
    
    .grand-total-box { text-align: right; border-top: 2px solid var(--clr-dark); padding-top: 15px; }
    .final-price-input { font-size: 32px; font-weight: 900; color: var(--clr-primary); text-align: right; border: none; background: transparent; width: 180px; pointer-events: none; }
</style>

<div class="edit-order-wrap">
    <form method="post" id="master-edit-form">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <div style="background:#fff; padding:12px 20px; border-radius:12px; border:1px solid var(--clr-border);">
                <span style="font-size:11px; font-weight:700; color:#94a3b8; display:block;">ORDER REFERENCE</span>
                <span style="font-size:26px; font-weight:900; color:var(--clr-primary);">#<?php echo esc_html($display_id); ?></span>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="?page=awesome_food_delivery&tab=orders" class="btn-v">← Back to Orders</a>
                <button type="button" id="add-new-item" class="btn-v" style="color:var(--clr-blue); border-color: var(--clr-blue);">+ Add Menu Item</button>
            </div>
        </div>

        <div class="view-grid">
            <div class="main-column">
                <div class="view-card">
                    <div class="view-card-header"><h2>Order Line Items</h2></div>
                    <div class="view-card-body" style="padding:0;">
                        <table class="view-table" id="items-table">
                            <thead>
                                <tr><th>Item Description</th><th width="120">Price</th><th width="80">Qty</th><th width="100" style="text-align:right;">Subtotal</th><th width="40"></th></tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($afon_items)): foreach($afon_items as $item): ?>
                                    <tr class="item-row">
                                        <td><input type="text" name="afon_items_name[]" value="<?php echo esc_attr($item['name']); ?>" class="edit-input"></td>
                                        <td><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_items_price[]" value="<?php echo number_format($item['price'], 2, '.', ''); ?>" class="edit-input price-trigger"></div></td>
                                        <td><input type="number" name="afon_items_qty[]" value="<?php echo intval($item['qty']); ?>" class="edit-input qty-trigger"></td>
                                        <td style="text-align:right; font-weight:700;">£<span class="row-subtotal"><?php echo number_format($item['qty'] * $item['price'], 2); ?></span></td>
                                        <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:red; cursor:pointer;"></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>

                        <div style="padding:25px; background:#fcfcfd; border-top:1px solid var(--clr-border);">
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:20px;">
                                <div><label class="field-label">Delivery Fee</label><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_delivery_fee" value="<?php echo number_format($order->delivery_fee, 2, '.', ''); ?>" class="edit-input charge-trigger"></div></div>
                                <div><label class="field-label">Service Fee</label><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" id="afon_service_fee" value="<?php echo number_format($service_fee_val, 2, '.', ''); ?>" class="edit-input charge-trigger"></div></div>
                                <div><label class="field-label">Bag Fee</label><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" id="afon_bag_fee" value="<?php echo number_format($bag_fee_val, 2, '.', ''); ?>" class="edit-input charge-trigger"></div></div>
                                
                                <div><label class="field-label">Discount (%)</label><input type="number" step="0.01" id="afon_discount_pct" value="<?php echo $discount_pct; ?>" class="edit-input charge-trigger"></div>
                                <div><label class="field-label">Discount Value (£)</label><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" id="afon_discount_amt" value="0.00" class="edit-input" style="color:#16a34a; font-weight:700;"></div></div>
                                
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
                    <div class="view-card" style="border-left:4px solid #f59e0b;">
                        <div class="view-card-header"><h2>Kitchen/Internal Notes</h2></div>
                        <div class="view-card-body"><textarea name="afon_notes" class="edit-input" rows="4" placeholder="Staff only notes..."><?php echo esc_textarea($order->notes); ?></textarea></div>
                    </div>
                    <div class="view-card" style="border-left:4px solid var(--clr-blue);">
                        <div class="view-card-header"><h2>Delivery Notes (For Rider)</h2></div>
                        <div class="view-card-body"><textarea name="afon_delivery_notes" class="edit-input" rows="4" placeholder="Gate codes, directions..."><?php echo esc_textarea($order->delivery_notes); ?></textarea></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-column">
                <div class="view-card" style="border-top: 4px solid var(--clr-primary);">
                    <div class="view-card-header"><h2>Order Control</h2></div>
                    <div class="view-card-body">
                        <label class="field-label">Minutes until Due</label>
                        <input type="number" name="afon_scheduled_time" value="<?php echo intval($order->scheduled_time); ?>" class="edit-input" style="font-size:28px; font-weight:900; text-align:center; height:60px; color:var(--clr-primary); margin-bottom:15px;">
                        
                        <label class="field-label">Order Status</label>
                        <select name="afon_status" class="edit-input" style="font-weight:700; height:45px; margin-bottom:20px;">
                            <option value="pending" <?php selected($order->order_status, 'pending'); ?>>🔴 PENDING</option>
                            <option value="cooking" <?php selected($order->order_status, 'cooking'); ?>>🟠 COOKING</option>
                            <option value="rider" <?php selected($order->order_status, 'rider'); ?>>🔵 RIDER ASSIGNED</option>
                            <option value="completed" <?php selected($order->order_status, 'completed'); ?>>🟢 COMPLETED</option>
                        </select>

                        <button type="submit" name="afon_update_order" class="btn-save">💾 SAVE ALL CHANGES</button>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Customer Details</h2></div>
                    <div class="view-card-body">
                        <p><strong><?php echo esc_html($order->full_name); ?></strong></p>
                        <p style="font-size:12px; color:#64748b; margin-bottom:5px;"><?php echo esc_html($order->email); ?></p>
                        <p style="color:var(--clr-blue); font-weight:700;"><?php echo esc_html($order->phone); ?></p>
                        <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                        <label class="field-label">Delay Message (Customer App)</label>
                        <textarea name="afon_delay_message" class="edit-input" rows="2" placeholder="Inform customer of delays..."><?php echo esc_textarea($order->delay_message); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    function calculate(triggerSource) {
        let itemsTotal = 0;
        $('.item-row').each(function() {
            let p = parseFloat($(this).find('.price-trigger').val()) || 0;
            let q = parseFloat($(this).find('.qty-trigger').val()) || 0;
            let s = p * q;
            $(this).find('.row-subtotal').text(s.toFixed(2));
            itemsTotal += s;
        });

        let delivery = parseFloat($('input[name="afon_delivery_fee"]').val()) || 0;
        let service  = parseFloat($('#afon_service_fee').val()) || 0;
        let bag      = parseFloat($('#afon_bag_fee').val()) || 0;
        let tip      = parseFloat($('input[name="afon_tip_amount"]').val()) || 0;
        
        let dPct     = parseFloat($('#afon_discount_pct').val()) || 0;
        let dAmt     = parseFloat($('#afon_discount_amt').val()) || 0;

        // Sync Percent to Amount unless Amount was manually edited
        if(triggerSource !== 'amt') {
            dAmt = (itemsTotal * dPct) / 100;
            $('#afon_discount_amt').val(dAmt.toFixed(2));
        } else {
            // Sync Amount to Percent
            if(itemsTotal > 0) {
                dPct = (dAmt / itemsTotal) * 100;
                $('#afon_discount_pct').val(dPct.toFixed(2));
            }
        }

        let finalTotal = (itemsTotal + delivery + service + bag + tip) - dAmt;
        $('#final-total').val(finalTotal.toFixed(2));
    }

    // Input Listeners
    $(document).on('input', '.price-trigger, .qty-trigger, .charge-trigger, #afon_discount_pct', function() { calculate('pct'); });
    $(document).on('input', '#afon_discount_amt', function() { calculate('amt'); });

    // Dynamic Row Logic
    $('#add-new-item').click(function() {
        let row = `<tr class="item-row">
            <td><input type="text" name="afon_items_name[]" class="edit-input" placeholder="Enter item name"></td>
            <td><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_items_price[]" value="0.00" class="edit-input price-trigger"></div></td>
            <td><input type="number" name="afon_items_qty[]" value="1" class="edit-input qty-trigger"></td>
            <td style="text-align:right; font-weight:700;">£<span class="row-subtotal">0.00</span></td>
            <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:red; cursor:pointer;"></span></td>
        </tr>`;
        $('#items-table tbody').append(row);
        calculate('pct');
    });

    $(document).on('click', '.remove-item', function() {
        if($('.item-row').length > 1) { 
            $(this).closest('tr').remove(); 
            calculate('pct'); 
        }
    });

    // Initial Calculation
    calculate('pct');
});
</script>