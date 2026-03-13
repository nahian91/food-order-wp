<?php
/*
Template Name: Checkout
*/

/**
 * 1. SERVER-SIDE ENGINE (Header Redirect Protocol)
 */
if (isset($_POST['fd_submit_hidden'])) {
    
    if (!isset($_POST['fd_nonce']) || !wp_verify_nonce($_POST['fd_nonce'], 'fd_place_order_action')) {
        wp_die('<h1>Session Expired</h1><p>Please refresh the checkout page and try again.</p>');
    }

    $raw_data = stripslashes($_POST['fd_order_json']);
    $data = json_decode($raw_data, true);

    if ($data && !empty($data['cart'])) {
        $user_id = get_current_user_id() ?: 0;
        $data['user_id'] = $user_id;

        // DB Insertion using the external function in functions.php
        $order_id = function_exists('fd_insert_custom_order') ? fd_insert_custom_order($data) : false;

        if ($order_id) {
            // SYNC USER METADATA: Ensures the "Bento" fields are saved to user profile for next time
            if ($user_id > 0) {
                update_user_meta($user_id, 'billing_phone', sanitize_text_field($data['phone']));
                if ($data['orderType'] === 'delivery') {
                    update_user_meta($user_id, 'fd_flat_no', sanitize_text_field($data['flat_no']));
                    update_user_meta($user_id, 'fd_building', sanitize_text_field($data['building']));
                    update_user_meta($user_id, 'fd_door_no', sanitize_text_field($data['door_no']));
                    update_user_meta($user_id, 'fd_road_name', sanitize_text_field($data['road_name']));
                    update_user_meta($user_id, 'fd_user_postcode', strtoupper(sanitize_text_field($data['postcode'])));
                }
            }

            // Redirect to Success Page using the Display ID returned by the function
            wp_redirect(add_query_arg('order_id', $order_id, home_url('/thanks/')));
            exit;
        } else {
            wp_die('Error 500: Database failure. Check if the fd_insert_custom_order function matches your table columns.');
        }
    }
}

get_header();

/**
 * 2. CONFIGURATION & DATA HYDRATION
 */
$u = wp_get_current_user();
$currency = '£';

// Dynamic Fees from Settings
$delivery_charge    = get_option('afd_delivery_charge', '0.00');
$collection_charge  = get_option('afd_pickup_charge', '0.00'); 
$service_fee     = get_option('afd_service_charge', '0.00');
$bag_fee         = get_option('afd_bag_charge', '0.00');
$delivery_disc   = get_option('afd_delivery_discount_percent', '0'); 
$collection_disc = get_option('afd_pickup_discount_percent', '0');

// Autocomplete Data from Meta
$user_phone     = get_user_meta($u->ID, 'billing_phone', true) ?: get_user_meta($u->ID, 'fd_user_phone', true);
$user_flat      = get_user_meta($u->ID, 'fd_flat_no', true);
$user_building  = get_user_meta($u->ID, 'fd_building', true);
$user_door      = get_user_meta($u->ID, 'fd_door_no', true);
$user_road      = get_user_meta($u->ID, 'fd_road_name', true);
$user_postcode  = get_user_meta($u->ID, 'fd_user_postcode', true);
?>

