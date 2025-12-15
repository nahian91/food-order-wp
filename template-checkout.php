<?php
/*
Template Name: Checkout
*/

// --------------------- HANDLE AJAX ORDER ---------------------
if(isset($_POST['fd_place_order'])){
    header('Content-Type: text/plain');

    if(!is_user_logged_in()){
        echo 'error: not logged in';
        exit;
    }

    $user_id = get_current_user_id();
    $data = json_decode(stripslashes($_POST['fd_place_order']), true);

    if(!$data){
        echo 'error: no data';
        exit;
    }

    // Make sure CPT exists
    if(!post_type_exists('food_order')){
        register_post_type('food_order', [
            'labels' => ['name'=>'Food Orders','singular_name'=>'Food Order'],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
        ]);
    }

    $order_id = wp_insert_post([
        'post_type'   => 'food_order',
        'post_title'  => 'Order #' . time(),
        'post_status' => 'publish'
    ]);

    if(!$order_id){
        echo 'error: could not create order';
        exit;
    }

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

    echo 'success';
    exit;
}

// --------------------- REDIRECT IF NOT LOGGED IN ---------------------
if(!is_user_logged_in()){
    wp_redirect(site_url('/login/'));
    exit;
}

get_header();
?>

<div class="breadcrumb-area bg-cover text-center text-light"
     style="background-image:url(<?php echo get_template_directory_uri(); ?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Checkout</h1>
                <ul class="breadcrumb">
                    <li><a href="<?php echo home_url(); ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li>Checkout</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">

        <!-- ================= BILLING DETAILS ================= -->
        <div class="col-lg-6 mb-4">
            <h4>Billing Details</h4>
            <form id="fdCheckoutForm">
                <input type="hidden" id="fdIsLoggedIn" value="1">
                <div class="mb-3">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullName" placeholder="Enter your name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" placeholder="Enter your phone number" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" rows="3" placeholder="Delivery address" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Order Notes (Optional)</label>
                    <textarea class="form-control" id="notes" rows="2" placeholder="Any special instructions?"></textarea>
                </div>
            </form>
        </div>

        <!-- ================= ORDER SUMMARY ================= -->
        <div class="col-lg-6 mb-4">
            <h4>Order Summary</h4>
            <div class="card">
                <div class="card-body">
                    <ul class="list-group mb-3" id="checkoutItems">
                        <li class="list-group-item">Loading items...</li>
                    </ul>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subtotal</span>
                            <strong>$<span id="checkoutSubtotal">0</span></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Delivery</span>
                            <strong>$<span id="checkoutDelivery">10</span></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total</span>
                            <strong>$<span id="checkoutTotal">0</span></strong>
                        </li>
                    </ul>
                    <button class="btn btn-success w-100 mt-4" id="placeOrderBtn">Place Order</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    const cart = JSON.parse(localStorage.getItem('fd_cart')) || [];
    const itemsWrap = document.getElementById('checkoutItems');
    const subtotalEl = document.getElementById('checkoutSubtotal');
    const deliveryEl = document.getElementById('checkoutDelivery');
    const totalEl = document.getElementById('checkoutTotal');

    if(cart.length === 0){
        itemsWrap.innerHTML = '<li class="list-group-item">Your cart is empty</li>';
        subtotalEl.innerText = "0";
        totalEl.innerText = deliveryEl.innerText;
        return;
    }

    let grouped = {};
    let subtotal = 0;

    cart.forEach(item => {
        subtotal += parseFloat(item.price);
        if(!grouped[item.name]){
            grouped[item.name] = { qty:0, price: parseFloat(item.price) };
        }
        grouped[item.name].qty++;
    });

    itemsWrap.innerHTML = '';
    for(let name in grouped){
        let li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = `${name} × ${grouped[name].qty} <span>$${(grouped[name].qty*grouped[name].price).toFixed(2)}</span>`;
        itemsWrap.appendChild(li);
    }

    subtotalEl.innerText = subtotal.toFixed(2);
    const delivery = parseFloat(deliveryEl.innerText);
    totalEl.innerText = (subtotal + delivery).toFixed(2);

    document.getElementById('placeOrderBtn').addEventListener('click', function(e){
        e.preventDefault();

        const orderData = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            notes: document.getElementById('notes').value,
            cart: cart,
            subtotal: subtotal.toFixed(2),
            delivery: delivery.toFixed(2),
            total: (subtotal + delivery).toFixed(2)
        };

        const fdData = new FormData();
        fdData.append('fd_place_order', JSON.stringify(orderData));

        fetch(window.location.href, { method:'POST', body: fdData })
        .then(res => res.text())
        .then(res=>{
            if(res === 'success'){
                localStorage.removeItem('fd_cart');
                alert('Order placed successfully!');
                location.reload();
            } else {
                alert('Something went wrong. Try again.');
                console.log(res);
            }
        })
        .catch(err=>{
            alert('Something went wrong. Try again.');
            console.error(err);
        });
    });

});
</script>

<?php get_footer(); ?>
