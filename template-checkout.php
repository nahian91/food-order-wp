<?php
/*
Template Name: Checkout
*/

// 1. HANDLE AJAX ORDER SUBMISSION
if (isset($_POST['fd_place_order'])) {
    header('Content-Type: application/json');

    // Security Check
    if (!isset($_POST['fd_nonce']) || !wp_verify_nonce($_POST['fd_nonce'], 'fd_place_order_action')) {
        echo json_encode(['status' => 'error', 'message' => 'Security check failed. Please refresh.']);
        exit;
    }

    $data = json_decode(stripslashes($_POST['fd_place_order']), true);

    if (!$data || empty($data['cart'])) {
        echo json_encode(['status' => 'error', 'message' => 'Your cart is empty.']);
        exit;
    }

    $user_id = get_current_user_id() ?: 0;
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';

    // --- STEP A: GENERATE PERMANENT SEQUENTIAL ID ---
    $today_date = current_time('Y-m-d');
    $date_prefix = current_time('Ymd');
    
    $count_today = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(id) FROM $table_name WHERE DATE(order_date) = %s",
        $today_date
    ));
    
    $new_sequence = intval($count_today) + 1;
    $permanent_id = $date_prefix . '-' . str_pad($new_sequence, 4, '0', STR_PAD_LEFT);

    // --- STEP B: PRE-ORDER LOGIC ---
    $scheduled_time = isset($data['scheduledTime']) ? sanitize_text_field($data['scheduledTime']) : 'asap';
    $initial_status = ($scheduled_time === 'asap') ? 'pending' : 'preorder';

    // --- STEP C: DATABASE INSERTION ---
    $inserted = $wpdb->insert(
        $table_name,
        [
            'display_id'     => $permanent_id, 
            'customer_id'    => $user_id,
            'order_type'     => sanitize_text_field($data['orderType']),
            'payment_method' => sanitize_text_field($data['paymentMethod']),
            'full_name'      => sanitize_text_field($data['fullName']),
            'email'          => sanitize_email($data['email']),
            'phone'          => sanitize_text_field($data['phone']),
            'address'        => sanitize_textarea_field($data['address']),
            'notes'          => sanitize_textarea_field($data['notes']),
            'scheduled_time' => $scheduled_time,
            'items_json'     => json_encode($data['cart']), 
            'subtotal'       => floatval($data['subtotal']),
            'delivery_fee'   => floatval($data['delivery']),
            'total_price'    => floatval($data['total']),
            'order_status'   => $initial_status,
            'order_date'     => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s']
    );

    if ($inserted) {
        if ($user_id > 0) {
            update_user_meta($user_id, 'phone', sanitize_text_field($data['phone']));
            if ($data['orderType'] === 'delivery') {
                update_user_meta($user_id, 'address', sanitize_textarea_field($data['address']));
            }
        }
        echo json_encode(['status' => 'success', 'order_id' => $permanent_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $wpdb->last_error]);
    }
    exit;
}

get_header();

$u = wp_get_current_user();
$user_id = $u->ID;
$user_phone = get_user_meta($user_id, 'phone', true);
$user_address = get_user_meta($user_id, 'address', true);

$currency = '£';
$base_delivery_fee = get_option('afd_delivery_charge', '0.00');
$service_fee       = get_option('afd_service_charge', '0.00');
$bag_fee           = get_option('afd_bag_charge', '0.00');
$rest_discount     = get_option('afd_restaurant_discount', '0.00');
?>