<style>
    :root { 
        --fd-red: #d63638; 
        --fd-red-light: #fff5f5;
        --fd-border: #e2e8f0; 
        --fd-text: #0f172a;
        --fd-muted: #64748b;
        --fd-bg: #f8fafc;
        --fd-radius: 24px;
        --fd-shadow: 0 10px 40px rgba(0,0,0,0.04);
    }

    .fd-checkout-page { background: var(--fd-bg); padding: 60px 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
    
    .bento-card { 
        background: #fff; border-radius: var(--fd-radius); border: 1px solid var(--fd-border); padding: 35px; 
        margin-bottom: 25px; box-shadow: var(--fd-shadow); transition: transform 0.3s ease;
    }
    .bento-card:hover { border-color: #cbd5e1; }

    .section-title { margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
    .section-title h4 { margin: 0; font-weight: 850; font-size: 1.4rem; letter-spacing: -0.5px; }
    .icon-box { background: var(--fd-red); color: #fff; width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; }

    .fd-label { font-weight: 800; font-size: 11px; color: var(--fd-muted); text-transform: uppercase; margin-bottom: 10px; display: block; letter-spacing: 1px; }
    .fd-input { 
        border-radius: 14px; padding: 16px 20px; border: 2px solid var(--fd-border); width: 100%; 
        transition: 0.3s; background: #fff; font-weight: 600; font-size: 15px; color: var(--fd-text);
    }
    .fd-input:focus { border-color: var(--fd-red); outline: none; box-shadow: 0 0 0 5px var(--fd-red-light); }

    .fulfillment-toggle { display: flex; background: #f1f5f9; padding: 6px; border-radius: 18px; margin-bottom: 30px; }
    .fulfillment-toggle input { display: none; }
    .fulfillment-toggle label { 
        flex: 1; text-align: center; padding: 14px; border-radius: 14px; cursor: pointer; 
        font-weight: 800; transition: 0.3s; margin: 0; color: var(--fd-muted);
    }
    .fulfillment-toggle input:checked + label { background: var(--fd-red); color: #fff; box-shadow: 0 10px 20px -5px rgba(214, 54, 56, 0.4); }

    .summary-item { display: flex; justify-content: space-between; align-items: center; padding: 18px 0; border-bottom: 1px solid #f1f5f9; }
    .qty-circle { background: var(--fd-red-light); color: var(--fd-red); font-weight: 900; width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; margin-right: 15px; }
    
    .calc-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: 650; color: var(--fd-muted); font-size: 15px; }
    .final-total-card { color: #0f172a; display: flex; justify-content: space-between; align-items: center; margin-top: 25px; margin-bottom: 30px; }
    .final-total-card .val { font-size: 2rem; font-weight: 900;color: #d63638}

/* Hide the default radio circle */
.fd-input input[type="radio"] {
    appearance: none;
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 50%;
    margin: 0;
    position: relative;
    background: #fff;
    transition: all 0.2s ease;
    flex-shrink: 0;
    min-height: 10px;
}

/* The dot inside the radio when checked */
.fd-input input[type="radio"]::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Checked State - The Magic */
.fd-input:has(input[type="radio"]:checked) {
    border-color: #ef4444; /* Your brand red */
    background: rgba(239, 68, 68, 0.02); /* Very subtle tint */
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.08), 0 0 0 1px rgba(239, 68, 68, 0.1);
}

.fd-input input[type="radio"]:checked {
    background: #ef4444;
    border-color: #ef4444;
}

.fd-input input[type="radio"]:checked::after {
    transform: translate(-50%, -50%) scale(1);
}

/* Hover Effects */
.fd-input:hover:not(:has(input:checked)) {
    border-color: #cbd5e1;
    background: #f8fafc;
    transform: translateY(-1px);
}


    .btn-place-order { 
        background: var(--fd-red); color: #fff; border: none; width: 100%; padding: 25px; border-radius: 20px; 
        font-weight: 900; font-size: 1.4rem; cursor: pointer; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        box-shadow: 0 15px 30px rgba(214, 54, 56, 0.3); margin-top: 25px;
    }
    .btn-place-order:hover { transform: translateY(-5px); filter: brightness(1.1); }
</style>

<div class="fd-checkout-page">
    <div class="container">
        <form id="mainCheckoutUI">
            <div class="row">
                <div class="col-lg-7">
                    <div class="bento-card">
                        <div class="section-title">
                            <h4>Order Method</h4>
                        </div>
                        
                        <div class="fulfillment-toggle">
                            <input type="radio" name="orderType" id="typeDelivery" value="delivery" checked>
                            <label for="typeDelivery">Delivery</label>
                            <input type="radio" name="orderType" id="typePickup" value="collection">
                            <label for="typePickup">Collection</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6"><label class="fd-label">Full Name</label><input type="text" id="fullName" class="fd-input" value="<?php echo esc_attr($u->display_name); ?>" required></div>
                            <div class="col-md-6">
    <label class="fd-label">Phone</label>
    <input 
        type="tel" 
        id="phone" 
        name="afon_phone"
        class="fd-input" 
        value="<?php echo esc_attr($user_phone); ?>" 
        placeholder="07123 456789"
        required
        pattern="^(\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$"
        title="Please enter a valid UK mobile number starting with 07 or +447">
</div>
                            <div class="col-12"><label class="fd-label">Email Address</label><input type="email" id="email" class="fd-input" value="<?php echo esc_attr($u->user_email); ?>" required></div>
                        </div>

                        <div id="deliverySection" class="mt-5">
                            <h5 style="font-weight:850; font-size:1.1rem; margin-bottom:20px;">Delivery Address</h5>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="fd-label">Flat No</label><input type="text" id="flat_no" class="fd-input" value="<?php echo esc_attr($user_flat); ?>" required></div>
                                <div class="col-md-8"><label class="fd-label">Building Name</label><input type="text" id="building" class="fd-input" value="<?php echo esc_attr($user_building); ?>" required></div>
                                <div class="col-md-4"><label class="fd-label">Door No</label><input type="text" id="door_no" class="fd-input" value="<?php echo esc_attr($user_door); ?>" required></div>
                                <div class="col-md-8"><label class="fd-label">Road / Street</label><input type="text" id="road_name" class="fd-input" value="<?php echo esc_attr($user_road); ?>" required></div>
                                <div class="col-12"><label class="fd-label">Postcode</label><input type="text" id="postcode" class="fd-input" value="<?php echo esc_attr($user_postcode); ?>" style="text-transform:uppercase" required></div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="fd-label">Delivery Notes / Instructions</label>
                            <textarea id="delivery_notes" class="fd-input" rows="2" placeholder="Ex: Gate code is 1234..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="sticky-top" style="top:30px">
                        <div class="bento-card">
                            <div class="section-title">
                                <h4>Order Review</h4>
                            </div>
                            
                            <div id="uiItemList" class="mb-4"></div>

                            <div class="pricing-rows">
                                <div class="calc-row"><span>Subtotal</span><span><?php echo $currency; ?><span id="valSub">0.00</span></span></div>
                                <div class="calc-row" style="color:#10b981"><span id="promoLabel">Discount</span><span>-<?php echo $currency; ?><span id="valDisc">0.00</span></span></div>
                                <div class="calc-row"><span>Service Fee</span><span><?php echo $currency . $service_fee; ?></span></div>
                                <div class="calc-row"><span id="feeLabel">Delivery Fee</span><span><?php echo $currency; ?><span id="valFee">0.00</span></span></div>
                                <div class="calc-row"><span>Bag Fee</span><span><?php echo $currency . $bag_fee; ?></span></div>
                                <div class="calc-row align-items-center mt-2">
                                    <span>Driver Tip</span>
                                    <div class="d-flex align-items-center gap-1">
                                        <span style="font-weight:800; font-size:12px"><?php echo $currency; ?></span>
                                        <input type="number" id="tipInput" class="fd-input p-2 text-end" style="width:80px; height:35px" value="0.00" step="0.50">
                                    </div>
                                </div>

                                <div class="final-total-card">
                                    <span style="font-weight:700; font-size:18px;font-weight: 800">TOTAL</span>
                                    <span class="val"><?php echo $currency; ?><span id="valGrand">0.00</span></span>
                                </div>
                            </div>

                            

                    <div class="bento-card">
                        <label class="fd-input d-flex align-items-center gap-3 mb-3" style="cursor:pointer">
                            <input type="radio" name="payment" value="cash" checked>
                            <div class="d-flex flex-column">
                                <span style="font-weight:800">Cash on Arrival</span>
                                <small style="color:var(--fd-muted)">Pay at your doorstep</small>
                            </div>
                        </label>
                        <label class="fd-input d-flex align-items-center gap-3" style="cursor:pointer">
                            <input type="radio" name="payment" value="card">
                            <div class="d-flex flex-column">
                                <span style="font-weight:800">Card Machine</span>
                                <small style="color:var(--fd-muted)">Driver brings card terminal</small>
                            </div>
                        </label>
                    </div>

                            <button type="submit" id="mainSubmitBtn" class="btn-place-order">PLACE ORDER NOW</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <form id="realSubmissionForm" method="POST" action="">
            <?php wp_nonce_field('fd_place_order_action', 'fd_nonce'); ?>
            <input type="hidden" name="fd_order_json" id="hidden_order_payload">
            <input type="hidden" name="fd_submit_hidden" value="1">
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($){
    
    const orderItems = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    const notesFromMenu = localStorage.getItem('fd_kitchen_notes') || '';
    const timeChoice = localStorage.getItem('fd_scheduled_time') || 'asap';

    const config = {
        dFee: parseFloat("<?php echo $delivery_charge; ?>"),
        cFee: parseFloat("<?php echo $collection_charge; ?>"),
        sFee: parseFloat("<?php echo $service_fee; ?>"),
        bFee: parseFloat("<?php echo $bag_fee; ?>"),
        dDisc: parseFloat("<?php echo $delivery_disc; ?>"),
        cDisc: parseFloat("<?php echo $collection_disc; ?>"),
        curr: "<?php echo $currency; ?>"
    };

    function updatePricing() {
        const isColl = $('#typePickup').is(':checked');
        const tipVal = parseFloat($('#tipInput').val()) || 0;
        
        $('#deliverySection').toggle(!isColl);
        $('#feeLabel').text(isColl ? 'Collection Fee' : 'Delivery Fee');
        
        let subtotal = 0;
        const $list = $('#uiItemList').empty();
        
        if(orderItems.length === 0) {
            $list.html('<p class="text-center py-4 text-muted">Your cart is empty.</p>');
            $('#mainSubmitBtn').prop('disabled', true);
            return;
        }

        orderItems.forEach(item => {
            const basePrice = parseFloat(item.price) || 0;
            const variationPrice = parseFloat(item.vPrice) || 0;
            const line = (basePrice + variationPrice) * item.qty;
            
            subtotal += line;
            $list.append(`
                <div class="summary-item">
                    <div class="d-flex align-items-center">
                        <span class="qty-circle">${item.qty}</span>
                        <div>
                            <span style="font-weight:800; display:block">${item.name}</span>
                            ${item.vName ? `<small style="color:var(--fd-red); font-weight:700; font-size:12px; display:block; margin-top:2px;">${item.vName}</small>` : ''}
                        </div>
                    </div>
                    <span style="font-weight:800">${config.curr}${line.toFixed(2)}</span>
                </div>
            `);
        });

        const discRate = isColl ? config.cDisc : config.dDisc;
        const activeFee = isColl ? config.cFee : config.dFee;
        const discAmt = (subtotal * discRate) / 100;
        const finalTotal = (subtotal - discAmt) + config.sFee + activeFee + config.bFee + tipVal;

        $('#valSub').text(subtotal.toFixed(2));
        $('#valDisc').text(discAmt.toFixed(2));
        $('#valFee').text(activeFee.toFixed(2));
        $('#valGrand').text(finalTotal.toFixed(2));
        $('#promoLabel').text(`Discount (${discRate}%)`);
    }

    $('#mainCheckoutUI').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#mainSubmitBtn');
        const isColl = $('#typePickup').is(':checked');
        const currentSubtotal = parseFloat($('#valSub').text());
        const currentDiscAmt = parseFloat($('#valDisc').text());

        $btn.prop('disabled', true).text('PROCESSING...');

        // Map UI IDs to Payload Keys
        // NEW FIELDS ADDED: delivery_fee, collection_charge, delivery_discount, collection_discount
        const payload = {
            orderType: isColl ? 'collection' : 'delivery',
            paymentMethod: $('input[name="payment"]:checked').val(),
            fullName: $('#fullName').val().trim(),
            phone: $('#phone').val().trim(),
            email: $('#email').val().trim(),
            flat_no: $('#flat_no').val().trim(),
            building: $('#building').val().trim(),
            door_no: $('#door_no').val().trim(),
            road_name: $('#road_name').val().trim(),
            postcode: $('#postcode').val().trim(),
            delivery_notes: $('#delivery_notes').val().trim(),
            kitchen_notes: notesFromMenu,
            scheduledTime: timeChoice,
            cart: orderItems,
            
            subtotal: currentSubtotal,
            service_fee: config.sFee,
            bag_fee: config.bFee,
            tip: $('#tipInput').val() || 0,
            
            // LOGIC FOR NEW DATABASE COLUMNS
            delivery_charge: isColl ? 0 : config.dFee,
            collection_charge: isColl ? config.cFee : 0,
            delivery_discount: isColl ? 0 : currentDiscAmt,
            collection_discount: isColl ? currentDiscAmt : 0,
            
            total: $('#valGrand').text() 
        };

        $('#hidden_order_payload').val(JSON.stringify(payload));
        
        // Clear local storage ONLY after payload is generated
        localStorage.removeItem('fd_cart_save');
        localStorage.removeItem('fd_kitchen_notes');

        setTimeout(() => { $('#realSubmissionForm').submit(); }, 300);
    });

    $('input[name="orderType"]').on('change', updatePricing);
    $('#tipInput').on('input', updatePricing);
    
    updatePricing();
});
</script>

<?php get_header(); ?>