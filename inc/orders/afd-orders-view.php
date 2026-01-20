<?php
/**
 * Final View Order - Unified Admin UI
 * Synchronized with Checkout Summary Serial Order
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

// 2. DATA MAPPING
$display_id  = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;
$afon_status = strtolower($order->order_status ?: 'pending');
$afon_items  = json_decode($order->items_json, true);

// Get fixed charges for labels (Matching Checkout settings)
$rest_discount = get_option('afd_restaurant_discount', '0.00');
$service_fee   = get_option('afd_service_charge', '0.00');
$bag_fee       = get_option('afd_bag_charge', '0.00');

// Action URLs
$base_order_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id);
$print_url      = $base_order_url . '&action=print&type=customer';
$delete_url     = wp_nonce_url($base_order_url . '&action=delete', 'delete_order_' . $order_id);
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
    
    /* Header Area */
    .view-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
    .id-badge-large { background: #fff; border: 1px solid var(--res-border); padding: 12px 24px; border-radius: 12px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    
    /* Layout Grid */
    .view-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
    .view-card { background: #fff; border: 1px solid var(--res-border); border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .view-card-header { padding: 18px 25px; border-bottom: 1px solid #f0f0f1; background: #fafafa; display: flex; align-items: center; justify-content: space-between; }
    .view-card-header h2 { margin: 0; font-size: 14px; font-weight: 700; color: var(--res-dark); text-transform: uppercase; letter-spacing: 0.5px; }
    .view-card-body { padding: 25px; }

    /* Items Table */
    .view-table { width: 100%; border-collapse: collapse; }
    .view-table th { text-align: left; padding: 12px 15px; background: #f8f9fa; color: #646970; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .view-table td { padding: 15px; border-bottom: 1px solid #f0f0f1; font-size: 14px; }
    .qty-badge { background: var(--res-dark); color: #fff; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-right: 8px; }

    /* SERIAL SUMMARY LIST (Sync with Checkout) */
    .summary-list { padding: 20px 25px; background: #fdfdfd; border-top: 1px solid #eee; }
    .summary-line { display: flex; justify-content: flex-end; margin-bottom: 10px; font-size: 14px; color: #555; }
    .summary-line label { width: 220px; text-align: right; margin-right: 25px; font-weight: 500; color: #646970; }
    .summary-line span { width: 110px; text-align: right; font-weight: 700; color: #1d2327; }
    .summary-line.order-total-bold { color: #1d2327; padding: 5px 0; border-top: 1px dashed #ddd; border-bottom: 1px dashed #ddd; margin: 10px 0; }
    .summary-line.grand-total { margin-top: 15px; padding-top: 15px; border-top: 2px solid #1d2327; }
    .summary-line.grand-total label { font-size: 18px; font-weight: 800; color: var(--res-dark); }
    .summary-line.grand-total span { font-size: 26px; font-weight: 900; color: var(--res-red); }

    /* Status Pills */
    .v-status { padding: 6px 15px; border-radius: 30px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .v-status-pending { background: var(--res-red); color: #fff; }
    .v-status-kitchen { background: var(--res-kitchen); color: #fff; }
    .v-status-rider { background: var(--res-rider); color: #fff; }
    .v-status-completed { background: var(--res-success); color: #fff; }
    .v-status-preorder { background: #7239ea; color: #fff; }

    /* Sidebar Utils */
    .info-block { margin-bottom: 20px; }
    .info-block label { display: block; font-size: 11px; color: #646970; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
    .info-block p { margin: 0; font-size: 14px; font-weight: 600; color: #1d2327; line-height: 1.5; }

    /* Buttons */
    .btn-v { text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: 1px solid #ccd0d4; background: #fff; color: #2c3338; }
    .btn-v:hover { background: #f6f7f7; border-color: #a7aaad; }
</style>

<div class="view-order-wrap">
    
    <div class="view-header">
        <div>
            <div class="id-badge-large">
                <span style="color:#646970; font-size:11px; font-weight:700; text-transform:uppercase;">Order Reference</span><br>
                <span style="font-weight:900; color:var(--res-red); font-size:24px;">#<?php echo esc_html($display_id); ?></span>
            </div>
            <div style="margin-top:12px; color:#646970; font-size:13px;">
                <span class="dashicons dashicons-calendar-alt" style="font-size:17px; margin-top:2px;"></span> 
                Placed on <?php echo date('M j, Y @ g:i a', strtotime($order->order_date)); ?>
                <span style="margin: 0 10px;">|</span>
                <span class="dashicons dashicons-clock" style="font-size:17px; margin-top:2px;"></span> 
                Time: <strong><?php echo strtoupper(esc_html($order->scheduled_time)); ?></strong>
            </div>
        </div>
        
        <div class="action-group" style="display:flex; gap:10px;">
            <?php if ($afon_status === 'pending' || $afon_status === 'preorder') : ?>
                <a href="<?php echo esc_url($base_order_url . '&action=update_status&new_status=kitchen'); ?>" class="btn-v" style="background:var(--res-kitchen); color:#fff; border:none;"><span class="dashicons dashicons-carrot"></span> Send to Kitchen</a>
            <?php elseif ($afon_status === 'kitchen') : ?>
                <a href="<?php echo esc_url($base_order_url . '&action=update_status&new_status=rider'); ?>" class="btn-v" style="background:var(--res-rider); color:#fff; border:none;"><span class="dashicons dashicons-bicycle"></span> Assign Rider</a>
            <?php elseif ($afon_status === 'rider') : ?>
                <a href="<?php echo esc_url($base_order_url . '&action=update_status&new_status=completed'); ?>" class="btn-v" style="background:var(--res-success); color:#fff; border:none;"><span class="dashicons dashicons-yes"></span> Mark Delivered</a>
            <?php endif; ?>

            <a href="<?php echo esc_url($print_url); ?>" target="_blank" class="btn-v"><span class="dashicons dashicons-printer"></span> Print Receipt</a>
        </div>
    </div>

    <div class="view-grid">
        <div class="main-column">
            
            <div class="view-card">
                <div class="view-card-header">
                    <h2>Cart Items (<?php echo esc_html(strtoupper($order->order_type)); ?>)</h2>
                    <span class="v-status v-status-<?php echo $afon_status; ?>"><?php echo esc_html($afon_status); ?></span>
                </div>
                <div class="view-card-body" style="padding:0;">
                    <table class="view-table">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th width="100">Price</th>
                                <th width="100">Quantity</th>
                                <th width="120" style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($afon_items)) : foreach ($afon_items as $item) : ?>
                                <tr>
                                    <td><span class="qty-badge"><?php echo $item['qty']; ?></span> <strong><?php echo esc_html($item['name']); ?></strong></td>
                                    <td>£<?php echo number_format($item['price'], 2); ?></td>
                                    <td>&times; <?php echo $item['qty']; ?></td>
                                    <td style="text-align:right; font-weight:700;">£<?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-list">
                    <div class="summary-line">
                        <label>Subtotal</label>
                        <span>£<?php echo number_format($order->subtotal, 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <label>Restaurant Discount</label>
                        <span>-£<?php echo number_format((float)$rest_discount, 2); ?></span>
                    </div>
                    <div class="summary-line order-total-bold">
                        <label>Order Total</label>
                        <span>£<?php echo number_format(($order->subtotal - (float)$rest_discount), 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <label>Service Charge</label>
                        <span>£<?php echo number_format((float)$service_fee, 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <label>Delivery Charges</label>
                        <span>£<?php echo number_format($order->delivery_fee, 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <label>Bag Charge</label>
                        <span>£<?php echo number_format((float)$bag_fee, 2); ?></span>
                    </div>
                    
                    <div class="summary-line grand-total">
                        <label>Total Due</label>
                        <span>£<?php echo number_format($order->total_price, 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="view-card">
                <div class="view-card-header"><h2>Customer Notes</h2></div>
                <div class="view-card-body">
                    <?php if($order->notes): ?>
                        <div style="background: #fff8e5; padding: 20px; border-radius: 8px; border-left: 5px solid #ffb900; color: #856404; font-size: 15px;">
                            <?php echo nl2br(esc_html($order->notes)); ?>
                        </div>
                    <?php else: ?>
                        <p style="color:#a7aaad; font-style: italic; margin:0;">No special instructions provided.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="sidebar-column">
            <div class="view-card">
                <div class="view-card-header"><h2>Customer Info</h2></div>
                <div class="view-card-body">
                    <div class="info-block"><label>Name</label><p><?php echo esc_html($order->full_name); ?></p></div>
                    <div class="info-block"><label>Phone</label><p style="color:var(--res-red); font-size:18px;"><?php echo esc_html($order->phone); ?></p></div>
                    <div class="info-block"><label>Email</label><p><?php echo esc_html($order->email); ?></p></div>
                    <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                    <div class="info-block"><label>Address</label><p><?php echo $order->address ? nl2br(esc_html($order->address)) : 'Store Pickup'; ?></p></div>
                    <div class="info-block"><label>Payment</label><p style="text-transform:uppercase; color:var(--res-success);"><?php echo esc_html($order->payment_method); ?></p></div>
                </div>
            </div>

            <div class="view-card" style="border: 1px solid #f5c2c7; background: #fff8f8;">
                <div class="view-card-body" style="text-align:center;">
                    <a href="<?php echo $delete_url; ?>" class="btn-v" style="color:#d63638; border-color:#f5c2c7; width:100%; justify-content:center;" onclick="return confirm('Delete this order permanently?')">
                        <span class="dashicons dashicons-trash"></span> Delete Record
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>