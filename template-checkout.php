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
    
    $order_id = wp_insert_post([
        'post_type'   => 'food_order',
        'post_title'  => 'Order #' . time() . ' - ' . sanitize_text_field($data['fullName']),
        'post_status' => 'publish',
        'post_author' => $user_id
    ]);

    if ($order_id) {
        // Meta Data Storage
        update_post_meta($order_id, 'customer_id', $user_id);
        update_post_meta($order_id, 'order_type', sanitize_text_field($data['orderType']));
        update_post_meta($order_id, 'customer_name', sanitize_text_field($data['fullName']));
        update_post_meta($order_id, 'customer_email', sanitize_email($data['email']));
        update_post_meta($order_id, 'customer_phone', sanitize_text_field($data['phone']));
        update_post_meta($order_id, 'customer_address', sanitize_textarea_field($data['address']));
        update_post_meta($order_id, 'order_notes', sanitize_textarea_field($data['notes']));
        update_post_meta($order_id, 'order_items', $data['cart']); 
        update_post_meta($order_id, 'subtotal', floatval($data['subtotal']));
        update_post_meta($order_id, 'delivery_fee', floatval($data['delivery']));
        update_post_meta($order_id, 'total_price', floatval($data['total']));
        update_post_meta($order_id, 'order_status', 'pending');

        // SYNC: Update User Profile meta so it auto-fills next time
        if ($user_id > 0) {
            update_user_meta($user_id, 'phone', sanitize_text_field($data['phone']));
            // Only update address if it's a delivery order
            if ($data['orderType'] === 'delivery') {
                update_user_meta($user_id, 'address', sanitize_textarea_field($data['address']));
            }
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

get_header();

// PRE-FILL LOGIC: Fetch profile data
$u = wp_get_current_user();
$user_id = $u->ID;
$user_phone = get_user_meta($user_id, 'phone', true);
$user_address = get_user_meta($user_id, 'address', true);

$currency = '£';
$base_delivery_fee = 3.50;
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
                    <h4 class="fw-bold mb-4">How should we get it to you?</h4>
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
                            <input type="text" id="fullName" class="form-control" value="<?php echo esc_attr($u->display_name); ?>" placeholder="John Doe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="email" class="form-control" value="<?php echo esc_attr($u->user_email); ?>" placeholder="john@example.com">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="phone" class="form-control" value="<?php echo esc_attr($user_phone); ?>" placeholder="07123 456789">
                        </div>
                        
                        <div class="col-md-12 mb-3" id="addressArea">
                            <label class="form-label">Delivery Address</label>
                            <textarea id="address" class="form-control" rows="3" placeholder="Street, City, Postcode"><?php echo esc_textarea($user_address); ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Special Notes (Optional)</label>
                            <textarea id="notes" class="form-control" rows="2" placeholder="Gate codes, allergies, etc."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="checkout-card sticky-top sticky-column" style="top: 20px;">
                    <h4 class="fw-bold mb-4">Your Order Summary</h4>
                    <div id="itemsContainer" class="mb-4"></div>

                    <div class="border-top pt-3">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span class="fw-bold"><?php echo $currency; ?><span id="subtotalVal">0.00</span></span>
                        </div>
                        <div class="summary-item" id="deliveryRow">
                            <span>Delivery Fee</span>
                            <span class="fw-bold"><?php echo $currency; ?><span id="deliveryVal"><?php echo number_format($base_delivery_fee, 2); ?></span></span>
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
    const deliveryFee = <?php echo $base_delivery_fee; ?>;
    const currency = "<?php echo $currency; ?>";

    function updateUI() {
        const isPickup = document.getElementById('typePickup').checked;
        const addressArea = document.getElementById('addressArea');
        const deliveryRow = document.getElementById('deliveryRow');
        const container = document.getElementById('itemsContainer');
        
        addressArea.style.display = isPickup ? 'none' : 'block';
        deliveryRow.style.display = isPickup ? 'none' : 'flex';

        if(cart.length === 0) {
            container.innerHTML = '<p class="text-muted">Your cart is empty.</p>';
            document.getElementById('placeOrderBtn').disabled = true;
            return;
        }

        let subtotal = 0;
        container.innerHTML = cart.map(item => {
            let total = item.price * item.qty;
            subtotal += total;
            return `<div class="d-flex justify-content-between mb-2">
                        <span><span class="fw-bold">${item.qty}x</span> ${item.name}</span>
                        <span>${currency}${total.toFixed(2)}</span>
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
            cart: cart,
            subtotal: document.getElementById('subtotalVal').innerText,
            delivery: document.getElementById('deliveryVal').innerText,
            total: document.getElementById('totalVal').innerText
        };

        if(!data.fullName || !data.phone || (orderType === 'delivery' && !data.address)) {
            alert('Please fill in all required fields.');
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
                window.location.href = '<?php echo home_url('/thanks'); ?>';
            } else {
                alert(res.message);
                btn.disabled = false;
                btn.innerText = "CONFIRM ORDER";
            }
        })
        .catch(() => {
            alert('Error connecting to server.');
            btn.disabled = false;
        });
    });

    updateUI();
});
</script>

<?php get_footer(); ?>