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

    // --- STEP A: GENERATE PERMANENT SEQUENTIAL ID (Existing Feature) ---
    $today_date = current_time('Y-m-d');
    $date_prefix = current_time('Ymd');
    
    $count_today = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(id) FROM $table_name WHERE DATE(order_date) = %s",
        $today_date
    ));
    
    $new_sequence = intval($count_today) + 1;
    $permanent_id = $date_prefix . '-' . str_pad($new_sequence, 4, '0', STR_PAD_LEFT);

    // --- STEP B: PRE-ORDER LOGIC (New Feature) ---
    $scheduled_time = isset($data['scheduledTime']) ? sanitize_text_field($data['scheduledTime']) : 'asap';
    // If time is not "asap", set status to "preorder"
    $initial_status = ($scheduled_time === 'asap') ? 'pending' : 'preorder';

    // --- STEP C: DATABASE INSERTION (All Features) ---
    $inserted = $wpdb->insert(
        $table_name,
        [
            'display_id'    => $permanent_id, 
            'customer_id'   => $user_id,
            'order_type'    => sanitize_text_field($data['orderType']),
            'full_name'     => sanitize_text_field($data['fullName']),
            'email'         => sanitize_email($data['email']),
            'phone'         => sanitize_text_field($data['phone']),
            'address'       => sanitize_textarea_field($data['address']),
            'notes'         => sanitize_textarea_field($data['notes']),
            'scheduled_time'=> $scheduled_time, // New Feature
            'items_json'    => json_encode($data['cart']), 
            'subtotal'      => floatval($data['subtotal']),
            'delivery_fee'  => floatval($data['delivery']),
            'total_price'   => floatval($data['total']),
            'order_status'  => $initial_status, // Dynamic Status
            'order_date'    => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s']
    );

    if ($inserted) {
        // Update user meta for convenience (Existing Feature)
        if ($user_id > 0) {
            update_user_meta($user_id, 'phone', sanitize_text_field($data['phone']));
            if ($data['orderType'] === 'delivery') {
                update_user_meta($user_id, 'address', sanitize_textarea_field($data['address']));
            }
        }
        
        echo json_encode([
            'status' => 'success', 
            'order_id' => $permanent_id 
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $wpdb->last_error]);
    }
    exit;
}

get_header();

// PRE-FILL LOGIC (Existing Feature)
$u = wp_get_current_user();
$user_id = $u->ID;
$user_phone = get_user_meta($user_id, 'phone', true);
$user_address = get_user_meta($user_id, 'address', true);

$currency = '£';
$base_delivery_fee = get_option('afd_delivery_charge', '0.00');
?>

