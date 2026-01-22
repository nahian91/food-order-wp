<?php
/**
 * Edit Order - Complete Integrated Version
 * Features: 
 * - DB Auto-Sync & Repair
 * - Live Financial Calculations (Items + Charges - Discounts)
 * - Minute-Based Kitchen Timer Sync
 * - Full Address Display (Read-Only Reference)
 * - Customer & Internal Notes management
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
# 1. DATABASE AUTO-REPAIR (Ensures columns exist)
--------------------------------------------------------------*/
$column_check = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'delay_message'");
if (empty($column_check)) {
    $wpdb->query("ALTER TABLE `$table_name` ADD `delay_message` TEXT NOT NULL AFTER `scheduled_time`;");
}

/*--------------------------------------------------------------
# 2. SAVE LOGIC
--------------------------------------------------------------*/
if (isset($_POST['afon_update_order'])) {
    check_admin_referer('afon_update_order_action', 'afon_update_order_nonce');

    // Process Order Items from Table
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

    // TIMER SYNC: Recalculate order_date based on "Due In" minutes
    $mins_input = intval($_POST['afon_scheduled_time']);
    $prep_time_setting = intval(get_option('afd_cooking_time', 20)); 
    $new_anchor_ts = current_time('timestamp') + (($mins_input - $prep_time_setting) * 60);
    $new_order_date = date('Y-m-d H:i:s', $new_anchor_ts);

    // Update Database
    $update_data = [
        'notes'          => sanitize_textarea_field($_POST['afon_notes']),
        'delay_message'  => sanitize_textarea_field($_POST['afon_delay_message']), 
        'order_status'   => sanitize_text_field($_POST['afon_status']),
        'items_json'     => json_encode($afon_updated_items),
        'delivery_fee'   => floatval($_POST['afon_delivery_fee']),
        'total_price'    => floatval($_POST['afon_total_price']), // Calculated by JS
        'scheduled_time' => $mins_input,
        'order_date'     => $new_order_date,
    ];

    $result = $wpdb->update($table_name, $update_data, ['id' => $afon_order_id]);

    if ($result !== false) {
        echo '<div class="updated notice is-dismissible"><p>Order #'.$afon_order_id.' updated successfully.</p></div>';
    }
}

/*--------------------------------------------------------------
# 3. DATA RETRIEVAL
--------------------------------------------------------------*/
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
if (!$order) { echo "Order not found."; return; }

$afon_items = json_decode($order->items_json, true) ?: [];
$display_id = !empty($order->display_id) ? $order->display_id : $order->id;

// Current Charge Defaults (for reference if order fields are null)
$service_fee_val = (float)get_option('afd_service_charge', '0.00');
$bag_fee_val     = (float)get_option('afd_bag_charge', '0.00');
$discount_val    = (float)get_option('afd_restaurant_discount', '0.00');
?>

