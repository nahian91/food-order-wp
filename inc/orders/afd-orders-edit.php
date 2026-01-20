<?php
/**
 * Edit Order - Final Version
 * Features: DB Auto-Sync, Live Calculations, Minute-Based Kitchen Timer Sync
 * Customer Info: Read-Only (Show after Order Items)
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

    // TIMER CALCULATION
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
        'total_price'    => floatval($_POST['afon_total_price']),
        'scheduled_time' => $mins_input,
        'order_date'     => $new_order_date,
    ];

    $result = $wpdb->update($table_name, $update_data, ['id' => $afon_order_id]);

    if ($result !== false) {
        echo '<div class="updated notice is-dismissible"><p>Order Updated successfully.</p></div>';
    }
}

/*--------------------------------------------------------------
# 3. DATA RETRIEVAL
--------------------------------------------------------------*/
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
if (!$order) { echo "Order not found."; return; }

$afon_items = json_decode($order->items_json, true) ?: [];
$display_id = !empty($order->display_id) ? $order->display_id : $order->id;
?>

<style>
    :root { --clr-primary: #ef4444; --clr-dark: #1e293b; --clr-border: #e2e8f0; }
    .view-order-wrap { margin: 20px; font-family: sans-serif; color: var(--clr-dark); }
    .view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .id-badge { background: #fff; border: 1px solid var(--clr-border); padding: 12px 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--clr-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .view-card-header { padding: 15px 25px; border-bottom: 1px solid var(--clr-border); background: #fcfcfd; }
    .view-card-header h2 { margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #64748b; }
    .view-card-body { padding: 25px; }
    .edit-input { width: 100%; border: 1px solid var(--clr-border); border-radius: 8px; padding: 10px 12px; box-sizing: border-box; }
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8fafc; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--clr-border); }
    .view-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; }
    .btn-v { text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1px solid var(--clr-border); background: #fff; }
    .btn-save { background: var(--clr-primary); color: #fff !important; border: none; width: 100%; justify-content: center; height: 45px; margin-top: 10px; }
    .total-amount-display { font-size: 32px; font-weight: 900; color: var(--clr-primary); text-align: right; border: none; width: 100%; background: transparent; }
    label.field-label { font-size: 11px; font-weight: 800; color: #64748b; display: block; margin-bottom: 5px; text-transform: uppercase; }
    
    /* Static Info Styles */
    .static-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .static-val { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 15px; }
</style>

<div class="view-order-wrap">
    <form method="post">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>
        <div class="view-header">
            <div class="id-badge">
                <span style="font-size:11px; font-weight:700; color:#94a3b8; display:block;">EDIT ORDER</span>
                <span style="font-size:24px; font-weight:900; color:var(--clr-primary);">#<?php echo esc_html($display_id); ?></span>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="?page=awesome_food_delivery&tab=orders" class="btn-v">Back</a>
                <button type="button" id="add-new-item" class="btn-v" style="color: #3b82f6;">+ Add Item</button>
            </div>
        </div>

        <div class="view-grid">
            <div class="main-column">
                
                <div class="view-card">
                    <div class="view-card-header"><h2>Order Items</h2></div>
                    <div class="view-card-body" style="padding:0;">
                        <table class="view-table" id="items-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th width="100">Price</th>
                                    <th width="80">Qty</th>
                                    <th width="100" style="text-align:right;">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($afon_items)): foreach($afon_items as $item): ?>
                                    <tr class="item-row">
                                        <td><input type="text" name="afon_items_name[]" value="<?php echo esc_attr($item['name']); ?>" class="edit-input"></td>
                                        <td><input type="number" step="0.01" name="afon_items_price[]" value="<?php echo number_format($item['price'], 2, '.', ''); ?>" class="edit-input price-trigger"></td>
                                        <td><input type="number" name="afon_items_qty[]" value="<?php echo intval($item['qty']); ?>" class="edit-input qty-trigger"></td>
                                        <td style="text-align:right; font-weight:700;">£<span class="row-subtotal"><?php echo number_format($item['qty'] * $item['price'], 2); ?></span></td>
                                        <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:#ef4444; cursor:pointer;"></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                        <div style="padding: 25px; background: #fafafa; text-align: right; border-top: 1px solid var(--clr-border);">
                            <label class="field-label">Grand Total</label>
                            <input type="number" step="0.01" name="afon_total_price" id="final-total" class="total-amount-display" value="<?php echo number_format(floatval($order->total_price), 2, '.', ''); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Customer Details (Reference)</h2></div>
                    <div class="view-card-body">
                        <div class="static-info-grid">
                            <div>
                                <label class="field-label">Customer Name</label>
                                <div class="static-val"><?php echo esc_html($order->full_name); ?></div>
                            </div>
                            <div>
                                <label class="field-label">Phone Number</label>
                                <div class="static-val" style="color:var(--clr-primary);"><?php echo esc_html($order->phone); ?></div>
                            </div>
                        </div>
                        <div class="static-info-grid">
                            <div>
                                <label class="field-label">Email Address</label>
                                <div class="static-val"><?php echo esc_html($order->email); ?></div>
                            </div>
                            <div>
                                <label class="field-label">Delivery Type</label>
                                <div class="static-val" style="text-transform: uppercase;"><?php echo esc_html($order->order_type); ?></div>
                            </div>
                        </div>
                        <div style="margin-top:10px;">
                            <label class="field-label">Delivery Address</label>
                            <div class="static-val" style="line-height:1.5; color:#475569;">
                                <?php echo $order->address ? nl2br(esc_html($order->address)) : '<em>Pickup / No address provided</em>'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Internal Kitchen Notes</h2></div>
                    <div class="view-card-body">
                        <textarea name="afon_notes" class="edit-input" rows="4" style="background:#fffbeb;"><?php echo esc_textarea($order->notes); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="sidebar-column">
                <div class="view-card" style="border-top: 4px solid var(--clr-primary);">
                    <div class="view-card-header"><h2>Kitchen Timer</h2></div>
                    <div class="view-card-body">
                        <div style="margin-bottom:20px;">
                            <label class="field-label">Due In (Minutes)</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="number" name="afon_scheduled_time" 
                                       value="<?php echo intval($order->scheduled_time); ?>" 
                                       class="edit-input" 
                                       style="font-weight:bold; font-size:24px; color:var(--clr-primary); text-align:center; width:120px;">
                                <span style="font-weight:800; color:#64748b;">MINS</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="field-label">Customer Delay Message</label>
                            <textarea name="afon_delay_message" class="edit-input" rows="4"><?php echo esc_textarea($order->delay_message); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Order Status</h2></div>
                    <div class="view-card-body">
                        <div style="margin-bottom:20px;">
                            <label class="field-label">Current Status</label>
                            <select name="afon_status" class="edit-input" style="font-weight:700; height:45px;">
                                <option value="pending" <?php selected($order->order_status, 'pending'); ?>>🔴 PENDING</option>
                                <option value="cooking" <?php selected($order->order_status, 'cooking'); ?>>🟠 COOKING</option>
                                <option value="rider" <?php selected($order->order_status, 'rider'); ?>>🔵 RIDER</option>
                                <option value="completed" <?php selected($order->order_status, 'completed'); ?>>🟢 COMPLETED</option>
                            </select>
                        </div>
                        <button type="submit" name="afon_update_order" class="btn-v btn-save">SAVE CHANGES</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    function calculate() {
        let total = 0;
        $('.item-row').each(function() {
            let p = parseFloat($(this).find('.price-trigger').val()) || 0;
            let q = parseFloat($(this).find('.qty-trigger').val()) || 0;
            let sub = p * q;
            $(this).find('.row-subtotal').text(sub.toFixed(2));
            total += sub;
        });
        $('#final-total').val(total.toFixed(2));
    }

    $('#add-new-item').on('click', function() {
        let row = `<tr class="item-row">
            <td><input type="text" name="afon_items_name[]" class="edit-input" placeholder="New Item"></td>
            <td><input type="number" step="0.01" name="afon_items_price[]" value="0.00" class="edit-input price-trigger"></td>
            <td><input type="number" name="afon_items_qty[]" value="1" class="edit-input qty-trigger"></td>
            <td style="text-align:right; font-weight:700;">£<span class="row-subtotal">0.00</span></td>
            <td style="text-align:center;"><span class="dashicons dashicons-trash remove-item" style="color:#ef4444; cursor:pointer;"></span></td>
        </tr>`;
        $('#items-table tbody').append(row);
    });

    $(document).on('click', '.remove-item', function() {
        if($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculate();
        }
    });

    $(document).on('input', '.price-trigger, .qty-trigger', calculate);
});
</script>