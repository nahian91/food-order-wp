<?php
if (!defined('ABSPATH')) exit;

/**
 * Edit Order - Unified Admin UI (Permanent ID Version)
 */
global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';
$afon_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$afon_order_id) {
    echo '<div class="notice notice-error"><p>Invalid Order Resource.</p></div>';
    return;
}

// 1. UPDATE LOGIC
if (isset($_POST['afon_update_order'])) {
    check_admin_referer('afon_update_order_action', 'afon_update_order_nonce');

    $afon_updated_items = [];
    if (isset($_POST['afon_items_name']) && is_array($_POST['afon_items_name'])) {
        foreach ($_POST['afon_items_name'] as $afon_index => $afon_name) {
            if (!empty($afon_name)) {
                $afon_updated_items[] = [
                    'name'  => sanitize_text_field($afon_name),
                    'qty'   => intval($_POST['afon_items_qty'][$afon_index]),
                    'price' => floatval($_POST['afon_items_price'][$afon_index]) 
                ];
            }
        }
    }

    $update_data = [
        'full_name'    => sanitize_text_field($_POST['afon_customer_name']),
        'phone'        => sanitize_text_field($_POST['afon_customer_phone']),
        'address'      => sanitize_textarea_field($_POST['afon_customer_address']),
        'notes'        => sanitize_textarea_field($_POST['afon_notes']),
        'order_status' => sanitize_text_field($_POST['afon_status']),
        'items_json'   => json_encode($afon_updated_items),
        'total_price'  => floatval($_POST['afon_total_price'])
    ];

    $wpdb->update($table_name, $update_data, ['id' => $afon_order_id]);
    echo '<div class="updated notice is-dismissible"><p>Order updated successfully.</p></div>';
}

// 2. DATA RETRIEVAL
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
if (!$order) { return; }
$afon_items = json_decode($order->items_json, true) ?: [];

/**
 * 3. PERMANENT ID RETRIEVAL
 * Fetches the static ID saved during checkout.
 */
$display_id = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;
?>

