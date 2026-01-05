<?php
/*
Template Name: Thanks
*/

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

/**
 * 1. FETCH THE SPECIFIC ORDER
 * We check if an order_id is passed in the URL (sent from Checkout redirect)
 * If not, we fall back to the latest order for the user.
 */
$url_order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';

if (!empty($url_order_id)) {
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE display_id = %s",
        $url_order_id
    ));
} else {
    $user_id = get_current_user_id();
    if ($user_id > 0) {
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE customer_id = %d ORDER BY order_date DESC LIMIT 1",
            $user_id
        ));
    } else {
        $order = $wpdb->get_row("SELECT * FROM $table_name ORDER BY order_date DESC LIMIT 1");
    }
}

$currency = '£';
?>

<style>
    :root { --primary-red: #d63638; }
    .success-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; background: #fff; margin-bottom: 50px; }
    .success-header { background: var(--primary-red); color: white; padding: 40px 20px; text-align: center; }
    .order-id-badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 50px; font-size: 14px; display: inline-block; margin-top: 10px; font-family: monospace; font-weight: bold; }
    .detail-label { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; font-weight: 700; }
    .detail-value { font-weight: 600; color: #333; margin-bottom: 15px; font-size: 15px; }
    .receipt-table th { border: none; color: #888; font-weight: 600; text-transform: uppercase; font-size: 11px; padding-bottom: 15px; }
    .receipt-table td { vertical-align: middle; padding: 12px 0; border-top: 1px solid #f8f9fa; }
    .instruction-box { margin-top: 25px; padding: 20px; background: #fff9f9; border-radius: 12px; border-left: 4px solid var(--primary-red); }
    .schedule-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 15px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
    .btn-home { background: var(--primary-red); color: white !important; border-radius: 12px; font-weight: 700; padding: 15px 40px; text-decoration: none; display: inline-block; transition: 0.3s; border: none; }
    .btn-home:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(214, 54, 56, 0.3); opacity: 0.9; }
</style>

<div class="breadcrumb-area text-center text-light" style="background:#1a1a1a; padding: 60px 0;">
    <div class="container">
        <h1 class="text-white m-0">Order Confirmed</h1>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php if ($order) : 
                $display_id = $order->display_id;
                $items = json_decode($order->items_json, true);
                $is_preorder = ($order->order_status === 'preorder');
            ?>

            <div class="card success-card">
                <div class="success-header">
                    <div class="mb-3" style="font-size: 50px;">🥘</div>
                    <h2 class="text-white">Thank You, <?php echo esc_html(explode(' ', $order->full_name)[0]); ?>!</h2>
                    <p class="mb-0 text-white">Your order has been received and is being processed.</p>
                    <span class="order-id-badge">Order Reference: #<?php echo esc_html($display_id); ?></span>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <div class="schedule-box">
                        <div style="font-size: 24px;">⏰</div>
                        <div>
                            <div class="detail-label" style="margin:0">Requested Time</div>
                            <div class="fw-bold text-dark" style="font-size: 1.1rem;">
                                <?php echo ($order->scheduled_time === 'asap') ? 'As Soon As Possible' : esc_html($order->scheduled_time); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">Service Type</div>
                            <div class="detail-value text-capitalize"><?php echo esc_html($order->order_type); ?></div>

                            <div class="detail-label">Fulfillment Address</div>
                            <div class="detail-value"><?php echo $order->address ? nl2br(esc_html($order->address)) : 'In-store Collection'; ?></div>
                            
                            <div class="detail-label">Contact Phone</div>
                            <div class="detail-value"><?php echo esc_html($order->phone); ?></div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="detail-label">Order Placed</div>
                            <div class="detail-value"><?php echo date('F j, Y g:i a', strtotime($order->order_date)); ?></div>
                            
                            <div class="detail-label">Current Status</div>
                            <div class="detail-value">
                                <span class="badge <?php echo $is_preorder ? 'bg-warning text-dark' : 'bg-danger text-white'; ?>" style="padding: 8px 12px; border-radius: 8px; text-transform: uppercase;">
                                    <?php echo esc_html($order->order_status); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table receipt-table mb-4">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($items) && is_array($items)): foreach($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo esc_html($item['name']); ?></div>
                                    </td>
                                    <td class="text-center">x<?php echo esc_html($item['qty']); ?></td>
                                    <td class="text-end"><?php echo $currency . number_format($item['price'] * $item['qty'], 2); ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No items found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-md-6 col-lg-5">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-600"><?php echo $currency . number_format((float)$order->subtotal, 2); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Delivery Fee</span>
                                <span class="fw-600"><?php echo $currency . number_format((float)$order->delivery_fee, 2); ?></span>
                            </div>
                            
                            <hr class="my-3">
                            <div class="d-flex justify-content-between">
                                <span class="h5 fw-bold mb-0">Grand Total</span>
                                <span class="h5 fw-bold text-danger mb-0"><?php echo $currency . number_format((float)$order->total_price, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if(!empty($order->notes)): ?>
                    <div class="instruction-box">
                        <div class="detail-label">Special Instructions:</div>
                        <div class="text-dark mb-0"><?php echo esc_html($order->notes); ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-5">
                        <a href="<?php echo home_url('/'); ?>" class="btn-home">Return to Home</a>
                    </div>
                </div>
            </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size: 60px; margin-bottom: 20px;">🔍</div>
                    <h3 class="fw-bold">No recent order found.</h3>
                    <p class="text-muted">If you just placed an order, please wait a moment or check your account.</p>
                    <a href="<?php echo home_url('/'); ?>" class="btn-home">Return Home</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Full Cleanup
        localStorage.removeItem('fd_cart_save');
        localStorage.removeItem('fd_order_type_save');
        localStorage.removeItem('fd_scheduled_time');
    });
</script>

<?php get_footer(); ?>