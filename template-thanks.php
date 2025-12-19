<?php
/*
Template Name: Thanks
*/

get_header();

// Fetch the most recent order
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
    .success-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; background: #fff; }
    
    /* Changed Green to Red */
    .success-header { background: var(--primary-red); color: white; padding: 40px 20px; text-align: center; }
    
    .order-id-badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 50px; font-size: 14px; }
    .detail-label { color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
    .detail-value { font-weight: 600; color: #333; margin-bottom: 15px; }
    .receipt-table th { border: none; color: #888; font-weight: 500; text-transform: uppercase; font-size: 12px; }
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
                $order_id = $order->ID;
                
                $items    = get_post_meta($order_id, 'order_items', true); 
                $subtotal = get_post_meta($order_id, 'subtotal', true);
                $delivery = get_post_meta($order_id, 'delivery_fee', true); 
                $total    = get_post_meta($order_id, 'total_price', true);
                
                $address  = get_post_meta($order_id, 'customer_address', true);
                $phone    = get_post_meta($order_id, 'customer_phone', true);
                $name     = get_post_meta($order_id, 'customer_name', true);
                $notes    = get_post_meta($order_id, 'order_notes', true); 
                $type     = get_post_meta($order_id, 'order_type', true);
            ?>

            <div class="card success-card">
                <div class="success-header">
                    <div class="mb-3" style="font-size: 50px;">🥘</div>
                    <h2 class="text-white">Thank You, <?php echo esc_html(explode(' ', $name)[0]); ?>!</h2>
                    <p class="mb-3">We've received your order and started preparing it.</p>
                    <span class="order-id-badge"><?php echo esc_html($order->post_title); ?></span>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">Service Type</div>
                            <div class="detail-value" style="text-transform: capitalize;"><?php echo esc_html($type); ?></div>

                            <div class="detail-label">Details</div>
                            <div class="detail-value"><?php echo nl2br(esc_html($address)); ?></div>
                            
                            <div class="detail-label">Contact</div>
                            <div class="detail-value"><?php echo esc_html($phone); ?></div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo get_the_date('F j, Y g:i a', $order_id); ?></div>
                            
                            <div class="detail-label">Status</div>
                            <div class="detail-value text-danger">Pending Confirmation</div>
                        </div>
                    </div>

                    <table class="table receipt-table mb-4">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(is_array($items)): foreach($items as $item): ?>
                            <tr>
                                <td><span class="fw-bold"><?php echo esc_html($item['name']); ?></span></td>
                                <td class="text-center">x<?php echo esc_html($item['qty']); ?></td>
                                <td class="text-end"><?php echo $currency . number_format($item['price'] * $item['qty'], 2); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span><?php echo $currency . number_format((float)$subtotal, 2); ?></span>
                            </div>
                            <?php if($type === 'delivery'): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Delivery:</span>
                                <span><?php echo $currency . number_format((float)$delivery, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="h5 fw-bold">Grand Total:</span>
                                <span class="h5 fw-bold text-danger"><?php echo $currency . number_format((float)$total, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if($notes): ?>
                    <div class="mt-4 p-3 bg-light rounded border-start border-danger border-4">
                        <div class="detail-label">Special Instructions:</div>
                        <div class="small text-dark"><?php echo esc_html($notes); ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-5">
                        <a href="<?php echo home_url('/'); ?>" class="btn btn-danger btn-lg px-5" style="border-radius: 12px; font-weight: 700; padding: 15px 40px;">Back to Home</a>
                    </div>
                </div>
            </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <h3 class="fw-bold">No recent order found.</h3>
                    <a href="<?php echo home_url('/'); ?>" class="btn btn-danger">Return Home</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    localStorage.removeItem('fd_cart_save');
    localStorage.removeItem('fd_order_type_save');
</script>

<?php get_footer(); ?>