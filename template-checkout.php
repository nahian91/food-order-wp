<?php
/*
Template Name: Checkout
*/

// 1. HANDLE AJAX ORDER SUBMISSION
if (isset($_POST['fd_place_order'])) {
    header('Content-Type: application/json');

    if (!is_user_logged_in()) {
        echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
        exit;
    }

    $user_id = get_current_user_id();
    $data = json_decode(stripslashes($_POST['fd_place_order']), true);

    if (!$data || empty($data['cart'])) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit;
    }

    $order_id = wp_insert_post([
        'post_type'   => 'food_order',
        'post_title'  => 'Order #' . time() . ' - ' . sanitize_text_field($data['fullName']),
        'post_status' => 'publish'
    ]);

    if ($order_id) {
        update_post_meta($order_id, 'customer_id', $user_id);
        update_post_meta($order_id, 'customer_name', sanitize_text_field($data['fullName']));
        update_post_meta($order_id, 'customer_email', sanitize_email($data['email']));
        update_post_meta($order_id, 'customer_phone', sanitize_text_field($data['phone']));
        update_post_meta($order_id, 'customer_address', sanitize_text_field($data['address']));
        update_post_meta($order_id, 'notes', sanitize_text_field($data['notes']));
        update_post_meta($order_id, 'items', $data['cart']); 
        update_post_meta($order_id, 'subtotal', floatval($data['subtotal']));
        update_post_meta($order_id, 'delivery', floatval($data['delivery']));
        update_post_meta($order_id, 'total_price', floatval($data['total']));
        update_post_meta($order_id, 'status', 'pending');

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode(get_permalink())));
    exit;
}

get_header();
$u = wp_get_current_user();
?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background:#333; padding: 80px 0; background-image:url(<?php echo get_template_directory_uri(); ?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <h1 class="text-white">Checkout</h1>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="mb-4">Billing Details</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Full Name</label>
                        <input type="text" class="form-control" id="fullName" value="<?php echo esc_attr($u->display_name); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo esc_attr($u->user_email); ?>" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Phone Number</label>
                        <input type="tel" class="form-control" id="phone" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Delivery Address</label>
                        <textarea class="form-control" id="address" rows="3" required></textarea>
                    </div>
                    <div class="col-md-12">
                        <label>Order Notes (Optional)</label>
                        <textarea class="form-control" id="notes" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 bg-light">
                <h4 class="mb-4">Your Order</h4>
                <div id="checkoutItemsList">
                    </div>
                
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>€<span id="subtotalVal">0.00</span></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee</span>
                    <span>€<span id="deliveryVal">5.00</span></span>
                </div>
                <div class="d-flex justify-content-between h5 mt-3">
                    <strong>Total</strong>
                    <strong class="text-danger">€<span id="totalVal">0.00</span></strong>
                </div>

                <button class="btn btn-danger btn-lg w-100 mt-4 shadow" id="placeOrderBtn" style="border-radius:10px; font-weight:bold;">
                    PLACE ORDER NOW
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    let cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    const itemsWrap = document.getElementById('checkoutItemsList');
    const subtotalEl = document.getElementById('subtotalVal');
    const totalEl = document.getElementById('totalVal');
    const deliveryFee = 5.00;

    function renderCheckout() {
        if(cart.length === 0){
            itemsWrap.innerHTML = '<p class="text-center py-4">Your cart is empty. <a href="<?php echo home_url('/menu'); ?>">Back to Menu</a></p>';
            document.getElementById('placeOrderBtn').disabled = true;
            subtotalEl.innerText = "0.00";
            totalEl.innerText = "0.00";
            return;
        }

        let subtotal = 0;
        let html = '';

        cart.forEach((item, index) => {
            let itemTotal = parseFloat(item.price) * parseInt(item.qty);
            subtotal += itemTotal;
            html += `
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <div>
                        <span class="fw-bold">${item.name}</span> <br>
                        <small class="text-muted">Qty: ${item.qty} × €${parseFloat(item.price).toFixed(2)}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">€${itemTotal.toFixed(2)}</div>
                        <a href="javascript:void(0)" class="text-danger small" onclick="removeCheckoutItem(${index})">Remove</a>
                    </div>
                </div>
            `;
        });

        itemsWrap.innerHTML = html;
        subtotalEl.innerText = subtotal.toFixed(2);
        totalEl.innerText = (subtotal + deliveryFee).toFixed(2);
    }

    // Global function to remove item
    window.removeCheckoutItem = function(index) {
        cart.splice(index, 1);
        localStorage.setItem('fd_cart_save', JSON.stringify(cart));
        renderCheckout();
        // Sync the header count if the function exists
        if (typeof syncHeaderCart === "function") syncHeaderCart();
    };

    renderCheckout();

    // Submit Order
    document.getElementById('placeOrderBtn').addEventListener('click', function(){
        const btn = this;
        const orderData = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            notes: document.getElementById('notes').value,
            cart: cart,
            subtotal: subtotalEl.innerText,
            delivery: deliveryFee.toFixed(2),
            total: totalEl.innerText
        };

        if(!orderData.fullName || !orderData.address || !orderData.phone) {
            alert('Please fill in all delivery details.');
            return;
        }

        btn.innerText = "Processing...";
        btn.disabled = true;

        const fd = new FormData();
        fd.append('fd_place_order', JSON.stringify(orderData));

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success'){
                localStorage.removeItem('fd_cart_save');
                window.location.href = '<?php echo home_url('/thanks'); ?>';
            } else {
                alert('Error: ' + res.message);
                btn.disabled = false;
                btn.innerText = "PLACE ORDER NOW";
            }
        });
    });
});
</script>

<?php get_footer(); ?>