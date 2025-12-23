<?php
/*
Template Name: Thanks
*/

get_header();

// Fetch the most recent order for the current user (or the absolute latest if guest)
$args = [
    'post_type'      => 'food_order',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
];

if (is_user_logged_in()) {
    $args['meta_key']   = 'customer_id';
    $args['meta_value'] = get_current_user_id();
}

$orders = get_posts($args);
$currency = '£';
?>

<style>
    :root { --primary-red: #d63638; }
    .success-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; background: #fff; margin-bottom: 50px; }
    
    /* Header Background */
    .success-header { background: var(--primary-red); color: white; padding: 40px 20px; text-align: center; }
    
    .order-id-badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 50px; font-size: 14px; display: inline-block; margin-top: 10px; }
    .detail-label { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; font-weight: 700; }
    .detail-value { font-weight: 600; color: #333; margin-bottom: 15px; font-size: 15px; }
    
    .receipt-table th { border: none; color: #888; font-weight: 600; text-transform: uppercase; font-size: 11px; padding-bottom: 15px; }
    .receipt-table td { vertical-align: middle; padding: 12px 0; border-top: 1px solid #f8f9fa; }
    
    .instruction-box {
        margin-top: 25px;
        padding: 20px;
        background: #fff9f9;
        border-radius: 12px;
        border-left: 4px solid var(--primary-red);
    }

    .btn-home {
        background: var(--primary-red);
        color: white !important;
        border-radius: 12px;
        font-weight: 700;
        padding: 15px 40px;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s;
    }
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
            
            <?php if ($orders) : 
                $order = $orders[0];
                $order_post_id = $order->ID;
                
                // UPDATED: Get the custom Order ID title (e.g., order-12-23-001)
                $display_id = get_the_title($order_post_id);
                
                /* FETCH ITEMS */
                $items = get_post_meta($order_post_id, 'order_items', true); 
                if (empty($items)) {
                    $items = get_post_meta($order_post_id, 'items', true);
                }

                $subtotal = get_post_meta($order_post_id, 'subtotal', true);
                $delivery = get_post_meta($order_post_id, 'delivery_fee', true); 
                $total    = get_post_meta($order_post_id, 'total_price', true);
                
                $address  = get_post_meta($order_post_id, 'customer_address', true);
                $phone    = get_post_meta($order_post_id, 'customer_phone', true);
                $name     = get_post_meta($order_post_id, 'customer_name', true);
                $notes    = get_post_meta($order_post_id, 'order_notes', true); 
                $type     = get_post_meta($order_post_id, 'order_type', true);
            ?>

            <div class="card success-card">
                <div class="success-header">
                    <div class="mb-3" style="font-size: 50px;">🥘</div>
                    <h2 class="text-white">Thank You, <?php echo esc_html(explode(' ', $name)[0]); ?>!</h2>
                    <p class="mb-0 text-white">Your order has been received and is being processed.</p>
                    <span class="order-id-badge">Order ID: #<?php echo esc_html($display_id); ?></span>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">Service Type</div>
                            <div class="detail-value text-capitalize"><?php echo esc_html($type); ?></div>

                            <div class="detail-label">Delivery Address / Notes</div>
                            <div class="detail-value"><?php echo $address ? nl2br(esc_html($address)) : 'In-store Collection'; ?></div>
                            
                            <div class="detail-label">Contact Phone</div>
                            <div class="detail-value"><?php echo esc_html($phone); ?></div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo get_the_date('F j, Y g:i a', $order_post_id); ?></div>
                            
                            <div class="detail-label">Status</div>
                            <div class="detail-value text-danger">Pending Confirmation</div>
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
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No items found in order records.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-md-6 col-lg-5">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-600"><?php echo $currency . number_format((float)$subtotal, 2); ?></span>
                            </div>
                            <?php if($type === 'delivery' && !empty($delivery)): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Delivery Fee</span>
                                <span class="fw-600"><?php echo $currency . number_format((float)$delivery, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="my-3">
                            <div class="d-flex justify-content-between">
                                <span class="h5 fw-bold mb-0">Grand Total</span>
                                <span class="h5 fw-bold text-danger mb-0"><?php echo $currency . number_format((float)$total, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if(!empty($notes)): ?>
                    <div class="instruction-box">
                        <div class="detail-label">Special Instructions:</div>
                        <div class="text-dark mb-0"><?php echo esc_html($notes); ?></div>
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
                    <p class="text-muted">If you just placed an order, please wait a moment and refresh.</p>
                    <a href="<?php echo home_url('/'); ?>" class="btn btn-danger px-4">Return Home</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    // Remove cart data from browser memory so user starts with a fresh cart next time
    document.addEventListener('DOMContentLoaded', function() {
        localStorage.removeItem('fd_cart_save');
        localStorage.removeItem('fd_order_type_save');
    });
</script>

<?php get_footer(); ?>