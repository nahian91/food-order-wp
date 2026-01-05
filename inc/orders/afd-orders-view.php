<?php
/**
 * Final View Order - Unified Admin UI (Updated Status Workflow)
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';
$order_id   = intval($_GET['order_id']);

// 1. DATA FETCHING
$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));

if (!$order) {
    echo '<div class="notice notice-error"><p>Order not found in the database.</p></div>';
    return;
}

// 2. PERMANENT ID LOGIC
$display_id = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;

// Map columns for the template
$afon_customer_name    = $order->full_name;
$afon_customer_phone   = $order->phone;
$afon_customer_address = $order->address;
$afon_notes            = $order->notes;
$afon_status           = strtolower($order->order_status ?: 'pending');
$afon_total            = $order->total_price;
$afon_items            = json_decode($order->items_json, true);

// Action URLs
$base_order_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id);
$edit_url    = $base_order_url . '&action=edit';
$print_url   = $base_order_url . '&action=print&type=customer';
$kitchen_url = $base_order_url . '&action=print&type=kitchen';
$delete_url  = wp_nonce_url($base_order_url . '&action=delete', 'delete_order_' . $order_id);
?>

<style>
    :root { 
        --res-red: #d63638; 
        --res-dark: #1d2327; 
        --res-border: #ccd0d4; 
        --res-kitchen: #eba333; 
        --res-rider: #2271b1;
        --res-success: #46b450;
    }
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

    /* Table */
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8f9fa; color: #646970; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .view-table td { padding: 15px; border-bottom: 1px solid #f0f0f1; }
    .qty-badge { background: #1d2327; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; margin-right: 10px; }

    /* Totals Box */
    .totals-area { background: #fdfdfd; padding: 25px; text-align: right; border-top: 2px solid #f0f0f1; }
    .total-label { color: #646970; font-weight: 600; font-size: 14px; margin-right: 15px; }
    .total-amount { color: var(--res-red); font-size: 32px; font-weight: 800; }

    /* Updated Workflow Status Pills */
    .v-status { padding: 6px 15px; border-radius: 30px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .v-status-pending { background: var(--res-red); color: #fff; }
    .v-status-kitchen { background: var(--res-kitchen); color: #fff; }
    .v-status-rider { background: var(--res-rider); color: #fff; }
    .v-status-completed { background: var(--res-success); color: #fff; }

    /* Sidebar Utils */
    .info-block { margin-bottom: 20px; }
    .info-block label { display: block; font-size: 11px; color: #646970; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
    .info-block p { margin: 0; font-size: 14px; font-weight: 600; color: #1d2327; }

    /* Buttons */
    .btn-v { text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: 1px solid #ccd0d4; background: #fff; color: #2c3338; }
    .btn-v:hover { background: #f6f7f7; border-color: #a7aaad; }
    
    /* Workflow Action Buttons */
    .btn-v-kitchen { background: var(--res-kitchen); color: #fff !important; border: none; }
    .btn-v-rider { background: var(--res-rider); color: #fff !important; border: none; }
    .btn-v-complete { background: var(--res-success); color: #fff !important; border: none; }
</style>

<div class="view-order-wrap">
    
    <div class="view-header">
        <div>
            <div class="id-badge-large">
                <span style="color:#646970; font-size:12px; font-weight:700;">ORDER ID:</span>
                <span style="font-weight:800; color:var(--res-red); font-size:18px; margin-left:5px;">#<?php echo esc_html($display_id); ?></span>
            </div>
            <p style="margin:10px 0 0; color:#646970;"><span class="dashicons dashicons-calendar-alt" style="font-size:16px;"></span> Ordered on <?php echo date('F j, Y \a\t g:i a', strtotime($order->order_date)); ?></p>
        </div>
        
        <div class="action-group">
            <?php 
            // DYNAMIC WORKFLOW BUTTON IN VIEW PAGE
            if ($afon_status === 'pending') : ?>
                <a href="<?php echo esc_url($base_order_url . '&action=update_status&new_status=kitchen'); ?>" class="btn-v btn-v-kitchen"><span class="dashicons dashicons-carrot"></span> Send to Kitchen</a>
            <?php elseif ($afon_status === 'kitchen') : ?>
                <a href="<?php echo esc_url($base_order_url . '&action=update_status&new_status=rider'); ?>" class="btn-v btn-v-rider"><span class="dashicons dashicons-bicycle"></span> Assign Rider</a>
            <?php elseif ($afon_status === 'rider') : ?>
                <a href="<?php echo esc_url($base_order_url . '&action=update_status&new_status=completed'); ?>" class="btn-v btn-v-complete"><span class="dashicons dashicons-yes"></span> Mark Delivered</a>
            <?php endif; ?>

            <a href="?page=awesome_food_delivery&tab=orders" class="btn-v">Back</a>
            <a href="<?php echo esc_url($print_url); ?>" target="_blank" class="btn-v"><span class="dashicons dashicons-printer"></span> Receipt</a>
            <a href="<?php echo $edit_url; ?>" class="btn-v">Edit Order</a>
        </div>
    </div>

    <div class="view-grid">
        <div class="main-column">
            
            <div class="view-card">
                <div class="view-card-header">
                    <h2>Order Summary (<?php echo ucfirst($order->order_type); ?>)</h2>
                    <span class="v-status v-status-<?php echo $afon_status; ?>"><?php echo strtoupper($afon_status); ?></span>
                </div>
                <div class="view-card-body" style="padding:0;">
                    <table class="view-table">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th width="100">Price</th>
                                <th width="100">Qty</th>
                                <th width="120" style="text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($afon_items) && is_array($afon_items)) :
                                foreach ($afon_items as $item) : 
                                    $name  = $item['name'] ?? 'Product';
                                    $qty   = intval($item['qty'] ?? 1);
                                    $price = floatval($item['price'] ?? 0);
                            ?>
                                <tr>
                                    <td><span class="qty-badge"><?php echo $qty; ?></span> <strong><?php echo esc_html($name); ?></strong></td>
                                    <td>£<?php echo number_format($price, 2); ?></td>
                                    <td>&times; <?php echo $qty; ?></td>
                                    <td style="text-align:right; font-weight:700;">£<?php echo number_format($qty * $price, 2); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="4" style="text-align:center; padding:50px; color:#a7aaad;">No items found in this order.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="totals-area">
                    <span class="total-label">ORDER TOTAL</span>
                    <span class="total-amount">£<?php echo number_format(floatval($afon_total), 2); ?></span>
                </div>
            </div>

            <div class="view-card">
                <div class="view-card-header"><h2>Special Instructions / Notes</h2></div>
                <div class="view-card-body">
                    <?php if($afon_notes): ?>
                        <div style="background: #fff8e5; padding: 20px; border-radius: 8px; border-left: 5px solid #ffb900; color: #856404; line-height: 1.6;">
                            <?php echo nl2br(esc_html($afon_notes)); ?>
                        </div>
                    <?php else: ?>
                        <p style="color:#a7aaad; font-style: italic;">No special notes provided for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="sidebar-column">
            
            <div class="view-card">
                <div class="view-card-header"><h2>Customer Details</h2></div>
                <div class="view-card-body">
                    <div class="info-block">
                        <label>Full Name</label>
                        <p><?php echo esc_html($afon_customer_name ?: 'Guest'); ?></p>
                    </div>
                    <div class="info-block">
                        <label>Phone Number</label>
                        <p style="color:var(--res-red); font-size: 18px;"><?php echo esc_html($afon_customer_phone ?: 'Not Provided'); ?></p>
                    </div>
                    <hr style="border:0; border-top:1px solid #f0f0f1; margin:20px 0;">
                    <div class="info-block">
                        <label>Delivery Address</label>
                        <p style="line-height:1.5; color:#444;">
                            <?php echo nl2br(esc_html($afon_customer_address ?: 'Pickup / No Address Provided')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="view-card" style="border-color: #f5c2c7;">
                <div class="view-card-header" style="background: #fff8f8;"><h2>Danger Zone</h2></div>
                <div class="view-card-body">
                    <a href="<?php echo $delete_url; ?>" 
                       class="btn-v" 
                       style="color:#d63638; border-color:#f5c2c7; width:100%; justify-content:center;"
                       onclick="return confirm('Permanently delete #<?php echo $display_id; ?>?')">
                       <span class="dashicons dashicons-trash"></span> Delete Order Record
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>