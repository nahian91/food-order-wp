<?php
/*
Template Name: Checkout
*/

/**
 * 1. SERVER-SIDE AJAX HANDLER: PROCESSING THE ORDER
 */
if (isset($_POST['fd_place_order'])) {
    header('Content-Type: application/json');

    // Security Nonce Verification
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

    /**
     * Call the custom function to insert into database.
     * Ensure this is defined in your functions.php.
     */
    $order_display_id = function_exists('fd_insert_custom_order') ? fd_insert_custom_order($data) : false;

    if ($order_display_id) {
        // Update User Meta for faster future checkouts
        if ($user_id > 0) {
            update_user_meta($user_id, 'phone', sanitize_text_field($data['phone']));
            if ($data['orderType'] === 'delivery') {
                update_user_meta($user_id, 'address', sanitize_textarea_field($data['address']));
            }
        }
        echo json_encode(['status' => 'success', 'order_id' => $order_display_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Critical: Database error. Order could not be saved.']);
    }
    exit;
}

get_header();

/**
 * 2. DATA INITIALIZATION
 */
$u = wp_get_current_user();
$user_phone = get_user_meta($u->ID, 'phone', true);
$user_address = get_user_meta($u->ID, 'address', true);

// Get Global Settings
$currency = '£';
$base_delivery_fee = get_option('afd_delivery_charge', '0.00');
$service_fee       = get_option('afd_service_charge', '0.00');
$bag_fee           = get_option('afd_bag_charge', '0.00');

// Conditional Discounts
$delivery_discount_pct   = get_option('afd_delivery_discount', '5'); 
$collection_discount_pct = get_option('afd_collection_discount', '0'); 
?>

<style>
    :root { 
        --primary: #d63638; 
        --primary-soft: rgba(214, 54, 56, 0.1);
        --bg: #f9fafb; 
        --card-bg: #ffffff;
        --border: #eef0f2; 
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --success: #10b981;
    }

    .fd-checkout-container { 
        background: var(--bg); 
        padding: 80px 0; 
        min-height: 100vh; 
        font-family: 'Inter', -apple-system, system-ui, sans-serif; 
        color: var(--text-main); 
    }

    /* Card Styling */
    .checkout-card { 
        background: var(--card-bg);
        border-radius: 24px; 
        border: 1px solid var(--border);
        box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); 
        padding: 35px; 
        margin-bottom: 30px; 
    }

    .section-title { 
        font-weight: 800; 
        font-size: 1.35rem; 
        margin-bottom: 30px; 
        display: flex; 
        align-items: center; 
        letter-spacing: -0.5px;
    }

    /* Form Elements */
    .form-group { margin-bottom: 20px; }
    .form-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block; }
    .form-control { 
        border-radius: 14px; 
        padding: 14px 18px; 
        border: 1.5px solid var(--border); 
        transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
        font-size: 15px; 
        background: #fcfcfc;
        width: 100%;
    }
    .form-control:focus { 
        background: #fff;
        border-color: var(--primary); 
        box-shadow: 0 0 0 4px var(--primary-soft); 
        outline: none;
    }

    /* Fulfillment Toggle */
    .fulfillment-toggle { 
        display: flex; 
        background: #f3f4f6; 
        padding: 6px; 
        border-radius: 16px; 
        margin-bottom: 35px; 
    }
    .fulfillment-toggle input { display: none; }
    .fulfillment-toggle label { 
        flex: 1; 
        text-align: center; 
        padding: 14px; 
        border-radius: 12px; 
        cursor: pointer; 
        font-weight: 700; 
        transition: 0.3s; 
        color: var(--text-muted); 
        margin: 0; 
        font-size: 15px; 
    }
    .fulfillment-toggle input:checked + label { 
        background: var(--primary); 
        color: #fff; 
        box-shadow: 0 4px 12px rgba(214, 54, 56, 0.2);
    }

    /* Summary Items */
    .summary-item { 
        display: flex; 
        justify-content: space-between; 
        margin-bottom: 14px; 
        font-size: 14.5px; 
        color: var(--text-main); 
    }
    .discount-line { color: var(--success); font-weight: 700; }
    .order-total-row { 
        border-top: 2px solid var(--border); 
        border-bottom: 2px solid var(--border); 
        padding: 15px 0; 
        margin: 15px 0; 
        font-weight: 800; 
        font-size: 1rem;
    }

    /* Payment List */
    .payment-list { list-style: none; padding: 0; border: 1px solid var(--border); border-radius: 18px; overflow: hidden; }
    .payment-item { border-bottom: 1px solid var(--border); }
    .payment-item:last-child { border-bottom: none; }
    .payment-item input { display: none; }
    .payment-item label { 
        display: flex; 
        align-items: center; 
        padding: 20px; 
        cursor: pointer; 
        font-weight: 700; 
        margin: 0; 
    }
    .payment-item label::before { 
        content: ""; 
        width: 20px; height: 20px; 
        border: 2px solid #d1d5db; 
        border-radius: 50%; 
        margin-right: 15px; 
        transition: 0.2s;
    }
    .payment-item input:checked + label { background: #fafafa; }
    .payment-item input:checked + label::before { 
        border-color: var(--primary); 
        background: radial-gradient(var(--primary) 40%, #fff 50%); 
    }

    /* Order Button */
    .btn-place-order { 
        background: var(--primary); 
        color: #fff; 
        border: none; 
        width: 100%; 
        padding: 20px; 
        border-radius: 18px; 
        font-weight: 800; 
        font-size: 1.2rem; 
        transition: 0.3s; 
        box-shadow: 0 10px 25px rgba(214, 54, 56, 0.2); 
        cursor: pointer;
        margin-top: 20px;
    }
    .btn-place-order:hover { transform: translateY(-3px); filter: brightness(1.1); }
    .btn-place-order:disabled { background: #d1d5db; cursor: not-allowed; transform: none; box-shadow: none; }

    .item-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
    .item-row:last-child { border-bottom: none; }
    
    @media (max-width: 991px) { .fd-checkout-container { padding: 30px 0; } }
</style>

<div class="fd-checkout-container">
    <div class="container">
        <form id="checkoutForm">
            <div class="row">
                <div class="col-lg-7">
                    <div class="checkout-card">
                        <h5 class="section-title">📦 1. Order Method</h5>
                        <div class="fulfillment-toggle">
                            <input type="radio" name="orderType" id="typeDelivery" value="delivery" checked>
                            <label for="typeDelivery">Delivery</label>
                            <input type="radio" name="orderType" id="typePickup" value="pickup">
                            <label for="typePickup">Collection</label>
                        </div>

                        <h5 class="section-title">👤 2. Contact Information</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" id="fullName" class="form-control" placeholder="John Doe" value="<?php echo esc_attr($u->display_name); ?>" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" id="email" class="form-control" placeholder="john@example.com" value="<?php echo esc_attr($u->user_email); ?>" required>
                            </div>
                            <div class="col-md-12 form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" id="phone" class="form-control" placeholder="07123 456789" value="<?php echo esc_attr($user_phone); ?>" required>
                            </div>
                            <div class="col-md-12 form-group" id="addressArea">
                                <label class="form-label">Delivery Address</label>
                                <textarea id="address" class="form-control" rows="3" placeholder="Flat No, Street, Postcode"><?php echo esc_textarea($user_address); ?></textarea>
                            </div>
                            <div class="col-md-12 form-group">
                                <label class="form-label">Delivery Notes (Optional)</label>
                                <textarea id="delivery_notes" class="form-control" rows="2" placeholder="Gate code, knock loudly, etc."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="checkout-card sticky-top" style="top: 20px;">
                        <h5 class="section-title">📋 Order Summary</h5>
                        <div id="itemsList" class="mb-4"></div>

                        <div class="summary-details">
                            <div class="summary-item">
                                <span>Subtotal</span>
                                <span><?php echo $currency; ?><span id="uiSubtotal">0.00</span></span>
                            </div>
                            
                            <div class="summary-item discount-line">
                                <span id="uiDiscountLabel">Delivery Discount</span>
                                <span>-<?php echo $currency; ?><span id="uiDiscountVal">0.00</span></span>
                            </div>

                            <div class="summary-item order-total-row">
                                <span>Order Total</span>
                                <span><?php echo $currency; ?><span id="uiOrderTotal">0.00</span></span>
                            </div>

                            <div class="summary-item">
                                <span>Service Charge</span>
                                <span><?php echo $currency . number_format((float)$service_fee, 2); ?></span>
                            </div>

                            <div class="summary-item" id="fulfillmentRow">
                                <span id="uiFulfillmentLabel">Delivery Fee</span>
                                <span><?php echo $currency; ?><span id="uiFulfillmentVal">0.00</span></span>
                            </div>

                            <div class="summary-item">
                                <span>Bag Charge</span>
                                <span><?php echo $currency . number_format((float)$bag_fee, 2); ?></span>
                            </div>

                            <div class="summary-item">
                                <span>Driver Tip</span>
                                <div class="d-flex align-items-center">
                                    <span class="me-1"><?php echo $currency; ?></span>
                                    <input type="number" id="driverTip" class="form-control p-1 text-end" style="width:85px; height:32px; border-radius: 8px;" value="0.00" step="0.50" min="0">
                                </div>
                            </div>

                            <div class="summary-item mt-4 pt-3" style="border-top: 1px solid var(--border)">
                                <strong style="font-size: 1.5rem;">Grand Total</strong>
                                <strong style="color: var(--primary); font-size: 1.5rem;"><?php echo $currency; ?><span id="uiGrandTotal">0.00</span></strong>
                            </div>
                        </div>

                        <div class="payment-list mt-4">
                            <div class="payment-item">
                                <input type="radio" name="payment" id="payCash" value="cash" checked>
                                <label for="payCash">Cash on Delivery / Collection</label>
                            </div>
                            <div class="payment-item">
                                <input type="radio" name="payment" id="payCard" value="card">
                                <label for="payCard">Card Machine on Arrival</label>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="btn-place-order">PLACE ORDER NOW</button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">By placing your order, you agree to our terms of service.</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Local Storage Data
    const cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    const kitchenNotes = localStorage.getItem('fd_kitchen_notes') || '';
    const scheduledTime = localStorage.getItem('fd_scheduled_time') || 'asap';

    // 2. Pricing Configuration from PHP
    const config = {
        deliveryFee: parseFloat("<?php echo $base_delivery_fee; ?>"),
        serviceFee: parseFloat("<?php echo $service_fee; ?>"),
        bagFee: parseFloat("<?php echo $bag_fee; ?>"),
        delDiscountPct: parseFloat("<?php echo $delivery_discount_pct; ?>"),
        collDiscountPct: parseFloat("<?php echo $collection_discount_pct; ?>"),
        currency: "<?php echo $currency; ?>"
    };

    /**
     * Re-calculate all totals based on UI state
     */
    function updateTotals() {
        const isPickup = document.getElementById('typePickup').checked;
        const tipVal = parseFloat(document.getElementById('driverTip').value) || 0;
        
        // Dynamic UI Text Updates
        document.getElementById('addressArea').style.display = isPickup ? 'none' : 'block';
        document.getElementById('uiFulfillmentLabel').innerText = isPickup ? 'Collection Fee' : 'Delivery Fee';
        document.getElementById('uiDiscountLabel').innerText = isPickup 
            ? `Collection Discount (${config.collDiscountPct}%)` 
            : `Delivery Discount (${config.delDiscountPct}%)`;

        // Calculate Subtotal from Cart
        let subtotal = 0;
        const itemsList = document.getElementById('itemsList');
        
        if (cart.length === 0) {
            itemsList.innerHTML = '<div class="alert alert-warning">Your cart is empty!</div>';
            return;
        }

        itemsList.innerHTML = cart.map(item => {
            let unitPrice = parseFloat(item.price) + (parseFloat(item.vPrice) || 0);
            let itemTotal = unitPrice * item.qty;
            subtotal += itemTotal;
            return `
                <div class="item-row">
                    <span>
                        <strong class="text-danger">${item.qty}x</strong> ${item.name}
                        ${item.vName ? `<br><small class="text-muted">${item.vName}</small>` : ''}
                    </span>
                    <strong>${config.currency}${itemTotal.toFixed(2)}</strong>
                </div>`;
        }).join('');

        // Apply Logic
        const discountPct = isPickup ? config.collDiscountPct : config.delDiscountPct;
        const discountVal = (subtotal * discountPct) / 100;
        const orderTotal = subtotal - discountVal;
        const fulfillmentFee = isPickup ? 0 : config.deliveryFee;

        const grandTotal = orderTotal + config.serviceFee + fulfillmentFee + config.bagFee + tipVal;

        // Final UI Mapping
        document.getElementById('uiSubtotal').innerText = subtotal.toFixed(2);
        document.getElementById('uiDiscountVal').innerText = discountVal.toFixed(2);
        document.getElementById('uiOrderTotal').innerText = orderTotal.toFixed(2);
        document.getElementById('uiFulfillmentVal').innerText = fulfillmentFee.toFixed(2);
        document.getElementById('uiGrandTotal').innerText = grandTotal.toFixed(2);
    }

    /**
     * Handle Form Submission
     */
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const isPickup = document.getElementById('typePickup').checked;

        // Basic validation
        if (cart.length === 0) { alert('Cart is empty'); return; }

        const orderData = {
            orderType: isPickup ? 'collection' : 'delivery',
            paymentMethod: document.querySelector('input[name="payment"]:checked').value,
            fullName: document.getElementById('fullName').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            address: isPickup ? 'COLLECTION' : document.getElementById('address').value.trim(),
            delivery_notes: document.getElementById('delivery_notes').value.trim(),
            kitchen_notes: kitchenNotes,
            scheduledTime: scheduledTime,
            cart: cart,
            totals: {
                subtotal: document.getElementById('uiSubtotal').innerText,
                discount: document.getElementById('uiDiscountVal').innerText,
                orderTotal: document.getElementById('uiOrderTotal').innerText,
                service: config.serviceFee,
                delivery: document.getElementById('uiFulfillmentVal').innerText,
                bag: config.bagFee,
                tip: document.getElementById('driverTip').value,
                grandTotal: document.getElementById('uiGrandTotal').innerText
            }
        };

        // UI Loading State
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> PROCESSING...';

        const formData = new FormData();
        formData.append('fd_place_order', JSON.stringify(orderData));
        formData.append('fd_nonce', document.getElementById('fd_nonce').value);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                // Clear local memory
                localStorage.removeItem('fd_cart_save');
                localStorage.removeItem('fd_scheduled_time');
                localStorage.removeItem('fd_kitchen_notes');
                // Redirect to success page
                window.location.href = "<?php echo home_url('/thanks/?order_id='); ?>" + res.order_id;
            } else {
                alert(res.message);
                btn.disabled = false;
                btn.innerText = 'PLACE ORDER NOW';
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerText = 'PLACE ORDER NOW';
        });
    });

    // Event Listeners for UI interaction
    document.querySelectorAll('input[name="orderType"]').forEach(el => el.addEventListener('change', updateTotals));
    document.getElementById('driverTip').addEventListener('input', updateTotals);

    // Initial Run
    updateTotals();
});
</script>

<input type="hidden" id="fd_nonce" value="<?php echo wp_create_nonce('fd_place_order_action'); ?>">

<?php get_footer(); ?>