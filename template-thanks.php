<?php
/*
Template Name: Thanks
*/

// Safety: Redirect guests away from this page
if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

get_header();

$current_user_id = get_current_user_id();

// Fetch the most recent order for this user
$orders = get_posts([
    'post_type'      => 'food_order',
    'meta_key'       => 'customer_id',
    'meta_value'     => $current_user_id,
    'posts_per_page' => 1,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
]);

?>

<style>
    .success-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; }
    .success-header { background: #28a745; color: white; padding: 40px 20px; text-align: center; }
    .order-id-badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 50px; font-size: 14px; }
    .detail-label { color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
    .detail-value { font-weight: 600; color: #333; margin-bottom: 15px; }
    .receipt-table th { border-top: none; color: #888; font-weight: 500; }
    @media print {
        .navbar, .footer, .btn-print, .breadcrumb-area { display: none !important; }
        .success-card { box-shadow: none; border: 1px solid #eee; }
    }
</style>

<div class="breadcrumb-area bg-cover text-center text-light" style="background:#333; padding: 60px 0; background-image:url(<?php echo get_template_directory_uri(); ?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <h1 class="text-white">Order Received</h1>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php if ($orders) : 
                $order = $orders[0];
                $order_id = $order->ID;
                $items = get_post_meta($order_id, 'items', true);
                $subtotal = get_post_meta($order_id, 'subtotal', true);
                $delivery = get_post_meta($order_id, 'delivery', true);
                $total = get_post_meta($order_id, 'total_price', true);
                $address = get_post_meta($order_id, 'customer_address', true);
                $phone = get_post_meta($order_id, 'customer_phone', true);
                $name = get_post_meta($order_id, 'customer_name', true);
                $notes = get_post_meta($order_id, 'notes', true);
            ?>

            <div class="card success-card">
                <div class="success-header">
                    <i class="fas fa-check-circle mb-3" style="font-size: 60px;"></i>
                    <h2>Thank You, <?php echo esc_html(explode(' ', $name)[0]); ?>!</h2>
                    <p class="mb-3">Your order has been placed successfully.</p>
                    <span class="order-id-badge">Order ID: <?php echo esc_html($order->post_title); ?></span>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value"><?php echo esc_html($address); ?></div>
                            
                            <div class="detail-label">Phone Number</div>
                            <div class="detail-value"><?php echo esc_html($phone); ?></div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo get_the_date('F j, Y g:i a', $order_id); ?></div>
                            
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value">Cash on Delivery</div>
                        </div>
                    </div>

                    <table class="table receipt-table mb-4">
                        <thead>
                            <tr>
                                <th>Menu Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(is_array($items)): foreach($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item['name']); ?></td>
                                <td class="text-center">x<?php echo esc_html($item['qty']); ?></td>
                                <td class="text-end">€<?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span>€<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Delivery Fee:</span>
                                <span>€<?php echo number_format($delivery, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="h5">Total:</span>
                                <span class="h5 text-danger">€<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if($notes): ?>
                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="detail-label">Note for Chef:</div>
                        <div class="small text-muted"><?php echo esc_html($notes); ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-5 d-flex gap-2 justify-content-center">
                        <a href="<?php echo home_url('/menu'); ?>" class="btn btn-danger btn-lg px-4" style="border-radius: 50px;">Order Again</a>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-lg px-4 btn-print" style="border-radius: 50px;">
                            <i class="fas fa-print me-2"></i> Print Receipt
                        </button>
                    </div>
                </div>
            </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <h3>No order found.</h3>
                    <p>It seems like you haven't placed any orders recently.</p>
                    <a href="<?php echo home_url('/menu'); ?>" class="btn btn-danger">View Menu</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    // FINAL SAFETY: Clear the cart one last time on success
    localStorage.removeItem('fd_cart_save');
    if (typeof syncHeaderCart === "function") syncHeaderCart();
</script>

<?php get_footer(); ?>