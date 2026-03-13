<?php
/*
Template Name: Thanks
*/

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'afd_food_orders';

/**
 * 1. FETCH THE SPECIFIC ORDER
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
    :root { --primary-red: #d63638; --bg-light: #f9fafb; --text-dark: #111827; --text-muted: #6b7280; }
    .success-page-wrapper { background: var(--bg-light); padding: 60px 0; min-height: 90vh; font-family: 'Inter', sans-serif; }
    .success-card { border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.06); overflow: hidden; background: #fff; }
    .success-header { background: var(--primary-red); color: white; padding: 50px 20px; text-align: center; position: relative; }
    .order-id-badge { background: rgba(255,255,255,0.15); padding: 8px 20px; border-radius: 50px; font-size: 14px; display: inline-block; margin-top: 15px; font-weight: 700; letter-spacing: 0.5px; }
    
    .detail-label { color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 4px; font-weight: 800; }
    .detail-value { font-weight: 600; color: var(--text-dark); margin-bottom: 20px; font-size: 15px; line-height: 1.5; }
    
    .receipt-table { margin-top: 20px; }
    .receipt-table th { border: none; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 11px; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6; }
    .receipt-table td { vertical-align: middle; padding: 15px 0; border-bottom: 1px solid #f3f4f6; color: #374151; }
    
    .instruction-box { margin-top: 20px; padding: 20px; background: #fff5f5; border-radius: 16px; border-left: 5px solid var(--primary-red); }
    .delivery-box { margin-top: 20px; padding: 20px; background: #f0fdf4; border-radius: 16px; border-left: 5px solid #22c55e; }
    
    .schedule-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 16px; padding: 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 18px; }
    .btn-home { background: var(--primary-red); color: white !important; border-radius: 14px; font-weight: 800; padding: 18px 45px; text-decoration: none; display: inline-block; transition: all 0.3s ease; border: none; box-shadow: 0 10px 20px rgba(214, 54, 56, 0.2); }
    .btn-home:hover { transform: translateY(-2px); opacity: 0.9; }
    
    .status-badge { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .fee-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .discount-text { color: #16a34a; font-weight: 700; }
</style>

<div class="success-page-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php if ($order) : 
                    $display_id = $order->display_id;
                    $items = json_decode($order->items_json, true);
                    $order_type = strtolower($order->order_type);
                    
                    // Construct formatted address
                    $address_parts = array_filter([
                        $order->flat_no ? "Flat " . $order->flat_no : '',
                        $order->door_no ? "Door " . $order->door_no : '',
                        $order->building_name,
                        $order->road_name,
                        $order->postcode
                    ]);
                    $formatted_address = ($order_type === 'pickup' || $order_type === 'collection') ? 'Collection at Store' : (!empty($address_parts) ? implode(', ', $address_parts) : 'Address not specified');
                ?>

                <div class="card success-card">
                    <div class="success-header">
                        <div class="mb-3" style="font-size: 60px;">✅</div>
                        <h2 class="text-white fw-bold mb-2">Order Confirmed, <?php echo esc_html(explode(' ', $order->full_name)[0]); ?>!</h2>
                        <p class="mb-0 text-white opacity-75">Confirmation sent to <?php echo esc_html($order->email); ?></p>
                        <span class="order-id-badge">Order Reference: #<?php echo esc_html($display_id); ?></span>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        
                        <div class="schedule-box">
                            <div style="font-size: 28px;">🕒</div>
                            <div>
                                <div class="detail-label" style="margin:0">Estimated Fulfillment Time</div>
                                <div class="fw-bold text-dark" style="font-size: 1.2rem;">
                                    <?php echo (empty($order->scheduled_time) || $order->scheduled_time == 'null' || $order->scheduled_time == 'asap') ? 'Preparing Now (ASAP)' : 'Scheduled: ' . esc_html($order->scheduled_time); ?>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-6 border-end">
                                <div class="detail-label">Service Type</div>
                                <div class="detail-value text-capitalize">
                                    <i class="fas <?php echo ($order_type === 'pickup' || $order_type === 'collection') ? 'fa-store' : 'fa-truck'; ?> me-1"></i> <?php echo esc_html($order->order_type); ?>
                                </div>

                                <div class="detail-label">Location / Address</div>
                                <div class="detail-value"><?php echo nl2br(esc_html($formatted_address)); ?></div>
                                
                                <div class="detail-label">Payment Information</div>
                                <div class="detail-value text-capitalize">
                                    <?php echo esc_html(str_replace('_', ' ', $order->payment_method)); ?> 
                                    <span class="status-badge <?php echo ($order->payment_status === 'Paid') ? 'bg-success text-white' : 'bg-danger text-white'; ?> ms-2">
                                        <?php echo esc_html($order->payment_status); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 ps-md-4">
                                <div class="detail-label">Placement Date</div>
                                <div class="detail-value"><?php echo date('D, M j, Y @ g:i a', strtotime($order->order_date)); ?></div>
                                
                                <div class="detail-label">Order Status</div>
                                <div class="detail-value">
                                    <span class="status-badge bg-info text-white"><?php echo esc_html($order->order_status); ?></span>
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
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $fresh_subtotal = 0;
                                    if(!empty($items) && is_array($items)): 
                                        foreach($items as $item): 
                                            // MATCH CHECKOUT LOGIC: (Base + Variation) * Qty
                                            $baseP = isset($item['price']) ? (float)$item['price'] : 0;
                                            $varP  = isset($item['vPrice']) ? (float)$item['vPrice'] : 0;
                                            $line_total = ($baseP + $varP) * (int)$item['qty'];
                                            $fresh_subtotal += $line_total;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo esc_html($item['name']); ?></div>
                                            <?php if(!empty($item['vName'])): ?>
                                                <small class="text-muted">Variation: <?php echo esc_html($item['vName']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">x<?php echo esc_html($item['qty']); ?></td>
                                        <td class="text-end"><?php echo $currency . number_format($line_total, 2); ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row justify-content-end">
                            <div class="col-md-6 col-lg-5">
                                <?php 
                                    // MATCH CHECKOUT LOGIC: Choose fee and discount based on order type
                                    $is_collection = ($order_type === 'pickup' || $order_type === 'collection');
                                    
                                    $shipping_charge = $is_collection ? (float)$order->collection_charge : (float)$order->delivery_charge;
                                    $service_charge  = (float)$order->service_fee;
                                    $bag_fee         = (float)$order->bag_fee;
                                    $tip_amount      = (float)$order->tip_amount; // Match the key from your payload 'tip'

                                    // Discount Calculation
                                    $discount_val = $is_collection ? (float)$order->collection_discount : (float)$order->delivery_discount;

                                    // FINAL CALCULATION
                                    $grand_total = ($fresh_subtotal - $discount_val) + $shipping_charge + $service_charge + $bag_fee + $tip_amount;
                                ?>

                                <div class="fee-row">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-bold"><?php echo $currency . number_format($fresh_subtotal, 2); ?></span>
                                </div>

                                <?php if($discount_val > 0): ?>
                                <div class="fee-row">
                                    <span class="discount-text">Discount Applied</span>
                                    <span class="discount-text">-<?php echo $currency . number_format($discount_val, 2); ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="fee-row">
                                    <span class="text-muted">Service Charge</span>
                                    <span><?php echo $currency . number_format($service_charge, 2); ?></span>
                                </div>

                                <div class="fee-row">
                                    <span class="text-muted"><?php echo $is_collection ? 'Collection Fee' : 'Delivery Fee'; ?></span>
                                    <span><?php echo $currency . number_format($shipping_charge, 2); ?></span>
                                </div>

                                <?php if($bag_fee > 0): ?>
                                <div class="fee-row">
                                    <span class="text-muted">Bag Fee</span>
                                    <span><?php echo $currency . number_format($bag_fee, 2); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if($tip_amount > 0): ?>
                                <div class="fee-row">
                                    <span class="text-muted">Driver Tip</span>
                                    <span><?php echo $currency . number_format($tip_amount, 2); ?></span>
                                </div>
                                <?php endif; ?>

                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 fw-bold mb-0">TOTAL</span>
                                    <span class="h4 fw-bold text-danger mb-0"><?php echo $currency . number_format($grand_total, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <?php if(!empty($order->kitchen_notes)): ?>
                            <div class="col-12 mb-3">
                                <div class="instruction-box">
                                    <div class="detail-label">Kitchen Instructions:</div>
                                    <p class="text-dark mb-0">"<?php echo esc_html($order->kitchen_notes); ?>"</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($order->delivery_notes)): ?>
                            <div class="col-12">
                                <div class="delivery-box">
                                    <div class="detail-label" style="color:#166534">Delivery Instructions:</div>
                                    <p class="text-dark mb-0">"<?php echo esc_html($order->delivery_notes); ?>"</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="text-center mt-5">
                            <a href="<?php echo home_url('/'); ?>" class="btn-home">Back to Menu</a>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                    <div class="text-center py-5">
                        <div style="font-size: 80px; margin-bottom: 20px;">🕵️‍♂️</div>
                        <h2 class="fw-bold">Order not found.</h2>
                        <a href="<?php echo home_url('/'); ?>" class="btn-home">Return to Home</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clear all cart related storage once the order is viewed
        localStorage.removeItem('fd_cart_save');
        localStorage.removeItem('fd_kitchen_notes');
        localStorage.removeItem('fd_scheduled_time');
    });
</script>

<?php get_footer(); ?>