<style>
    :root { --res-red: #d63638; --res-dark: #1d2327; --res-border: #ccd0d4; --res-bg: #f0f2f5; }
    .view-order-wrap { margin: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    
    /* Header & Quick Actions */
    .view-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
    .id-badge-large { background: #fff; border: 1px solid var(--res-border); padding: 10px 20px; border-radius: 12px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .action-group { display: flex; gap: 10px; }

    /* Layout */
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--res-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .view-card-header { padding: 18px 25px; border-bottom: 1px solid #f0f0f1; background: #fafafa; display: flex; align-items: center; justify-content: space-between; }
    .view-card-header h2 { margin: 0; font-size: 15px; font-weight: 700; color: var(--res-dark); text-transform: uppercase; letter-spacing: 0.5px; }
    .view-card-body { padding: 25px; }

    /* Editable Table */
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8f9fa; color: #646970; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .view-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
    
    .edit-input { width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; font-size: 14px; }
    .edit-input:focus { border-color: var(--res-red); outline: none; box-shadow: 0 0 0 1px var(--res-red); }

    /* Totals Box */
    .totals-area { background: #fdfdfd; padding: 25px; text-align: right; border-top: 2px solid #f0f0f1; }
    .total-label { color: #646970; font-weight: 600; font-size: 14px; margin-right: 15px; }
    .total-amount-input { color: var(--res-red); font-size: 32px; font-weight: 800; border: none; background: transparent; width: 150px; text-align: right; pointer-events: none; outline: none; }

    .info-block { margin-bottom: 20px; }
    .info-block label { display: block; font-size: 11px; color: #646970; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
    
    .btn-v { text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: 1px solid #ccd0d4; background: #fff; color: #2c3338; cursor: pointer; }
    .btn-v:hover { background: #f6f7f7; border-color: #a7aaad; }
    .btn-v-red { background: var(--res-red); color: #fff !important; border: none; width: 100%; justify-content: center; }
    .btn-v-red:hover { background: #b52a2c; }
    
    .remove-item { color: #d63638; cursor: pointer; }
    .remove-item:hover { color: #000; }
</style>

<div class="view-order-wrap">
    <form method="post" id="afon-edit-form">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>
        
        <div class="view-header">
            <div>
                <div class="id-badge-large">
                    <span style="color:#646970; font-size:12px; font-weight:700;">EDITING ORDER:</span>
                    <span style="font-weight:800; color:var(--res-red); font-size:18px; margin-left:5px;">#<?php echo esc_html($display_id); ?></span>
                </div>
                <p style="margin:10px 0 0; color:#646970;"><span class="dashicons dashicons-calendar-alt" style="font-size:16px;"></span> Ordered on <?php echo date('F j, Y', strtotime($order->order_date)); ?></p>
            </div>
            
            <div class="action-group">
                <a href="?page=awesome_food_delivery&tab=orders&order_id=<?php echo $afon_order_id; ?>&action=view" class="btn-v"><span class="dashicons dashicons-visibility"></span> View Mode</a>
                <button type="button" id="add-new-item" class="btn-v" style="background:#f0f6ff; color:#2271b1; border-color:#c2d7ef;"><span class="dashicons dashicons-plus-alt2"></span> Add Item</button>
            </div>
        </div>

        <div class="view-grid">
            <div class="main-column">
                
                <div class="view-card">
                    <div class="view-card-header">
                        <h2>Order Items</h2>
                    </div>
                    <div class="view-card-body" style="padding:0;">
                        <table class="view-table" id="items-table">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th width="100">Price (£)</th>
                                    <th width="100">Qty</th>
                                    <th width="120" style="text-align:right;">Subtotal</th>
                                    <th width="40"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($afon_items)): foreach($afon_items as $item): ?>
                                    <tr class="item-row">
                                        <td><input type="text" name="afon_items_name[]" value="<?php echo esc_attr($item['name']); ?>" class="edit-input" placeholder="Item Name"></td>
                                        <td><input type="number" step="0.01" name="afon_items_price[]" value="<?php echo number_format($item['price'], 2, '.', ''); ?>" class="edit-input price-trigger"></td>
                                        <td><input type="number" name="afon_items_qty[]" value="<?php echo intval($item['qty']); ?>" class="edit-input qty-trigger" min="1"></td>
                                        <td style="text-align:right; font-weight:700;">£<span class="row-subtotal"><?php echo number_format($item['qty'] * $item['price'], 2); ?></span></td>
                                        <td style="text-align:center;"><span class="dashicons dashicons-no-alt remove-item"></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="totals-area">
                        <span class="total-label">ORDER TOTAL</span>
                        <input type="number" step="0.01" name="afon_total_price" id="final-total" class="total-amount-input" value="<?php echo number_format(floatval($order->total_price), 2, '.', ''); ?>" readonly>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Special Instructions / Notes</h2></div>
                    <div class="view-card-body">
                        <textarea name="afon_notes" class="edit-input" rows="4" style="padding:15px; background:#fff8e5; border-left:5px solid #ffb900;" placeholder="Chef notes..."><?php echo esc_textarea($order->notes); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="sidebar-column">
                
                <div class="view-card">
                    <div class="view-card-header"><h2>Customer Details</h2></div>
                    <div class="view-card-body">
                        <div class="info-block">
                            <label>Full Name</label>
                            <input type="text" name="afon_customer_name" value="<?php echo esc_attr($order->full_name); ?>" class="edit-input">
                        </div>
                        <div class="info-block">
                            <label>Phone Number</label>
                            <input type="text" name="afon_customer_phone" value="<?php echo esc_attr($order->phone); ?>" class="edit-input" style="color:var(--res-red); font-weight:700;">
                        </div>
                        <hr style="border:0; border-top:1px solid #f0f0f1; margin:20px 0;">
                        <div class="info-block">
                            <label>Delivery Address</label>
                            <textarea name="afon_customer_address" class="edit-input" rows="3"><?php echo esc_textarea($order->address); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Save Changes</h2></div>
                    <div class="view-card-body">
                        <div class="info-block">
                            <label>Order Status</label>
                            <select name="afon_status" class="edit-input" style="font-weight:700;">
                                <option value="pending" <?php selected($order->order_status, 'pending'); ?>>⏳ PENDING</option>
                                <option value="processing" <?php selected($order->order_status, 'processing'); ?>>👨‍🍳 PROCESSING</option>
                                <option value="completed" <?php selected($order->order_status, 'completed'); ?>>✅ COMPLETED</option>
                                <option value="cancelled" <?php selected($order->order_status, 'cancelled'); ?>>❌ CANCELLED</option>
                            </select>
                        </div>
                        <button type="submit" name="afon_update_order" class="btn-v btn-v-red">
                            <span class="dashicons dashicons-saved"></span> Update Order Record
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    function calculate() {
        let grandTotal = 0;
        $('.item-row').each(function() {
            let p = parseFloat($(this).find('.price-trigger').val()) || 0;
            let q = parseFloat($(this).find('.qty-trigger').val()) || 0;
            let sub = p * q;
            $(this).find('.row-subtotal').text(sub.toFixed(2));
            grandTotal += sub;
        });
        $('#final-total').val(grandTotal.toFixed(2));
    }

    $('#add-new-item').on('click', function() {
        let row = `<tr class="item-row">
            <td><input type="text" name="afon_items_name[]" class="edit-input" placeholder="New Item"></td>
            <td><input type="number" step="0.01" name="afon_items_price[]" value="0.00" class="edit-input price-trigger"></td>
            <td><input type="number" name="afon_items_qty[]" value="1" class="edit-input qty-trigger" min="1"></td>
            <td style="text-align:right; font-weight:700;">£<span class="row-subtotal">0.00</span></td>
            <td style="text-align:center;"><span class="dashicons dashicons-no-alt remove-item"></span></td>
        </tr>`;
        $('#items-table tbody').append(row);
    });

    $(document).on('click', '.remove-item', function() {
        if($('.item-row').length > 1) { $(this).closest('tr').remove(); calculate(); }
    });

    $(document).on('input', '.price-trigger, .qty-trigger', calculate);
});
</script>