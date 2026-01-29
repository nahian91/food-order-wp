<?php
/*
Template Name: Checkout
*/

/**
 * 1. AJAX HANDLER: PROCESSING THE ORDER
 */
if (isset($_POST['fd_place_order'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['fd_nonce']) || !wp_verify_nonce($_POST['fd_nonce'], 'fd_place_order_action')) {
        echo json_encode(['status' => 'error', 'message' => 'Security check failed. Please refresh the page.']);
        exit;
    }

    $raw_data = stripslashes($_POST['fd_place_order']);
    $data = json_decode($raw_data, true);

    if (!$data || empty($data['cart'])) {
        echo json_encode(['status' => 'error', 'message' => 'Your cart is empty or data is invalid.']);
        exit;
    }

    $user_id = get_current_user_id() ?: 0;
    $data['user_id'] = $user_id;

    // The fd_insert_custom_order function will receive 'kitchen_notes' from the JS data object
    $order_display_id = fd_insert_custom_order($data);

    if ($order_display_id) {
        if ($user_id > 0) {
            update_user_meta($user_id, 'phone', sanitize_text_field($data['phone']));
            if ($data['orderType'] === 'delivery') {
                update_user_meta($user_id, 'address', sanitize_textarea_field($data['address']));
            }
        }
        echo json_encode(['status' => 'success', 'order_id' => $order_display_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error. Order could not be saved.']);
    }
    exit;
}

get_header();

/**
 * 2. DATA PREPARATION
 */
$u = wp_get_current_user();
$user_phone = get_user_meta($u->ID, 'phone', true);
$user_address = get_user_meta($u->ID, 'address', true);

$currency = '£';
$base_delivery_fee = get_option('afd_delivery_charge', '0.00');
$service_fee       = get_option('afd_service_charge', '0.00');
$bag_fee           = get_option('afd_bag_charge', '0.00');
$rest_discount_pct = get_option('afd_restaurant_discount', '0'); 
?>

<style>
    :root { 
        --primary-red: #d63638; 
        --light-bg: #f8f9fa; 
        --border-color: #e5e7eb; 
        --text-dark: #333;
        --text-muted: #666;
    }

    .fd-checkout-wrapper { 
        background: var(--light-bg); 
        padding: 60px 0; 
        min-height: 90vh; 
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
        color: var(--text-dark); 
    }

    .checkout-card { 
        border: none; 
        border-radius: 20px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.04); 
        background: #fff; 
        padding: 30px; 
        margin-bottom: 30px; 
    }

    .section-title { 
        font-weight: 800; 
        font-size: 1.25rem; 
        margin-bottom: 25px; 
        display: flex; 
        align-items: center; 
        gap: 10px;
    }

    .form-label { 
        font-weight: 700; 
        font-size: 0.85rem; 
        color: #444; 
        margin-bottom: 8px; 
    }

    .form-control { 
        border-radius: 12px; 
        padding: 12px 16px; 
        border: 1.5px solid #eee; 
        transition: all 0.3s ease; 
        font-size: 15px; 
        background: #fafafa;
    }

    .form-control:focus { 
        background: #fff;
        border-color: var(--primary-red); 
        box-shadow: 0 0 0 4px rgba(214, 54, 56, 0.1); 
        outline: none;
    }

    .fulfillment-toggle { 
        display: flex; 
        background: #f1f1f1; 
        padding: 5px; 
        border-radius: 14px; 
        margin-bottom: 30px; 
    }
    .fulfillment-toggle input { display: none; }
    .fulfillment-toggle label { 
        flex: 1; 
        text-align: center; 
        padding: 12px; 
        border-radius: 10px; 
        cursor: pointer; 
        font-weight: 700; 
        transition: 0.3s; 
        color: var(--text-muted); 
        margin: 0; 
        font-size: 14px; 
    }
    .fulfillment-toggle input:checked + label { 
        background: var(--primary-red); 
        color: #fff; 
        box-shadow: 0 4px 10px rgba(214, 54, 56, 0.25);
    }

    .summary-item { 
        display: flex; 
        justify-content: space-between; 
        margin-bottom: 12px; 
        font-size: 14px; 
        color: #555; 
    }
    .summary-item strong { color: #1a1a1a; }
    
    .discount-line { color: #10b981; font-weight: 700; }

    .wc-payment-methods { 
        list-style: none; 
        padding: 0; 
        margin: 25px 0; 
        border: 1px solid var(--border-color); 
        border-radius: 15px; 
        overflow: hidden; 
    }
    .wc-payment-item { border-bottom: 1px solid var(--border-color); background: #fff; }
    .wc-payment-item:last-child { border-bottom: none; }
    .wc-payment-item input[type="radio"] { display: none; }
    .wc-payment-item label { 
        display: block; 
        padding: 18px; 
        cursor: pointer; 
        font-weight: 700; 
        color: var(--text-dark); 
        margin: 0; 
        font-size: 15px; 
    }
    .wc-payment-item label::before { 
        content: ""; 
        display: inline-block; 
        width: 18px; 
        height: 18px; 
        border: 2px solid #ddd; 
        border-radius: 50%; 
        margin-right: 12px; 
        vertical-align: middle; 
        background: #fff; 
    }
    .wc-payment-item input:checked + label { background: #fdfdfd; }
    .wc-payment-item input:checked + label::before { 
        border-color: var(--primary-red); 
        background: radial-gradient(var(--primary-red) 40%, #fff 50%); 
    }
    .payment-desc { 
        max-height: 0; 
        overflow: hidden; 
        transition: 0.3s ease; 
        padding: 0 18px; 
        font-size: 13px; 
        color: #888; 
    }
    .wc-payment-item input:checked ~ .payment-desc { max-height: 80px; padding: 0 18px 18px 48px; }

    .schedule-badge { 
        background: #fffbeb; 
        border: 1px solid #fef3c7; 
        color: #92400e; 
        padding: 12px; 
        border-radius: 12px; 
        font-size: 13px; 
        font-weight: 700; 
        margin-bottom: 25px; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }

    .place-order-btn { 
        background: var(--primary-red); 
        color: #fff; 
        border: none; 
        width: 100%; 
        padding: 18px; 
        border-radius: 15px; 
        font-weight: 800; 
        font-size: 1.15rem; 
        transition: 0.3s; 
        box-shadow: 0 8px 20px rgba(214, 54, 56, 0.25); 
        cursor: pointer;
    }
    .place-order-btn:hover { background: #b52a2c; transform: translateY(-2px); }
    .place-order-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }

    .cart-item-row { 
        display: flex; 
        justify-content: space-between; 
        padding: 8px 0; 
        border-bottom: 1px dashed #eee; 
        font-size: 14px;
    }
</style>

<div class="fd-checkout-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h5 class="section-title">1. How would you like your food?</h5>
                    <div class="fulfillment-toggle">
                        <input type="radio" name="orderType" id="typeDelivery" value="delivery" checked>
                        <label for="typeDelivery">🚚 Delivery</label>
                        <input type="radio" name="orderType" id="typePickup" value="pickup">
                        <label for="typePickup">🛍️ Pickup</label>
                    </div>

                    <h5 class="section-title">2. Contact Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="fullName" class="form-control" placeholder="Enter your name" value="<?php echo esc_attr($u->display_name); ?>">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="email" class="form-control" placeholder="your@email.com" value="<?php echo esc_attr($u->user_email); ?>">
                        </div>
                        <div class="col-md-12 mb-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="phone" class="form-control" placeholder="e.g. 07123456789" value="<?php echo esc_attr($user_phone); ?>">
                        </div>
                        <div class="col-md-12 mb-4" id="addressArea">
                            <label class="form-label">Delivery Address</label>
                            <textarea id="address" class="form-control" rows="3" placeholder="Street name, house number, postcode..."><?php echo esc_textarea($user_address); ?></textarea>
                        </div>
                        
                        <div class="col-md-12" id="deliveryNotesArea">
                            <label class="form-label">Notes for Delivery</label>
                            <textarea id="delivery_notes" class="form-control" rows="2" placeholder="e.g. Gate code 1234, knock loudly..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="checkout-card sticky-top" style="top: 20px;">
                    <h5 class="section-title">Order Summary</h5>
                    
                    <div id="scheduleInfo" class="schedule-badge" style="display:none;">
                        <span class="dashicons dashicons-clock"></span>
                        <span>Scheduled for: <span id="timeValDisplay">ASAP</span></span>
                    </div>

                    <div id="itemsContainer" class="mb-4"></div>

                    <div class="pt-3 border-top">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span><?php echo $currency; ?><span id="subtotalVal">0.00</span></span>
                        </div>
                        <div class="summary-item discount-line">
                            <span>Restaurant Discount (<?php echo esc_html($rest_discount_pct); ?>%)</span>
                            <span>-<?php echo $currency; ?><span id="discountVal">0.00</span></span>
                        </div>
                        <div class="summary-item">
                            <span>Service Charge</span>
                            <span><?php echo $currency . number_format(floatval($service_fee), 2); ?></span>
                        </div>
                        <div class="summary-item" id="deliveryRow">
                            <span>Delivery Fee</span>
                            <span><?php echo $currency; ?><span id="deliveryVal"><?php echo number_format(floatval($base_delivery_fee), 2); ?></span></span>
                        </div>
                        <div class="summary-item">
                            <span>Bag Charge</span>
                            <span><?php echo $currency . number_format(floatval($bag_fee), 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Add a Tip for Driver</span>
                            <div class="d-flex align-items-center">
                                <span class="me-1"><?php echo $currency; ?></span>
                                <input type="number" id="tipAmount" class="form-control p-1 text-end" style="width:70px; height:30px;" value="0.00" step="0.50" min="0">
                            </div>
                        </div>

                        <div class="summary-item mt-3 pt-3 border-top" style="font-size: 1.4rem;">
                            <strong>Total Due</strong>
                            <strong style="color: var(--primary-red);"><?php echo $currency; ?><span id="totalDueVal">0.00</span></strong>
                        </div>
                    </div>

                    <div class="wc-payment-methods">
                        <div class="wc-payment-item">
                            <input type="radio" name="paymentMethod" id="payCash" value="cash" checked>
                            <label for="payCash">Cash Payment</label>
                            <div class="payment-desc">Pay with cash at your doorstep or when picking up.</div>
                        </div>
                        <div class="wc-payment-item">
                            <input type="radio" name="paymentMethod" id="payCard" value="card">
                            <label for="payCard">Card on Arrival</label>
                            <div class="payment-desc">We will bring a card machine to your location.</div>
                        </div>
                    </div>

                    <input type="hidden" id="fd_nonce" value="<?php echo wp_create_nonce('fd_place_order_action'); ?>">
                    <button id="placeOrderBtn" class="place-order-btn">PLACE YOUR ORDER</button>
                    
                    <p class="text-center mt-4 text-muted" style="font-size: 11px; line-height: 1.5;">
                        By placing your order, you agree to our terms and conditions.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    const scheduledTime = localStorage.getItem('fd_scheduled_time') || 'asap';
    const kitchenNotes = localStorage.getItem('fd_kitchen_notes') || ''; // RETRIEVING FROM SHORTCODE
    
    const deliveryFee = parseFloat("<?php echo $base_delivery_fee; ?>") || 0;
    const serviceFee = parseFloat("<?php echo $service_fee; ?>") || 0;
    const bagFee = parseFloat("<?php echo $bag_fee; ?>") || 0;
    const restDiscountPct = parseFloat("<?php echo $rest_discount_pct; ?>") || 0;
    const currency = "<?php echo $currency; ?>";

    function calculateTotals() {
        const isPickup = document.getElementById('typePickup').checked;
        const container = document.getElementById('itemsContainer');
        const tipVal = parseFloat(document.getElementById('tipAmount').value) || 0;
        
        // UI Toggles based on Fulfillment Type
        document.getElementById('addressArea').style.display = isPickup ? 'none' : 'block';
        document.getElementById('deliveryRow').style.display = isPickup ? 'none' : 'flex';
        document.getElementById('deliveryNotesArea').style.display = isPickup ? 'none' : 'block';
        
        if(scheduledTime !== 'asap') {
            document.getElementById('scheduleInfo').style.display = 'flex';
            document.getElementById('timeValDisplay').innerText = scheduledTime;
        }

        if(cart.length === 0) {
            container.innerHTML = '<div class="alert alert-warning py-2" style="font-size:13px;">Your cart is empty.</div>';
            return;
        }

        let subtotal = 0;
        container.innerHTML = cart.map(item => {
            let itemTotal = item.price * item.qty;
            subtotal += itemTotal;
            return `
                <div class="cart-item-row">
                    <span><strong>${item.qty}x</strong> ${item.name}</span>
                    <span class="fw-bold">${currency}${itemTotal.toFixed(2)}</span>
                </div>`;
        }).join('');

        const discountVal = (subtotal * restDiscountPct) / 100;
        const afterDiscount = subtotal - discountVal;
        const finalDelivery = isPickup ? 0 : deliveryFee;
        const grandTotal = (afterDiscount + serviceFee + finalDelivery + bagFee + tipVal);

        document.getElementById('subtotalVal').innerText = subtotal.toFixed(2);
        document.getElementById('discountVal').innerText = discountVal.toFixed(2);
        document.getElementById('deliveryVal').innerText = finalDelivery.toFixed(2);
        document.getElementById('totalDueVal').innerText = grandTotal.toFixed(2);
    }

    document.getElementById('placeOrderBtn').addEventListener('click', function() {
        const orderType = document.querySelector('input[name="orderType"]:checked').value;
        const btn = this;

        const orderData = {
            orderType: orderType,
            paymentMethod: document.querySelector('input[name="paymentMethod"]:checked').value,
            fullName: document.getElementById('fullName').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            address: (orderType === 'pickup') ? 'COLLECTION' : document.getElementById('address').value.trim(),
            
            // USE THE VALUE RETRIEVED FROM LOCALSTORAGE
            kitchen_notes: kitchenNotes, 
            delivery_notes: (orderType === 'pickup') ? '' : document.getElementById('delivery_notes').value.trim(),
            
            scheduledTime: scheduledTime,
            cart: cart,
            subtotal: document.getElementById('subtotalVal').innerText,
            service_fee: serviceFee,
            bag_fee: bagFee,
            tip: document.getElementById('tipAmount').value,
            delivery: document.getElementById('deliveryVal').innerText,
            total: document.getElementById('totalDueVal').innerText
        };

        if(!orderData.fullName || !orderData.phone) {
            alert('Please provide your name and phone number.');
            return;
        }
        if(orderType === 'delivery' && !orderData.address) {
            alert('Please provide a delivery address.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> SECURING ORDER...';

        const formData = new FormData();
        formData.append('fd_place_order', JSON.stringify(orderData));
        formData.append('fd_nonce', document.getElementById('fd_nonce').value);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if(result.status === 'success') {
                localStorage.removeItem('fd_cart_save');
                localStorage.removeItem('fd_scheduled_time');
                localStorage.removeItem('fd_kitchen_notes'); // CLEAN UP
                window.location.href = "<?php echo home_url('/thanks/?order_id='); ?>" + result.order_id;
            } else {
                alert(result.message);
                btn.disabled = false;
                btn.innerText = "PLACE YOUR ORDER";
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerText = "PLACE YOUR ORDER";
        });
    });

    document.querySelectorAll('input[name="orderType"]').forEach(radio => {
        radio.addEventListener('change', calculateTotals);
    });
    document.getElementById('tipAmount').addEventListener('input', calculateTotals);

    calculateTotals();
});
</script>

<?php get_footer(); ?>