<style>
    :root { --primary-red: #d63638; --light-bg: #f8f9fa; --border-color: #e5e7eb; }
    .fd-checkout-wrapper { background: var(--light-bg); padding: 50px 0; min-height: 80vh; font-family: 'Inter', sans-serif; color: #333; }
    .checkout-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; padding: 25px; margin-bottom: 25px; }
    .form-label { font-weight: 700; font-size: 0.85rem; color: #555; margin-bottom: 6px; }
    .form-control { border-radius: 10px; padding: 10px 14px; border: 1px solid #ddd; transition: 0.3s; font-size: 15px; }
    .form-control:focus { border-color: var(--primary-red); box-shadow: 0 0 0 3px rgba(214, 54, 56, 0.08); }
    
    .fulfillment-toggle { display: flex; background: #eee; padding: 4px; border-radius: 12px; margin-bottom: 25px; }
    .fulfillment-toggle input { display: none; }
    .fulfillment-toggle label { flex: 1; text-align: center; padding: 10px; border-radius: 9px; cursor: pointer; font-weight: 700; transition: 0.3s; color: #666; margin: 0; font-size: 14px; }
    .fulfillment-toggle input:checked + label { background: var(--primary-red); color: #fff; }

    /* WooCommerce Style Payment Methods */
    .wc-payment-methods { list-style: none; padding: 0; margin: 20px 0; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: #fafafa; }
    .wc-payment-item { border-bottom: 1px solid var(--border-color); }
    .wc-payment-item:last-child { border-bottom: none; }
    .wc-payment-item input[type="radio"] { display: none; }
    .wc-payment-item label { display: block; padding: 15px; cursor: pointer; font-weight: 700; color: #333; transition: 0.2s; position: relative; margin: 0; font-size: 14px; }
    .wc-payment-item label::before { content: ""; display: inline-block; width: 16px; height: 16px; border: 2px solid #ccc; border-radius: 50%; margin-right: 10px; vertical-align: middle; background: #fff; }
    .wc-payment-item input:checked + label { background: #fff; }
    .wc-payment-item input:checked + label::before { border-color: var(--primary-red); background: radial-gradient(var(--primary-red) 40%, #fff 50%); }
    .payment-desc { max-height: 0; overflow: hidden; transition: 0.3s ease-out; padding: 0 15px; font-size: 0.85rem; color: #777; line-height: 1.4; }
    .wc-payment-item input:checked ~ .payment-desc { max-height: 100px; padding: 0 15px 15px 42px; }

    .schedule-badge { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 10px; border-radius: 10px; font-size: 13px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #555; }
    .place-order-btn { background: var(--primary-red); color: #fff; border: none; width: 100%; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; transition: 0.3s; box-shadow: 0 6px 12px rgba(214, 54, 56, 0.2); }
    .place-order-btn:hover { background: #b52a2c; transform: translateY(-1px); }
</style>

<div class="fd-checkout-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h5 class="fw-bold mb-4">Fulfillment Method</h5>
                    <div class="fulfillment-toggle">
                        <input type="radio" name="orderType" id="typeDelivery" value="delivery" checked>
                        <label for="typeDelivery">🚚 Delivery</label>
                        <input type="radio" name="orderType" id="typePickup" value="pickup">
                        <label for="typePickup">🛍️ Pickup</label>
                    </div>

                    <h5 class="fw-bold mb-4">Contact Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="fullName" class="form-control" value="<?php echo esc_attr($u->display_name); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="email" class="form-control" value="<?php echo esc_attr($u->user_email); ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="phone" class="form-control" value="<?php echo esc_attr($user_phone); ?>">
                        </div>
                        <div class="col-md-12 mb-3" id="addressArea">
                            <label class="form-label">Delivery Address</label>
                            <textarea id="address" class="form-control" rows="2"><?php echo esc_textarea($user_address); ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Special Notes (Optional)</label>
                            <textarea id="notes" class="form-control" rows="2" placeholder="Instructions for our staff..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="checkout-card sticky-top" style="top: 20px;">
                    <h5 class="fw-bold mb-4">Order Summary</h5>
                    
                    <div id="scheduleInfo" class="schedule-badge" style="display:none;">
                        <span class="dashicons dashicons-clock"></span>
                        <span>Time: <span id="timeValDisplay">ASAP</span></span>
                    </div>

                    <div id="itemsContainer" class="mb-3"></div>

                    <div class="border-top pt-3">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span><?php echo $currency; ?><span id="subtotalVal">0.00</span></span>
                        </div>
                        <div class="summary-item">
                            <span>Restaurant discount</span>
                            <span>-<?php echo $currency . number_format(floatval($rest_discount), 2); ?></span>
                        </div>
                        <div class="summary-item" style="font-weight:700; color:#1a1a1a;">
                            <span>Order total</span>
                            <span><?php echo $currency; ?><span id="orderTotalVal">0.00</span></span>
                        </div>
                        <div class="summary-item">
                            <span>Service Charge</span>
                            <span><?php echo $currency . number_format(floatval($service_fee), 2); ?></span>
                        </div>
                        <div class="summary-item" id="deliveryRow">
                            <span>Delivery Charges</span>
                            <span><?php echo $currency; ?><span id="deliveryVal"><?php echo number_format(floatval($base_delivery_fee), 2); ?></span></span>
                        </div>
                        <div class="summary-item">
                            <span>Bag Charge</span>
                            <span><?php echo $currency . number_format(floatval($bag_fee), 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Tips</span>
                            <span><?php echo $currency; ?><input type="number" id="tipAmount" class="form-control d-inline-block p-1" style="width:65px; height:28px; font-size:13px; text-align:right;" value="0.00" step="0.50"></span>
                        </div>

                        <div class="summary-item mt-2 pt-3 border-top" style="font-size: 1.3rem;">
                            <strong>Total Due</strong>
                            <strong style="color: var(--primary-red);"><?php echo $currency; ?><span id="totalDueVal">0.00</span></strong>
                        </div>
                    </div>

                    <div class="wc-payment-methods">
                        <div class="wc-payment-item">
                            <input type="radio" name="paymentMethod" id="payCash" value="cash" checked>
                            <label for="payCash">Cash on Delivery</label>
                            <div class="payment-desc">Pay with cash upon arrival at your location.</div>
                        </div>
                        <div class="wc-payment-item">
                            <input type="radio" name="paymentMethod" id="payCard" value="card">
                            <label for="payCard">Pay by Card on Arrival</label>
                            <div class="payment-desc">We will bring a card terminal for payment on delivery.</div>
                        </div>
                    </div>

                    <input type="hidden" id="fd_nonce" value="<?php echo wp_create_nonce('fd_place_order_action'); ?>">
                    <button id="placeOrderBtn" class="place-order-btn">CONFIRM ORDER</button>
                    <p class="text-center mt-3 text-muted" style="font-size: 11px;">Your personal data will be used to process your order.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    let cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    let scheduledTime = localStorage.getItem('fd_scheduled_time') || 'asap';
    
    const deliveryFee = parseFloat("<?php echo $base_delivery_fee; ?>") || 0;
    const serviceFee = parseFloat("<?php echo $service_fee; ?>") || 0;
    const bagFee = parseFloat("<?php echo $bag_fee; ?>") || 0;
    const restDiscount = parseFloat("<?php echo $rest_discount; ?>") || 0;
    const currency = "<?php echo $currency; ?>";

    function updateUI() {
        const isPickup = document.getElementById('typePickup').checked;
        const container = document.getElementById('itemsContainer');
        const tipVal = parseFloat(document.getElementById('tipAmount').value) || 0;
        
        if(scheduledTime !== 'asap') {
            document.getElementById('scheduleInfo').style.display = 'flex';
            document.getElementById('timeValDisplay').innerText = scheduledTime;
        }

        document.getElementById('addressArea').style.display = isPickup ? 'none' : 'block';
        document.getElementById('deliveryRow').style.display = isPickup ? 'none' : 'flex';

        if(cart.length === 0) {
            container.innerHTML = '<p class="text-muted">Empty Cart</p>';
            return;
        }

        let subtotal = 0;
        container.innerHTML = cart.map(item => {
            let itemTotal = item.price * item.qty;
            subtotal += itemTotal;
            return `<div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span><strong>${item.qty}x</strong> ${item.name}</span>
                        <span>${currency}${itemTotal.toFixed(2)}</span>
                    </div>`;
        }).join('');

        const orderTotal = Math.max(0, subtotal - restDiscount);
        const finalDelivery = isPickup ? 0 : deliveryFee;
        const totalDue = subtotal > 0 ? (orderTotal + serviceFee + finalDelivery + bagFee + tipVal) : 0;

        document.getElementById('subtotalVal').innerText = subtotal.toFixed(2);
        document.getElementById('orderTotalVal').innerText = orderTotal.toFixed(2);
        document.getElementById('deliveryVal').innerText = finalDelivery.toFixed(2);
        document.getElementById('totalDueVal').innerText = totalDue.toFixed(2);
    }

    document.querySelectorAll('input[name="orderType"], #tipAmount').forEach(el => {
        el.addEventListener('change', updateUI);
    });

    document.getElementById('placeOrderBtn').addEventListener('click', function() {
        const orderType = document.querySelector('input[name="orderType"]:checked').value;
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        const btn = this;

        const data = {
            orderType: orderType,
            paymentMethod: paymentMethod,
            fullName: document.getElementById('fullName').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            address: orderType === 'delivery' ? document.getElementById('address').value.trim() : 'STORE PICKUP',
            notes: document.getElementById('notes').value.trim(),
            scheduledTime: scheduledTime,
            cart: cart,
            subtotal: document.getElementById('subtotalVal').innerText,
            delivery: document.getElementById('deliveryVal').innerText,
            total: document.getElementById('totalDueVal').innerText
        };

        if(!data.fullName || !data.phone || (orderType === 'delivery' && !data.address)) {
            alert('Required contact fields are empty.'); return;
        }

        btn.disabled = true; btn.innerText = "Processing...";

        const fd = new FormData();
        fd.append('fd_place_order', JSON.stringify(data));
        fd.append('fd_nonce', document.getElementById('fd_nonce').value);

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                localStorage.removeItem('fd_cart_save');
                localStorage.removeItem('fd_scheduled_time');
                window.location.href = '<?php echo home_url('/thanks/?order_id='); ?>' + res.order_id;
            } else {
                alert(res.message); btn.disabled = false; btn.innerText = "CONFIRM ORDER";
            }
        });
    });

    updateUI();
});
</script>

<?php get_footer(); ?>