<?php
if (!defined('ABSPATH')) exit;

/**
 * Edit Order - Unified Admin UI (Premium Modern Version)
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
$display_id = !empty($order->display_id) ? $order->display_id : $order->id;
?>

<style>
    :root { 
        --clr-primary: #ef4444; 
        --clr-dark: #1e293b; 
        --clr-border: #e2e8f0; 
        --clr-bg: #f8fafc; 
    }
    .view-order-wrap { margin: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: var(--clr-dark); }
    
    /* Header Styles */
    .view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .id-badge { background: #fff; border: 1px solid var(--clr-border); padding: 12px 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    
    /* Grid Layout */
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--clr-border); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; }
    .view-card-header { padding: 18px 25px; border-bottom: 1px solid var(--clr-border); background: #fcfcfd; }
    .view-card-header h2 { margin: 0; font-size: 14px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
    .view-card-body { padding: 25px; }

    /* Form Inputs */
    .edit-input { width: 100%; border: 1px solid var(--clr-border); border-radius: 8px; padding: 10px 12px; font-size: 14px; transition: 0.2s; }
    .edit-input:focus { border-color: var(--clr-primary); outline: none; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); }
    
    /* Table */
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--clr-border); }
    .view-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; }

    /* Buttons */
    .btn-v { text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1px solid var(--clr-border); background: #fff; transition: 0.2s; }
    .btn-v:hover { background: #f8fafc; transform: translateY(-1px); }
    .btn-save { background: var(--clr-primary); color: #fff !important; border: none; width: 100%; justify-content: center; height: 45px; margin-top: 10px; }
    .btn-save:hover { background: #dc2626; box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3); }

    .total-amount-display { font-size: 32px; font-weight: 900; color: var(--clr-primary); text-align: right; border: none; width: 100%; outline: none; background: transparent; }
</style>

<div class="view-order-wrap">
    <form method="post">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>
        
        <div class="view-header">
            <div class="id-badge">
                <span style="font-size:12px; font-weight:700; color:#94a3b8; display:block; margin-bottom:2px;">EDITING ORDER</span>
                <span style="font-size:24px; font-weight:900; color:var(--clr-primary);">#<?php echo esc_html($display_id); ?></span>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="?page=awesome_food_delivery&tab=orders" class="btn-v"><span class="dashicons dashicons-arrow-left-alt"></span> Back to List</a>
                <button type="button" id="add-new-item" class="btn-v" style="color: #3b82f6;"><span class="dashicons dashicons-plus-alt2"></span> Add Item</button>
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
                            <div style="font-size:12px; font-weight:800; color:#64748b; margin-bottom:5px;">GRAND TOTAL</div>
                            <input type="number" step="0.01" name="afon_total_price" id="final-total" class="total-amount-display" value="<?php echo number_format(floatval($order->total_price), 2, '.', ''); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Chef Notes / Instructions</h2></div>
                    <div class="view-card-body">
                        <textarea name="afon_notes" class="edit-input" rows="4" style="background:#fffbeb; border-color:#fde68a;"><?php echo esc_textarea($order->notes); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="sidebar-column">
                <div class="view-card">
                    <div class="view-card-header"><h2>Customer Details</h2></div>
                    <div class="view-card-body">
                        <div style="margin-bottom:15px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b;">FULL NAME</label>
                            <input type="text" name="afon_customer_name" value="<?php echo esc_attr($order->full_name); ?>" class="edit-input">
                        </div>
                        <div style="margin-bottom:15px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b;">PHONE</label>
                            <input type="text" name="afon_customer_phone" value="<?php echo esc_attr($order->phone); ?>" class="edit-input">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:800; color:#64748b;">ADDRESS</label>
                            <textarea name="afon_customer_address" class="edit-input" rows="3"><?php echo esc_textarea($order->address); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="view-card">
                    <div class="view-card-header"><h2>Order Actions</h2></div>
                    <div class="view-card-body">
                        <div style="margin-bottom:20px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b;">CURRENT STATUS</label>
                            <select name="afon_status" class="edit-input" style="font-weight:700; height:40px; background:#f8fafc;">
                                <option value="pending" <?php selected($order->order_status, 'pending'); ?>>🔴 PENDING (New Order)</option>
                                <option value="cooking" <?php selected($order->order_status, 'cooking'); ?>>🟠 COOKING (Kitchen)</option>
                                <option value="rider" <?php selected($order->order_status, 'rider'); ?>>🔵 RIDER (Out for Delivery)</option>
                                <option value="completed" <?php selected($order->order_status, 'completed'); ?>>🟢 COMPLETED</option>
                                <option value="cancelled" <?php selected($order->order_status, 'cancelled'); ?>>⚪ CANCELLED</option>
                            </select>
                        </div>
                        <button type="submit" name="afon_update_order" class="btn-v btn-save">
                            <span class="dashicons dashicons-saved"></span> SAVE CHANGES
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
        if($('.item-row').length > 1) { $(this).closest('tr').remove(); calculate(); }
    });

    $(document).on('input', '.price-trigger, .qty-trigger', calculate);
});
</script>