<style>
    :root { 
        --clr-primary: #ef4444; 
        --clr-dark: #1e293b; 
        --clr-border: #e2e8f0; 
        --clr-blue: #2271b1;
        --clr-bg: #f8fafc;
    }
    .edit-order-wrap { margin: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: var(--clr-dark); }
    
    /* Header */
    .view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .id-badge { background: #fff; border: 1px solid var(--clr-border); padding: 12px 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    
    /* Grid System */
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--clr-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .view-card-header { padding: 15px 25px; border-bottom: 1px solid var(--clr-border); background: #fcfcfd; }
    .view-card-header h2 { margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #64748b; }
    .view-card-body { padding: 25px; }

    /* Form Elements */
    .edit-input { width: 100%; border: 1px solid var(--clr-border); border-radius: 8px; padding: 10px 12px; box-sizing: border-box; font-size: 14px; }
    .edit-input:focus { border-color: var(--clr-blue); outline: none; box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.1); }
    label.field-label { font-size: 11px; font-weight: 800; color: #64748b; display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Items Table */
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--clr-border); color: #64748b; }
    .view-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

    /* Charges Grid */
    .charge-section { background: #fcfcfd; padding: 25px; border-top: 1px solid var(--clr-border); }
    .charge-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px; }
    .input-with-symbol { position: relative; display: flex; align-items: center; }
    .input-with-symbol span { position: absolute; left: 12px; font-weight: bold; color: #94a3b8; }
    .input-with-symbol input { padding-left: 28px; font-weight: 600; }

    /* Financials Display */
    .total-display-wrapper { text-align: right; border-top: 2px solid var(--clr-dark); padding-top: 15px; }
    .total-amount-display { font-size: 36px; font-weight: 900; color: var(--clr-primary); text-align: right; border: none; width: 200px; background: transparent; pointer-events: none; }

    /* Address & Notes */
    .full-address-display { background: var(--clr-bg); padding: 15px; border-radius: 8px; border-left: 4px solid var(--clr-blue); font-size: 15px; line-height: 1.6; font-weight: 700; color: var(--clr-dark); }
    
    /* Buttons */
    .btn-v { text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1px solid var(--clr-border); background: #fff; color: var(--clr-dark); transition: all 0.2s; }
    .btn-v:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btn-save { background: var(--clr-primary); color: #fff !important; border: none; width: 100%; justify-content: center; height: 50px; font-size: 15px; margin-top: 10px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); }
    .btn-save:hover { background: #dc2626; transform: translateY(-1px); }

    .static-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .static-val { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 15px; }
</style>

<div class="edit-order-wrap">
    <form method="post" id="afon-edit-form">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>
        
        <div class="view-header">
            <div class="id-badge">
                <span style="font-size:11px; font-weight:700; color:#94a3b8; display:block;">MANAGE ORDER</span>
                <span style="font-size:24px; font-weight:900; color:var(--clr-primary);">#<?php echo esc_html($display_id); ?></span>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="?page=awesome_food_delivery&tab=orders" class="btn-v">Back to List</a>
                <button type="button" id="add-new-item" class="btn-v" style="color: #3b82f6; border-color: #3b82f6;">+ Add Menu Item</button>
            </div>
        </div>

        <div class="view-grid">
            <div class="main-column">
                
                <div class="view-card">
                    <div class="view-card-header"><h2>Order Items & Financials</h2></div>
                    <div class="view-card-body" style="padding:0;">
                        <table class="view-table" id="items-table">
                            <thead>
                                <tr>
                                    <th>Item Description</th>
                                    <th width="120">Unit Price</th>
                                    <th width="100">Qty</th>
                                    <th width="120" style="text-align:right;">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($afon_items)): foreach($afon_items as $item): ?>
                                    <tr class="item-row">
                                        <td><input type="text" name="afon_items_name[]" value="<?php echo esc_attr($item['name']); ?>" class="edit-input" placeholder="Item name..."></td>
                                        <td><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_items_price[]" value="<?php echo number_format($item['price'], 2, '.', ''); ?>" class="edit-input price-trigger"></div></td>
                                        <td><input type="number" name="afon_items_qty[]" value="<?php echo intval($item['qty']); ?>" class="edit-input qty-trigger"></td>
                                        <td style="text-align:right; font-weight:700;">£<span class="row-subtotal"><?php echo number_format($item['qty'] * $item['price'], 2); ?></span></td>
                                        <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:var(--clr-primary); cursor:pointer;"></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>

                        <div class="charge-section">
                            <div class="charge-grid">
                                <div>
                                    <label class="field-label">Delivery Charge (£)</label>
                                    <div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_delivery_fee" value="<?php echo number_format($order->delivery_fee, 2, '.', ''); ?>" class="edit-input charge-trigger"></div>
                                </div>
                                <div>
                                    <label class="field-label">Service Charge (£)</label>
                                    <div class="input-with-symbol"><span>£</span><input type="number" step="0.01" id="afon_service_fee" value="<?php echo number_format($service_fee_val, 2, '.', ''); ?>" class="edit-input charge-trigger"></div>
                                </div>
                                <div>
                                    <label class="field-label">Bag Charge (£)</label>
                                    <div class="input-with-symbol"><span>£</span><input type="number" step="0.01" id="afon_bag_fee" value="<?php echo number_format($bag_fee_val, 2, '.', ''); ?>" class="edit-input charge-trigger"></div>
                                </div>
                                <div>
                                    <label class="field-label">Restaurant Discount (£)</label>
                                    <div class="input-with-symbol"><span>£</span><input type="number" step="0.01" id="afon_discount" value="<?php echo number_format($discount_val, 2, '.', ''); ?>" class="edit-input charge-trigger" style="color:var(--clr-primary);"></div>
                                </div>
                            </div>

                            <div class="total-display-wrapper">
                                <label class="field-label">Final Grand Total</label>
                                <div style="display:flex; align-items:center; justify-content:flex-end;">
                                    <span style="font-size:32px; font-weight:900; color:var(--clr-primary); margin-right:5px;">£</span>
                                    <input type="number" step="0.01" name="afon_total_price" id="final-total" class="total-amount-display" value="<?php echo number_format(floatval($order->total_price), 2, '.', ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Customer & Delivery Reference</h2></div>
                    <div class="view-card-body">
                        <div class="static-info-grid">
                            <div>
                                <label class="field-label">Customer Name</label>
                                <div class="static-val"><?php echo esc_html($order->full_name); ?></div>
                            </div>
                            <div>
                                <label class="field-label">Contact Number</label>
                                <div class="static-val" style="color:var(--clr-blue);"><?php echo esc_html($order->phone); ?></div>
                            </div>
                        </div>
                        <div style="margin-top:10px;">
                            <label class="field-label">Full Delivery Address</label>
                            <div class="full-address-display">
                                <?php echo $order->address ? nl2br(esc_html($order->address)) : '<em>Store Pickup / No address</em>'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Internal Kitchen Notes</h2></div>
                    <div class="view-card-body">
                        <textarea name="afon_notes" class="edit-input" rows="4" style="background:#fffbeb; border-color:#fef3c7;" placeholder="Add private notes for kitchen staff..."><?php echo esc_textarea($order->notes); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="sidebar-column">
                
                <div class="view-card" style="border-top: 4px solid var(--clr-primary);">
                    <div class="view-card-header"><h2>Kitchen Timer</h2></div>
                    <div class="view-card-body">
                        <div style="margin-bottom:20px;">
                            <label class="field-label">Minutes until Due</label>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <input type="number" name="afon_scheduled_time" 
                                       value="<?php echo intval($order->scheduled_time); ?>" 
                                       class="edit-input" 
                                       style="font-weight:900; font-size:28px; color:var(--clr-primary); text-align:center; width:110px; height:60px;">
                                <span style="font-weight:800; color:#94a3b8; font-size:14px;">MINS</span>
                            </div>
                            <p style="font-size:11px; color:#94a3b8; margin-top:8px;">Updating this resets the live countdown for the customer.</p>
                        </div>
                        
                        <div style="border-top:1px solid #f1f5f9; padding-top:15px;">
                            <label class="field-label">Push Delay Message to App</label>
                            <textarea name="afon_delay_message" class="edit-input" rows="4" placeholder="e.g. Traffic delay, will be with you in 10 mins..."><?php echo esc_textarea($order->delay_message); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Order Status</h2></div>
                    <div class="view-card-body">
                        <div style="margin-bottom:20px;">
                            <label class="field-label">Current Progress</label>
                            <select name="afon_status" class="edit-input" style="font-weight:700; height:48px; font-size:14px;">
                                <option value="pending" <?php selected($order->order_status, 'pending'); ?>>🔴 PENDING</option>
                                <option value="cooking" <?php selected($order->order_status, 'cooking'); ?>>🟠 COOKING</option>
                                <option value="rider" <?php selected($order->order_status, 'rider'); ?>>🔵 RIDER ASSIGNED</option>
                                <option value="completed" <?php selected($order->order_status, 'completed'); ?>>🟢 COMPLETED</option>
                            </select>
                        </div>
                        <button type="submit" name="afon_update_order" class="btn-v btn-save">💾 SAVE ALL CHANGES</button>
                    </div>
                </div>

                <div style="text-align:center;">
                    <p style="font-size:11px; color:#94a3b8;">Order created: <?php echo $order->order_date; ?></p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    /**
     * Live Calculation Engine
     * Updates Subtotals, Total Items, Charges, and Grand Total
     */
    function calculate() {
        let itemsTotal = 0;
        
        // 1. Calculate Item Rows
        $('.item-row').each(function() {
            let price = parseFloat($(this).find('.price-trigger').val()) || 0;
            let qty = parseFloat($(this).find('.qty-trigger').val()) || 0;
            let sub = price * qty;
            $(this).find('.row-subtotal').text(sub.toFixed(2));
            itemsTotal += sub;
        });

        // 2. Get Additional Charges
        let delivery = parseFloat($('input[name="afon_delivery_fee"]').val()) || 0;
        let service  = parseFloat($('#afon_service_fee').val()) || 0;
        let bag      = parseFloat($('#afon_bag_fee').val()) || 0;
        let discount = parseFloat($('#afon_discount').val()) || 0;

        // 3. Grand Total Logic
        let grandTotal = (itemsTotal + delivery + service + bag) - discount;
        
        // 4. Update UI
        $('#final-total').val(grandTotal.toFixed(2));
    }

    /**
     * Add New Row Functionality
     */
    $('#add-new-item').on('click', function() {
        let row = `
        <tr class="item-row">
            <td><input type="text" name="afon_items_name[]" class="edit-input" placeholder="New Item Name"></td>
            <td><div class="input-with-symbol"><span>£</span><input type="number" step="0.01" name="afon_items_price[]" value="0.00" class="edit-input price-trigger"></div></td>
            <td><input type="number" name="afon_items_qty[]" value="1" class="edit-input qty-trigger"></td>
            <td style="text-align:right; font-weight:700;">£<span class="row-subtotal">0.00</span></td>
            <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:#ef4444; cursor:pointer;"></span></td>
        </tr>`;
        $('#items-table tbody').append(row);
        calculate();
    });

    /**
     * Remove Row Functionality
     */
    $(document).on('click', '.remove-item', function() {
        if($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculate();
        } else {
            alert("Order must have at least one item.");
        }
    });

    /**
     * Listeners for Input Changes
     */
    $(document).on('input', '.price-trigger, .qty-trigger, .charge-trigger', calculate);

    // Run initial calc on load
    calculate();
});
</script>