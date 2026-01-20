<?php
/*
Template Name: Thanks
*/

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

/**
 * 1. FETCH THE SPECIFIC ORDER
 * Checks URL for ?order_id= (sequential ID). 
 * Fallback to user's latest order if ID is missing.
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
    :root { --primary-red: #d63638; --bg-light: #f9fafb; }
    .success-page-wrapper { background: var(--bg-light); padding: 60px 0; min-height: 90vh; font-family: 'Inter', sans-serif; }
    .success-card { border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.06); overflow: hidden; background: #fff; }
    .success-header { background: var(--primary-red); color: white; padding: 50px 20px; text-align: center; position: relative; }
    .order-id-badge { background: rgba(255,255,255,0.15); padding: 8px 20px; border-radius: 50px; font-size: 14px; display: inline-block; margin-top: 15px; font-weight: 700; letter-spacing: 0.5px; }
    .detail-label { color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 4px; font-weight: 800; }
    .detail-value { font-weight: 600; color: #111827; margin-bottom: 20px; font-size: 15px; }
    .receipt-table { margin-top: 20px; }
    .receipt-table th { border: none; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 11px; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6; }
    .receipt-table td { vertical-align: middle; padding: 15px 0; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .instruction-box { margin-top: 30px; padding: 20px; background: #fff5f5; border-radius: 16px; border-left: 5px solid var(--primary-red); }
    .schedule-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 16px; padding: 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 18px; }
    .btn-home { background: var(--primary-red); color: white !important; border-radius: 14px; font-weight: 800; padding: 18px 45px; text-decoration: none; display: inline-block; transition: all 0.3s ease; border: none; box-shadow: 0 10px 20px rgba(214, 54, 56, 0.2); }
    .btn-home:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(214, 54, 56, 0.3); opacity: 0.95; }
    .status-badge { padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; text-transform: uppercase; display: inline-block; }
</style>

<div class="success-page-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php if ($order) : 
                    $display_id = $order->display_id;
                    $items = json_decode($order->items_json, true);
                    $is_preorder = ($order->order_status === 'preorder');
                ?>

                <div class="card success-card">
                    <div class="success-header">
                        <div class="mb-3" style="font-size: 60px;">✅</div>
                        <h2 class="text-white fw-bold mb-2">Order Confirmed, <?php echo esc_html(explode(' ', $order->full_name)[0]); ?>!</h2>
                        <p class="mb-0 text-white opacity-75">We've sent a confirmation email to <?php echo esc_html($order->email); ?></p>
                        <span class="order-id-badge">Order Reference: #<?php echo esc_html($display_id); ?></span>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        
                        <div class="schedule-box">
                            <div style="font-size: 28px;">🕒</div>
                            <div>
                                <div class="detail-label" style="margin:0">Estimated Fulfillment Time</div>
                                <div class="fw-bold text-dark" style="font-size: 1.2rem;">
                                    <?php echo ($order->scheduled_time === 'asap') ? 'Preparing Now (ASAP)' : 'Scheduled for ' . esc_html($order->scheduled_time); ?>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-6">
                                <div class="detail-label">Service Type</div>
                                <div class="detail-value"><span class="text-capitalize"><?php echo esc_html($order->order_type); ?></span></div>

                                <div class="detail-label">Delivery Address</div>
                                <div class="detail-value"><?php echo $order->address ? nl2br(esc_html($order->address)) : 'Store Pickup / Collection'; ?></div>
                                
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value text-capitalize"><?php echo esc_html($order->payment_method); ?></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="detail-label">Placement Date</div>
                                <div class="detail-value"><?php echo date('D, M j, Y @ g:i a', strtotime($order->order_date)); ?></div>
                                
                                <div class="detail-label">Order Status</div>
                                <div class="detail-value">
                                    <span class="status-badge <?php echo $is_preorder ? 'bg-warning text-dark' : 'bg-success text-white'; ?>">
                                        <?php echo esc_html($order->order_status); ?>
                                    </span>
                                </div>

                                <div class="detail-label">Contact Number</div>
                                <div class="detail-value"><?php echo esc_html($order->phone); ?></div>
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark mb-3">Order Receipt</h6>
                        <div class="table-responsive">
                            <table class="table receipt-table mb-4">
                                <thead>
                                    <tr>
                                        <th>Item Description</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
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
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row justify-content-end">
                            <div class="col-md-6 col-lg-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-bold"><?php echo $currency . number_format((float)$order->subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Delivery Fee</span>
                                    <span class="fw-bold"><?php echo $currency . number_format((float)$order->delivery_fee, 2); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 fw-bold mb-0">Amount Paid</span>
                                    <span class="h4 fw-bold text-danger mb-0"><?php echo $currency . number_format((float)$order->total_price, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if(!empty($order->notes)): ?>
                        <div class="instruction-box">
                            <div class="detail-label">Notes to Kitchen:</div>
                            <p class="text-dark mb-0 italic">"<?php echo esc_html($order->notes); ?>"</p>
                        </div>
                        <?php endif; ?>

                        <div class="text-center mt-5">
                            <a href="<?php echo home_url('/'); ?>" class="btn-home">Back to Menu</a>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                    <div class="text-center py-5">
                        <div style="font-size: 80px; margin-bottom: 20px;">🕵️‍♂️</div>
                        <h2 class="fw-bold">We couldn't find that order.</h2>
                        <p class="text-muted">It may still be processing or the link is incorrect.</p>
                        <a href="<?php echo home_url('/'); ?>" class="btn-home">Return to Home</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
    /**
     * CLEANUP LOGIC
     * Once the user reaches this page, the order is safely in the database.
     * We clear their local storage so they don't see old items in the cart.
     */
    document.addEventListener('DOMContentLoaded', function() {
        localStorage.removeItem('fd_cart_save');
        localStorage.removeItem('fd_order_type_save');
        localStorage.removeItem('fd_scheduled_time');
        console.log('Order completed. Cart cleared.');
    });
</script>

<?php get_footer(); ?>