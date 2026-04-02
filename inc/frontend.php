<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SHORTCODE: Main Food Ordering System (Menu + Cart)
 * Features: Variant Modal, Quantity Validation, Dynamic Charges.
 */
function fd_food_items_shortcode() {
    wp_enqueue_style('dashicons');

    // 1. SETTINGS & LIVE STATUS
    $store_status = function_exists('get_afd_restaurant_status') ? get_afd_restaurant_status() : ['status' => 'open', 'is_open' => true];
    $is_actually_open = ($store_status['status'] === 'open' || $store_status['status'] === 'warning');

$is_logged_in = is_user_logged_in() ? 'true' : 'false';
$custom_login_url = home_url('/login/'); 
$redirect_after_login = add_query_arg('redirect_to', home_url('/checkout/'), $custom_login_url);
    
    $currency = '£';
    
    $minimum_order       = get_option('afd_minimum_order', '0.00');
    $delivery_charge       = get_option('afd_delivery_charge', '0.00');
    $collection_fee     = get_option('afd_pickup_charge', '0.00'); 
    $service_fee        = get_option('afd_service_charge', '0.00');
    $bag_fee            = get_option('afd_bag_charge', '0.00');
    $delivery_discount   = get_option('afd_delivery_discount_percent', '0'); 
    $collection_discount = get_option('afd_pickup_discount_percent', '0');

    // DYNAMIC TIME CALCULATION (30 Mins Intervals + Tomorrow Support)
    $now = current_datetime();
    $current_day = $now->format('D');
    $current_ts = $now->getTimestamp();
    
    // Get today's and tomorrow's schedule
    $schedule = get_option('afd_schedule', []);
    $today_sched = !empty($schedule[$current_day]) ? $schedule[$current_day] : null;
    
    $target_day = $current_day;
    $target_sched = $today_sched;

    // Logic: If today is disabled OR current time > closing time, look at tomorrow
    if ($target_sched) {
        $closing_time = strtotime($target_sched['close']);
        if (!$target_sched['enabled'] || $current_ts > $closing_time) {
            $tomorrow_dt = $now->modify('+1 day');
            $target_day = $tomorrow_dt->format('D');
            $target_sched = !empty($schedule[$target_day]) ? $schedule[$target_day] : null;
        }
    }

    $time_options = [];
    if ($target_sched && !empty($target_sched['enabled'])) {
        $start_ts = strtotime($target_sched['open']);
        $end_ts   = strtotime($target_sched['close']);
        
        // Handle overnight shifts (e.g., 6PM to 2AM)
        if ($end_ts <= $start_ts) { $end_ts += 86400; }

        // Start from opening time OR current time (if today), whichever is later
        $loop_start = ($target_day === $current_day) ? max($start_ts, $current_ts) : $start_ts;
        
        // Round to next 30 min interval
        $loop_start = ceil($loop_start / 1800) * 1800;

        for ($t = $loop_start; $t <= $end_ts; $t += 1800) { // 1800s = 30 mins
            $label = ($target_day !== $current_day) ? 'Tomorrow ' : '';
            $time_options[date('H:i', $t)] = date('h:i A', $t);
        }
    }

    $items = get_posts([
        'post_type'      => 'food_item',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC'
    ]);

    if (!$items) return '<p>No food items found.</p>';

    $items_by_cat = [];
    foreach ($items as $item) {
        $terms = wp_get_post_terms($item->ID, 'food_category');
        $cat_name = !empty($terms) ? $terms[0]->name : 'Other';
        $cat_slug = !empty($terms) ? $terms[0]->slug : 'other';
        $cat_id   = !empty($terms) ? $terms[0]->term_id : 0;

        if (!isset($items_by_cat[$cat_slug])) {
            $img_id = get_term_meta($cat_id, 'fd_category_image', true);
            $image_url = $img_id ? wp_get_attachment_image_url($img_id, 'medium') : 'https://ui-avatars.com/api/?name=' . urlencode($cat_name) . '&background=fef2f2&color=d63638&bold=true';
            $items_by_cat[$cat_slug] = [
                'name'  => $cat_name,
                'img'   => $image_url,
                'items' => []
            ];
        }
        $items_by_cat[$cat_slug]['items'][] = $item;
    }

    ob_start(); ?>

<style>
    /* MODAL STYLES */
    .fd-variant-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .fd-variant-modal.active { display: flex; }
    .fd-vmodal-content { background: #fff; width: 90%; max-width: 420px; border-radius: 24px; overflow: hidden; animation: fd-slide-up 0.3s ease-out; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
    .fd-vmodal-header { padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fff; }
    .fd-vmodal-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
    #close-vmodal { cursor: pointer; color: #64748b; font-size: 24px; }
    .fd-vmodal-body { padding: 20px; max-height: 50vh; overflow-y: auto; background: #fff; }
    .fd-vmodal-footer { padding: 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; }
    .fd-v-row { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 2px solid #e2e8f0; border-radius: 16px; margin-bottom: 12px; cursor: pointer; transition: all 0.2s ease; }
    .fd-v-row:hover { border-color: #d63638; background: #fff5f5; }
    .fd-v-row.selected { border-color: #d63638; background: #fff5f5; }
    .fd-v-name { font-weight: 700; color: #1e293b; display: block; }
    .fd-v-price { font-size: 13px; color: #d63638; font-weight: 700; }
    .v-check { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; }
    .fd-v-row.selected .v-check { background: #d63638; border-color: #d63638; }
    .fd-v-row.selected .v-check:after { content: ''; width: 6px; height: 6px; background: #fff; border-radius: 50%; }
    
    /* ADD TO CART OVERRIDE */
    .fd-confirm-add-btn { width: 100%; padding: 16px; background: #d63638; color: #fff; border: none; border-radius: 14px; font-weight: 800; font-size: 16px; cursor: pointer; display: flex; justify-content: space-between; }
    
    @keyframes fd-slide-up { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    /* STICKY LAYOUT ADAPTATION */
.fd-container {
    display: flex;
    align-items: flex-start; /* Required for sticky to work */
    gap: 20px;
    position: relative;
}

/* 1. STICKY CATEGORY GRID */
.fd-category-grid {
    position: sticky;
    top: 80px; /* Adjust based on your header height */
    height: fit-content;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    z-index: 10;
}

/* 2. STICKY CART SIDEBAR */
.fd-cart-sidebar {
    position: sticky;
    top: 80px; /* Adjust based on your header height */
    height: fit-content;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    display: block; /* Ensure it's visible for desktop sticky */
}

/* 3. MENU SECTION (The scrollable area) */
.fd-menu-section {
    flex: 1;
}

/* MOBILE FIX: Disable sticky on small screens where they become drawers */
@media (max-width: 991px) {
    .fd-category-grid, .fd-cart-sidebar {
        position: static;
        height: auto;
        max-height: none;
    }
    
    .fd-cart-sidebar {
        position: fixed; /* Keeps your existing drawer logic */
        top: 0;
        right: -100%;
        height: 100%;
        z-index: 999999;
        transition: 0.3s;
    }
    
    .fd-cart-sidebar.active {
        right: 0;
    }
}

/* Custom Scrollbar for sticky areas */
.fd-category-grid::-webkit-scrollbar, 
.fd-cart-sidebar::-webkit-scrollbar {
    width: 4px;
}
.fd-category-grid::-webkit-scrollbar-thumb, 
.fd-cart-sidebar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
/* STICKY MOBILE CATEGORY HEADER */
.fd-mobile-cat-header {
    position: sticky;
    top: 0; /* Matches WP Admin Bar height on mobile */
    z-index: 30;
    background: #ffffff;
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Adjust top position if user is NOT logged in (No Admin Bar) */
body:not(.admin-bar) .fd-mobile-cat-header {
    top: 0;
}

/* Ensure the main container doesn't overlap on mobile */
@media (max-width: 991px) {
    .fd-container {
        padding-top: 10px;
    }
    
    /* Ensure category grid doesn't interfere with mobile sticky header */
    .fd-category-grid {
        display: none; /* Usually hidden on mobile in favor of the drawer */
    }
}

/* Highlight for the sticky sidebar category */
.fd-cat-grid-item.active-cat {
    border-color: #d63638 !important;
    background: #fff5f5 !important;
}
.fd-cat-grid-item.active-cat span {
    color: #d63638 !important;
    font-weight: 800 !important;
}
</style>

<div class="fd-main-wrapper">
    <div class="fd-mobile-cat-header">
        <div class="fd-cat-trigger" id="fd-open-cats">
            <span class="dashicons dashicons-menu"></span>
            <span>Menu Categories</span>
        </div>

<div id="fd-current-cat-name" style="font-size: 12px; font-weight: 700; color: #d63638;">Select a section</div>
    </div>

    <div class="fd-container">
        <div class="fd-category-grid">
            <div class="fd-search-container">
                <span class="fd-search-icon">🔍</span>
                <input type="text" id="fd-menu-search" class="fd-menu-search" placeholder="Search for your favorite food...">
            </div>
            <?php foreach ($items_by_cat as $slug => $cat) : ?>
                <a href="#cat-<?php echo esc_attr($slug); ?>" class="fd-cat-grid-item">
                    <div class="fd-cat-grid-thumb"><img src="<?php echo esc_url($cat['img']); ?>" alt="Category"></div>
                    <span><?php echo esc_html($cat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="fd-menu-section">
            <?php foreach ($items_by_cat as $slug => $cat_data) : ?>
                <div id="cat-<?php echo esc_attr($slug); ?>" class="food-menu">
                    <h4 class="sub-heading"><?php echo esc_html($cat_data['name']); ?></h4>
                    <ul class="meal-items">
                        <?php foreach ($cat_data['items'] as $item) : 
                            $price = get_post_meta($item->ID, 'price', true) ?: '0.00';
                            $img = get_the_post_thumbnail_url($item->ID, 'medium') ?: 'https://via.placeholder.com/130';
                            $variants = get_post_meta($item->ID, 'fd_item_extras_repeater', true);
                            $has_variants = !empty($variants) ? 'true' : 'false';
                        ?>
                            <li class="fd-food-card" 
                                data-id="<?php echo $item->ID; ?>"
                                data-title="<?php echo esc_attr(strtolower($item->post_title)); ?>" 
                                data-base-price="<?php echo esc_attr($price); ?>"
                                data-has-variants="<?php echo $has_variants; ?>">
                                
                                <div class="thumbnail"><img src="<?php echo esc_url($img); ?>" alt="Food"></div>
                                <div class="content">
                                    <div class="top">
                                        <div class="title"><h4><?php echo esc_html($item->post_title); ?></h4></div>
                                        <div class="price"><span><?php echo $currency . number_format((float)$price, 2); ?></span></div>
                                    </div>
                                    <div class="bottom"><p><?php echo wp_kses_post($item->post_content); ?></p></div>

                                    <?php if (!empty($variants)) : ?>
                                        <div class="fd-hidden-variants" style="display:none;">
                                            <?php foreach ($variants as $v) : ?>
                                                <div class="v-data" data-vname="<?php echo esc_attr($v['name']); ?>" data-vprice="<?php echo esc_attr($v['price']); ?>"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="order-btn-align">
                                        <div class="fd-qty-selector">
                                            <button class="fd-item-minus"><span class="dashicons dashicons-minus"></span></button>
                                            <span class="fd-item-qty">0</span> 
                                            <button class="fd-item-plus"><span class="dashicons dashicons-plus"></span></button>
                                        </div>
                                        <button class="order-btn" 
                                                data-name="<?php echo esc_attr($item->post_title); ?>" 
                                                data-price="<?php echo esc_attr($price); ?>">
                                            Add +
                                        </button>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="fd-cart-sidebar" id="fd-cart-sidebar">
            <div class="fd-mobile-cart-header">
                <span class="fd-close-cart dashicons dashicons-arrow-left-alt2"></span>
                <span style="font-weight:800; font-size:18px;">Review Order</span>
            </div>
            <div class="fd-sticky-panel">
                <?php if (!$is_actually_open) : ?>
                    <div class="preorder-badge" style="background:#fffbeb; border:1px solid #fef3c7; color:#92400e; padding:12px; border-radius:12px; font-size:13px; font-weight:700; margin-bottom:20px; text-align:center;">
                        <strong>We are currently closed.</strong><br>Accepting Pre-Orders.
                    </div>
                <?php endif; ?>

                <div class="fd-order-type">
                    <input type="radio" name="order_type" id="delivery" value="delivery" checked>
                    <label for="delivery" class="fd-type-label">Delivery</label>
                    <input type="radio" name="order_type" id="pickup" value="collection">
                    <label for="pickup" class="fd-type-label">Collection</label>
                </div>

                <div class="fd-schedule-wrap" style="margin-bottom:20px;">
                    <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:#666; margin-bottom:5px;">Select Time</label>
                    <select id="fd-scheduled-time" class="fd-schedule-select" style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd; font-weight:600; background:#fff;">
                        <?php if ($is_actually_open) : ?>
                            <option value="asap">ASAP (Fastest)</option>
                        <?php else : ?>
                            <option value="" disabled selected>Select Pre-Order Time</option>
                        <?php endif; ?>
                        <?php foreach($time_options as $val => $lbl) : ?>
                            <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h4 style="margin:0 0 15px 0; font-weight:800; font-size:20px;">Your Order</h4>
                <div id="fd-cart-list"></div>

                <div class="fd-summary-container">
                    <div class="fd-summary-row"><span>Subtotal</span><span><?php echo $currency; ?><span id="br-subtotal">0.00</span></span></div>
                    <div class="fd-summary-row"><span id="lbl-discount-text">Discount</span><span>-<?php echo $currency; ?><span id="br-discount-value">0.00</span></span></div>
                    <div class="fd-summary-row" style="font-weight:700; color:#1a1a1a; border-top: 1px dashed #ddd; padding-top: 10px; margin-top: 5px;"><span>Order Total</span><span><?php echo $currency; ?><span id="br-order-total">0.00</span></span></div>
                    <div class="fd-summary-row"><span>Service Charge</span><span><?php echo $currency . number_format((float)$service_fee, 2); ?></span></div>
                    <div class="fd-summary-row"><span><span id="lbl-fee-name">Delivery</span> Fee</span><span><?php echo $currency; ?><span id="lbl-fee-val">0.00</span></span></div>
                    <div class="fd-summary-row"><span>Bag Charge</span><span><?php echo $currency . number_format((float)$bag_fee, 2); ?></span></div>
                    <div class="fd-summary-row"><span>Driver Tip</span><span style="display:flex; align-items:center; gap:5px;"><?php echo $currency; ?><input type="number" id="fd-tip-amount" class="fd-tip-input" value="0.00" step="0.50" min="0" style="width:60px; text-align:right; border:1px solid #eee; border-radius:5px; padding:2px 5px;"></span></div>
                    <div class="fd-summary-row total-row" style="background: #fef2f2; padding: 15px; border-radius: 12px; margin-top: 15px;">
    <span style="font-weight:800;">Total Due</span>
    <span style="color:#d63638; font-size: 22px; font-weight: 900;">
        <?php echo $currency; ?><span id="fd-total-due">0.00</span>
    </span>
</div>

<div id="afd-min-order-msg" style="display:none; color: #d63638; background: #fff1f2; border: 1px solid #fecdd3; padding: 10px; border-radius: 8px; margin-top: 10px; font-weight: 800; text-align: center; font-size: 13px;">
    <span class="dashicons dashicons-warning" style="font-size:16px; vertical-align:middle;"></span>
    Minimum order amount is <?php echo $currency . $minimum_order; ?>
</div>
                    <textarea id="fd-kitchen-notes" class="fd-kitchen-notes" rows="2" placeholder="Any allergies or special requests?" style="width:100%; padding:10px; border-radius:10px; border:1px solid #ddd; margin-top:15px; font-size:13px;"></textarea>
                </div>
                <a href="#" class="fd-checkout-btn" id="fd-checkout-trigger" style="display:block; text-align:center; text-decoration:none;">
                    <?php echo $is_actually_open ? 'Confirm Order' : 'Place Pre-Order'; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="fd-variant-modal" id="fd-variant-modal">
    <div class="fd-vmodal-content">
        <div class="fd-vmodal-header">
            <h3>Customise Item</h3>
            <span class="dashicons dashicons-no-alt" id="close-vmodal"></span>
        </div>
        <div class="fd-vmodal-body">
            <p id="vmodal-item-info" style="font-size:13px; color:#64748b; margin-bottom:15px; font-weight:600;"></p>
            <div id="vmodal-options-list"></div>
        </div>
        <div class="fd-vmodal-footer">
            <button id="vmodal-confirm-btn" class="fd-confirm-add-btn">
                <span>Add to Order</span>
                <span id="vmodal-total-price">0.00</span>
            </button>
        </div>
    </div>
</div>

<div class="fd-cat-drawer" id="fd-cat-drawer">
    <div class="fd-cat-drawer-header">
        <span style="font-weight:800; font-size:18px;">Menu Sections</span>
        <span class="dashicons dashicons-no-alt" id="fd-close-cats" style="cursor:pointer; font-size:24px;"></span>
    </div>
    <div class="fd-cat-drawer-list">
        <?php foreach ($items_by_cat as $slug => $cat) : ?>
            <a href="#cat-<?php echo esc_attr($slug); ?>" class="fd-drawer-cat-link">
                <img src="<?php echo esc_url($cat['img']); ?>" alt="">
                <span><?php echo esc_html($cat['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="fd-overlay" id="fd-overlay"></div>

<div class="fd-bottom-cart-bar" id="fd-mobile-trigger">
    <div class="info"><span id="m-count">0</span> Items • <?php echo $currency; ?><span id="m-total">0.00</span></div>
    <div style="font-weight:800;">View Cart →</div>
</div>

<script>
jQuery(document).ready(function($){
    let cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    let pendingItem = null; 
    
    const config = {
        deliveryFee: parseFloat("<?php echo $delivery_charge; ?>") || 0,
        collectionFee: parseFloat("<?php echo $collection_fee; ?>") || 0,
        serviceFee: parseFloat("<?php echo $service_fee; ?>") || 0,
        bagFee: parseFloat("<?php echo $bag_fee; ?>") || 0,
        deliveryDiscount: parseFloat("<?php echo $delivery_discount; ?>") || 0,
        collectionDiscount: parseFloat("<?php echo $collection_discount; ?>") || 0,
        minOrder: parseFloat("<?php echo $minimum_order; ?>") || 0,
        currency: "<?php echo $currency; ?>"
    };

    // Restore Pre-Order Time on Load
    const savedTime = localStorage.getItem('fd_scheduled_time');
    if (savedTime) {
        $('#fd-scheduled-time').val(savedTime);
    }

    function updateCart() {
        const container = $('#fd-cart-list');
        container.empty();
        let subtotal = 0, count = 0;

        if(cart.length === 0) {
            container.html('<div style="color:#bbb; text-align:center; padding: 40px 0;">Your cart is empty</div>');
            $('#fd-checkout-trigger').css({'opacity': '0.5', 'pointer-events': 'none'});
            $('#fd-mobile-trigger').fadeOut();
        } else {
            $('#fd-checkout-trigger').css({'opacity': '1', 'pointer-events': 'auto'});
            $('#fd-mobile-trigger').css('display', 'flex').fadeIn();
        }

        cart.forEach((item, index) => {
            const itemUnitPrice = parseFloat(item.price) + (parseFloat(item.vPrice) || 0);
            const rowTotal = itemUnitPrice * item.qty;
            subtotal += rowTotal; 
            count += item.qty;

            container.append(`
                <div class="fd-cart-item" style="padding:12px 0; border-bottom:1px solid #eee;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div style="font-weight:700; font-size:15px;">${item.name}</div>
                            ${item.vName ? `<div style="font-size:11px; color:#d63638; font-weight:700;">${item.vName} (+${config.currency}${item.vPrice.toFixed(2)})</div>` : ''}
                        </div>
                        <button class="fd-delete" data-index="${index}" style="color:#ccc; background:none; border:none; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:10px;">
                        <div style="display:flex; align-items:center; gap:10px; background:#f5f5f5; padding:3px 10px; border-radius:8px;">
                            <button class="fd-minus" data-index="${index}" style="border:none; background:none; cursor:pointer; font-weight:bold;">-</button>
                            <span style="font-weight:800; font-size:14px;">${item.qty}</span>
                            <button class="fd-plus" data-index="${index}" style="border:none; background:none; cursor:pointer; font-weight:bold;">+</button>
                        </div>
                        <div style="font-weight:700; color:#1a1a1a;">${config.currency}${rowTotal.toFixed(2)}</div>
                    </div>
                </div>
            `);
        });

        let isDel = $('input[name="order_type"]:checked').val() === 'delivery';
        let activeFee = isDel ? config.deliveryFee : config.collectionFee;
        let activeDiscPercent = isDel ? config.deliveryDiscount : config.collectionDiscount;
        let tip = parseFloat($('#fd-tip-amount').val()) || 0;
        let discountVal = (subtotal * activeDiscPercent) / 100;
        let orderTotal = Math.max(0, subtotal - discountVal);
        
        $('#br-subtotal').text(subtotal.toFixed(2));
        $('#lbl-discount-text').text(isDel ? `Delivery Discount (${activeDiscPercent}%)` : `Collection Discount (${activeDiscPercent}%)`);
        $('#br-discount-value').text(discountVal.toFixed(2));
        $('#br-order-total').text(orderTotal.toFixed(2));
        $('#lbl-fee-name').text(isDel ? 'Delivery' : 'Collection');
        $('#lbl-fee-val').text(activeFee.toFixed(2));

        let totalDue = subtotal > 0 ? (orderTotal + config.serviceFee + activeFee + config.bagFee + tip) : 0;
        $('#fd-total-due, #m-total').text(totalDue.toFixed(2));
        // --- MINIMUM ORDER LOGIC (DELIVERY ONLY) ---
const checkoutBtn = $('#fd-checkout-trigger');
const minMsg = $('#afd-min-order-msg');
const minOrderVal = parseFloat("<?php echo $minimum_order; ?>") || 0;
const currentTotal = parseFloat($('#fd-total-due').text()) || 0;

// 'isDel' is already defined in your script as: 
// let isDel = $('input[name="order_type"]:checked').val() === 'delivery';

if (cart.length > 0 && isDel) {
    if (currentTotal < minOrderVal) {
        // LOCK CHECKOUT for Delivery
        checkoutBtn.css({'opacity': '0.5', 'pointer-events': 'none', 'filter': 'grayscale(1)'});
        minMsg.fadeIn(); 
    } else {
        // UNLOCK CHECKOUT
        checkoutBtn.css({'opacity': '1', 'pointer-events': 'auto', 'filter': 'grayscale(0)'});
        minMsg.fadeOut();
    }
} else {
    // ALWAYS UNLOCK for Collection or Empty Cart
    checkoutBtn.css({'opacity': '1', 'pointer-events': 'auto', 'filter': 'grayscale(0)'});
    minMsg.hide();
}
        $('#m-count').text(count);
        localStorage.setItem('fd_cart_save', JSON.stringify(cart));
    }

    // Modal Logic
    function calculateModalTotal() {
        if (!pendingItem) return;
        const vPrice = parseFloat($('.fd-v-row.selected').data('vprice')) || 0;
        const total = (pendingItem.price + vPrice) * pendingItem.qty;
        $('#vmodal-total-price').text(config.currency + total.toFixed(2));
    }

    function openVModal(itemData, variants) {
        pendingItem = itemData;
        $('#vmodal-item-info').text(`${itemData.name} (Quantity: ${itemData.qty})`);
        let list = $('#vmodal-options-list').empty();
        variants.forEach((v, idx) => {
            list.append(`
                <div class="fd-v-row" data-vname="${v.name}" data-vprice="${v.price}">
                    <div class="fd-v-info">
                        <span class="fd-v-name">${v.name}</span>
                        <span class="fd-v-price">+${config.currency}${parseFloat(v.price).toFixed(2)}</span>
                    </div>
                    <div class="v-check"></div>
                </div>
            `);
        });
        calculateModalTotal();
        $('#fd-variant-modal').addClass('active');
    }

    $(document).on('click', '.fd-v-row', function() {
        $('.fd-v-row').removeClass('selected');
        $(this).addClass('selected');
        calculateModalTotal();
    });

    $('#close-vmodal').on('click', function() { $('#fd-variant-modal').removeClass('active'); });

    // Main Interaction
    $(document).on('click', '.order-btn', function() {
        const $card = $(this).closest('.fd-food-card');
        const qty = parseInt($card.find('.fd-item-qty').text());
        if (qty <= 0) { alert("Please select a quantity first!"); return; }

        const itemData = {
            id: $card.data('id'),
            name: $(this).data('name'),
            price: parseFloat($card.data('base-price')),
            qty: qty
        };

        if ($card.data('has-variants')) {
            let variants = [];
            $card.find('.fd-hidden-variants .v-data').each(function() {
                variants.push({ name: $(this).data('vname'), price: $(this).data('vprice') });
            });
            openVModal(itemData, variants);
        } else {
            addToCart(itemData, '', 0);
            resetCard($card);
        }
    });

    $('#vmodal-confirm-btn').on('click', function() {
        const $selected = $('.fd-v-row.selected');
        if (!$selected.length) return;
        addToCart(pendingItem, $selected.data('vname'), parseFloat($selected.data('vprice')));
        $('#fd-variant-modal').removeClass('active');
        resetCard($(`.fd-food-card[data-id="${pendingItem.id}"]`));
    });

    function addToCart(item, vName, vPrice) {
        const exist = cart.find(i => i.name === item.name && i.vName === vName);
        if (exist) { exist.qty += item.qty; } 
        else { cart.push({ name: item.name, price: item.price, qty: item.qty, vName, vPrice }); }
        updateCart();
    }

    function resetCard($card) {
        const $btn = $card.find('.order-btn');
        const oldHtml = $btn.html();
        $btn.html('Added!').prop('disabled', true).css('background', '#10b981');
        setTimeout(() => {
            $card.find('.fd-item-qty').text(0);
            $btn.html(oldHtml).prop('disabled', false).css('background', '');
        }, 800);
    }

    // Helper Events
    $(document).on('click', '.fd-item-plus', function() { let s = $(this).siblings('.fd-item-qty'); s.text(parseInt(s.text()) + 1); });
    $(document).on('click', '.fd-item-minus', function() { let s = $(this).siblings('.fd-item-qty'); let val = parseInt(s.text()); if (val > 0) s.text(val - 1); });
    
    // Sidebar Controls
    $(document).on('click', '.fd-plus', function() { cart[$(this).data('index')].qty += 1; updateCart(); });
    $(document).on('click', '.fd-minus', function() { 
        const idx = $(this).data('index'); 
        if (cart[idx].qty > 1) { cart[idx].qty -= 1; } else { cart.splice(idx, 1); } 
        updateCart(); 
    });
    $(document).on('click', '.fd-delete', function() { cart.splice($(this).data('index'), 1); updateCart(); });

    // Drawer Toggles
    $('#fd-open-cats').on('click', function() { $('#fd-cat-drawer, #fd-overlay').addClass('active'); });
    $('#fd-mobile-trigger').on('click', function() { $('#fd-cart-sidebar, #fd-overlay').addClass('active'); });
    $('#fd-close-cats, .fd-close-cart, #fd-overlay').on('click', function() { $('.fd-cat-drawer, .fd-cart-sidebar, #fd-overlay').removeClass('active'); });

    // Search
    $('#fd-menu-search').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.fd-food-card').each(function() { $(this).toggle($(this).data('title').indexOf(val) > -1); });
    });

    // Time Save
    $('input[name="order_type"], #fd-tip-amount, #fd-scheduled-time').on('change input', function() {
        if($(this).attr('id') === 'fd-scheduled-time') {
            localStorage.setItem('fd_scheduled_time', $(this).val());
        }
        updateCart();
    });

    $(document).on('click', '#fd-checkout-trigger', function(e) {
        e.preventDefault();
        if (cart.length === 0) return;
        localStorage.setItem('fd_scheduled_time', $('#fd-scheduled-time').val());
        localStorage.setItem('fd_kitchen_notes', $('#fd-kitchen-notes').val());
        localStorage.setItem('fd_order_type', $('input[name="order_type"]:checked').val());
        localStorage.setItem('fd_tip_amount', $('#fd-tip-amount').val());
        const isLoggedIn = <?php echo $is_logged_in; ?>;
        if (isLoggedIn) { window.location.href = "<?php echo home_url('/checkout/'); ?>"; } 
        else { window.location.href = "<?php echo $redirect_after_login; ?>"; }
    });

    // Auto Category Selector & Mobile Header Text Update
    const observerOptions = { root: null, rootMargin: '-20% 0px -70% 0px', threshold: 0 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                const activeLink = $(`.fd-cat-grid-item[href="#${id}"]`);
                const catName = activeLink.find('span').text(); // Get name from sidebar link

                // 1. Highlight Sidebar
                $('.fd-cat-grid-item').removeClass('active-cat'); 
                activeLink.addClass('active-cat');
                
                // 2. Update Mobile Header Text
                if (catName) {
                    $('#fd-current-cat-name').text(catName);
                }

                // 3. Auto-scroll sidebar if overflowed
                if (activeLink.length > 0) {
                    activeLink[0].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
                }
            }
        });
    }, observerOptions);

    document.querySelectorAll('.food-menu').forEach((section) => {
        observer.observe(section);
    });

    updateCart();
});
</script>

<?php return ob_get_clean(); }
add_shortcode('fd_food_items','fd_food_items_shortcode');