<style>
    :root { --primary-red: #d63638; --light-bg: #f8f9fa; }
    .fd-checkout-wrapper { background: var(--light-bg); padding: 60px 0; min-height: 80vh; font-family: 'Inter', sans-serif; }
    .checkout-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; padding: 30px; margin-bottom: 25px; }
    .form-label { font-weight: 700; font-size: 0.9rem; color: #444; margin-bottom: 8px; }
    .form-control { border-radius: 12px; padding: 12px 15px; border: 1px solid #eee; transition: 0.3s; font-weight: 500; }
    .form-control:focus { border-color: var(--primary-red); box-shadow: 0 0 0 4px rgba(214, 54, 56, 0.1); }
    
    .fulfillment-toggle { display: flex; background: #eee; padding: 5px; border-radius: 15px; margin-bottom: 30px; }
    .fulfillment-toggle input { display: none; }
    .fulfillment-toggle label { flex: 1; text-align: center; padding: 12px; border-radius: 12px; cursor: pointer; font-weight: 800; transition: 0.3s; color: #666; margin: 0; }
    .fulfillment-toggle input:checked + label { background: var(--primary-red); color: #fff; box-shadow: 0 4px 10px rgba(214, 54, 56, 0.2); }

    /* New Feature Style */
    .schedule-badge { background: #fff8e1; border: 1px solid #ffe082; color: #795548; padding: 10px 15px; border-radius: 12px; font-size: 13px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

    .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
    .place-order-btn { background: var(--primary-red); color: #fff; border: none; width: 100%; padding: 18px; border-radius: 15px; font-weight: 800; font-size: 1.1rem; transition: 0.3s; margin-top: 20px; }
    .place-order-btn:hover { background: #b52a2c; transform: translateY(-2px); }
    .place-order-btn:disabled { background: #ccc; cursor: not-allowed; }

    @media (max-width: 991px) { .sticky-column { position: static !important; } }
</style>

<div class="fd-checkout-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h4 class="fw-bold mb-4">Fulfillment Method</h4>
                    <div class="fulfillment-toggle">
                        <input type="radio" name="orderType" id="typeDelivery" value="delivery" checked>
                        <label for="typeDelivery">🚚 Delivery</label>
                        <input type="radio" name="orderType" id="typePickup" value="pickup">
                        <label for="typePickup">🛍️ Pickup</label>
                    </div>

                    <h4 class="fw-bold mb-4">Contact Details</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="fullName" class="form-control" value="<?php echo esc_attr($u->display_name); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="email" class="form-control" value="<?php echo esc_attr($u->user_email); ?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="phone" class="form-control" value="<?php echo esc_attr($user_phone); ?>" required>
                        </div>
                        <div class="col-md-12 mb-3" id="addressArea">
                            <label class="form-label">Delivery Address</label>
                            <textarea id="address" class="form-control" rows="3"><?php echo esc_textarea($user_address); ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Special Notes (Optional)</label>
                            <textarea id="notes" class="form-control" rows="2" placeholder="e.g. Extra spicy, gate code..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="checkout-card sticky-top" style="top: 100px;">
                    <h4 class="fw-bold mb-4">Order Summary</h4>
                    
                    <div id="scheduleInfo" class="schedule-badge" style="display:none;">
                        <span class="dashicons dashicons-clock"></span>
                        <span>Scheduled for: <span id="timeValDisplay">ASAP</span></span>
                    </div>

                    <div id="itemsContainer" class="mb-4"></div>

                    <div class="border-top pt-3">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span class="fw-bold"><?php echo $currency; ?><span id="subtotalVal">0.00</span></span>
                        </div>
                        <div class="summary-item" id="deliveryRow">
                            <span>Delivery Fee</span>
                            <span class="fw-bold"><?php echo $currency; ?><span id="deliveryVal"><?php echo number_format(floatval($base_delivery_fee), 2); ?></span></span>
                        </div>
                        <div class="summary-item mt-3 pt-3 border-top" style="font-size: 1.4rem;">
                            <strong>Total</strong>
                            <strong style="color: var(--primary-red);"><?php echo $currency; ?><span id="totalVal">0.00</span></strong>
                        </div>
                    </div>

                    <input type="hidden" id="fd_nonce" value="<?php echo wp_create_nonce('fd_place_order_action'); ?>">
                    <button id="placeOrderBtn" class="place-order-btn">CONFIRM ORDER</button>
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
    const currency = "<?php echo $currency; ?>";

    function updateUI() {
        const isPickup = document.getElementById('typePickup').checked;
        const addressArea = document.getElementById('addressArea');
        const deliveryRow = document.getElementById('deliveryRow');
        const container = document.getElementById('itemsContainer');
        
        // Handle Scheduled Display
        if(scheduledTime !== 'asap') {
            document.getElementById('scheduleInfo').style.display = 'flex';
            document.getElementById('timeValDisplay').innerText = scheduledTime;
        }

        addressArea.style.display = isPickup ? 'none' : 'block';
        deliveryRow.style.display = isPickup ? 'none' : 'flex';

        if(cart.length === 0) {
            container.innerHTML = '<p class="text-muted">Your cart is empty.</p>';
            document.getElementById('placeOrderBtn').disabled = true;
            return;
        }

        let subtotal = 0;
        container.innerHTML = cart.map(item => {
            let itemTotal = item.price * item.qty;
            subtotal += itemTotal;
            return `<div class="d-flex justify-content-between mb-2">
                        <span><span class="fw-bold">${item.qty}x</span> ${item.name}</span>
                        <span>${currency}${itemTotal.toFixed(2)}</span>
                    </div>`;
        }).join('');

        const finalFee = isPickup ? 0 : deliveryFee;
        const grandTotal = subtotal + finalFee;

        document.getElementById('subtotalVal').innerText = subtotal.toFixed(2);
        document.getElementById('deliveryVal').innerText = finalFee.toFixed(2);
        document.getElementById('totalVal').innerText = grandTotal.toFixed(2);
    }

    document.querySelectorAll('input[name="orderType"]').forEach(radio => {
        radio.addEventListener('change', updateUI);
    });

    document.getElementById('placeOrderBtn').addEventListener('click', function() {
        const orderType = document.querySelector('input[name="orderType"]:checked').value;
        const btn = this;

        const data = {
            orderType: orderType,
            fullName: document.getElementById('fullName').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            address: orderType === 'delivery' ? document.getElementById('address').value.trim() : 'STORE PICKUP',
            notes: document.getElementById('notes').value.trim(),
            scheduledTime: scheduledTime, // Send New Feature Data
            cart: cart,
            subtotal: document.getElementById('subtotalVal').innerText,
            delivery: document.getElementById('deliveryVal').innerText,
            total: document.getElementById('totalVal').innerText
        };

        if(!data.fullName || !data.phone || (orderType === 'delivery' && !data.address)) {
            alert('Please fill in all required contact fields.');
            return;
        }

        btn.disabled = true;
        btn.innerText = "PROCESSING...";

        const fd = new FormData();
        fd.append('fd_place_order', JSON.stringify(data));
        fd.append('fd_nonce', document.getElementById('fd_nonce').value);

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                localStorage.removeItem('fd_cart_save');
                localStorage.removeItem('fd_scheduled_time'); // Clear the schedule storage
                window.location.href = '<?php echo home_url('/thanks/?order_id='); ?>' + res.order_id;
            } else {
                alert(res.message);
                btn.disabled = false;
                btn.innerText = "CONFIRM ORDER";
            }
        })
        .catch(() => {
            alert('Error connecting to server.');
            btn.disabled = false;
            btn.innerText = "CONFIRM ORDER";
        });
    });

    updateUI();
});
</script>

<?php get_footer(); ?>