<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ADVANCED FOOD MENU SHORTCODE
 * Features: Search, Grid Categories, Set Meal Items, Pickup/Delivery Toggle
 */
function fd_food_items_shortcode() {

    // 1. RESTAURANT STATUS & SETTINGS
    $store_status = function_exists('get_afd_restaurant_status') ? get_afd_restaurant_status() : ['is_open' => true];
    $is_open      = $store_status['is_open'];
    $open_time    = get_option('afd_open_time', '09:00');
    $close_time   = get_option('afd_close_time', '22:00');
    $currency     = '£';
    $delivery_fee = 3.50; // Set your delivery fee here

    // 2. FETCH AND GROUP DATA
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

    $is_logged_in = is_user_logged_in() ? 'true' : 'false';
    $login_url = site_url('/login/');

    ob_start(); ?>

<style>
    .fd-main-wrapper { max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: 'Inter', sans-serif; background: #fcfcfc; }

    /* --- SEARCH BAR --- */
    .fd-search-container { margin-bottom: 40px; position: relative; }
    .fd-menu-search { width: 100%; padding: 18px 25px 18px 55px; border-radius: 15px; border: 1px solid #ddd; font-size: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); outline: none; transition: 0.3s; }
    .fd-menu-search:focus { border-color: #d63638; box-shadow: 0 5px 20px rgba(214, 54, 56, 0.15); }
    .fd-search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 20px; }

    /* --- CATEGORY GRID --- */
    .fd-category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 25px; margin-bottom: 60px; }
    .fd-cat-grid-item { text-decoration: none !important; text-align: center; transition: 0.3s ease; display: block; }
    .fd-cat-grid-item:hover { transform: translateY(-5px); }
    .fd-cat-grid-thumb { width: 100px; height: 100px; border-radius: 50%; background: #fff; margin: 0 auto 15px; overflow: hidden; border: 4px solid #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; }
    .fd-cat-grid-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .fd-cat-grid-item:hover .fd-cat-grid-thumb { border-color: #d63638; }
    .fd-cat-grid-item span { display: block; font-weight: 700; color: #1a1a1a; font-size: 15px; }

    /* --- LAYOUT --- */
    .fd-container { display: flex; flex-wrap: wrap; gap: 40px; }
    .fd-menu-section { flex: 1; min-width: 320px; }
    .fd-cart-sidebar { width: 380px; }

    /* --- FOOD ITEMS --- */
    .food-menu-style-two-content { margin-bottom: 60px; scroll-margin-top: 40px; }
    .sub-heading { font-size: 28px; font-weight: 800; margin-bottom: 30px; color: #1a1a1a; border-left: 6px solid #d63638; padding-left: 20px; }
    .meal-items { list-style: none; padding: 0; margin: 0; }
    .meal-items li { display: flex; align-items: flex-start; background: #fff; padding: 25px; border-radius: 20px; margin-bottom: 25px; border: 1px solid #f1f1f1; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .meal-items li:hover { border-color: #d63638; }
    .meal-items li .thumbnail { width: 130px; height: 130px; min-width: 130px; margin-right: 30px; overflow: hidden; border-radius: 15px; }
    .meal-items li .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
    .meal-items li .content .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .meal-items li .content .top .title h4 { margin: 0; font-size: 21px; font-weight: 700; color: #1a1a1a; }
    .meal-items li .content .top .price span { color: #d63638; font-weight: 800; font-size: 21px; }

    /* --- PICKUP/DELIVERY TOGGLE --- */
    .fd-order-type { display: flex; gap: 10px; margin-bottom: 25px; background: #f0f0f1; padding: 6px; border-radius: 12px; }
    .fd-type-label { flex: 1; text-align: center; cursor: pointer; padding: 10px; border-radius: 8px; font-weight: 700; transition: 0.3s; color: #666; }
    .fd-order-type input { display: none; }
    .fd-order-type input:checked + .fd-type-label { background: #d63638; color: #fff; }

    /* --- STICKY CART --- */
    .fd-sticky-panel { position: sticky; top: 30px; background: #fff; border-radius: 25px; padding: 35px; border: 1px solid #eee; box-shadow: 0 20px 50px rgba(0,0,0,0.08); }
    .order-btn { margin-top: 15px; padding: 12px 30px; background: #d63638; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
    .fd-cart-item { border-bottom: 1px solid #f8f8f8; padding: 15px 0; }
    .fd-checkout-btn { display: block; text-align: center; text-decoration: none !important; width:100%; background:#d63638; color:#fff !important; padding:20px; border-radius:15px; margin-top:30px; font-weight:800; }

    @media (max-width: 991px) {
        .fd-cart-sidebar { width: 100%; order: -1; }
        .fd-sticky-panel { position: static; margin-bottom: 40px; }
    }
</style>

<div class="fd-main-wrapper">
    
    <div class="fd-search-container">
        <span class="fd-search-icon">🔍</span>
        <input type="text" id="fd-menu-search" class="fd-menu-search" placeholder="Search for your favorite food...">
    </div>

    <div class="fd-category-grid">
        <?php foreach ($items_by_cat as $slug => $cat) : ?>
            <a href="#cat-<?php echo esc_attr($slug); ?>" class="fd-cat-grid-item">
                <div class="fd-cat-grid-thumb">
                    <img src="<?php echo esc_url($cat['img']); ?>" alt="<?php echo esc_html($cat['name']); ?>">
                </div>
                <span><?php echo esc_html($cat['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="fd-container">
        <div class="fd-menu-section">
            <?php foreach ($items_by_cat as $slug => $cat_data) : ?>
                <div id="cat-<?php echo esc_attr($slug); ?>" class="food-menu-style-two-content food-menu">
                    <h4 class="sub-heading"><?php echo esc_html($cat_data['name']); ?></h4>
                    <ul class="meal-items">
                        <?php foreach ($cat_data['items'] as $item) : 
                            $price = get_post_meta($item->ID, 'price', true) ?: '0.00';
                            $img = get_the_post_thumbnail_url($item->ID, 'medium') ?: get_template_directory_uri().'/assets/img/placeholder.jpg';
                        ?>
                            <li class="fd-food-card" data-title="<?php echo esc_attr(strtolower($item->post_title)); ?>">
                                <div class="thumbnail"><img src="<?php echo esc_url($img); ?>" alt="Food"></div>
                                <div class="content">
                                    <div class="top">
                                        <div class="title"><h4><?php echo esc_html($item->post_title); ?></h4></div>
                                        <div class="price"><span><?php echo $currency . number_format((float)$price, 2); ?></span></div>
                                    </div>
                                    <div class="bottom"><p><?php echo wp_kses_post($item->post_content); ?></p></div>
                                    <?php if($is_open): ?>
                                        <button class="order-btn" data-name="<?php echo esc_attr($item->post_title); ?>" data-price="<?php echo esc_attr($price); ?>">Add to Order</button>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="fd-cart-sidebar">
            <div class="fd-sticky-panel">
                <?php if ($is_open) : ?>
                    
                    <div class="fd-order-type">
                        <input type="radio" name="order_type" id="delivery" value="delivery" checked>
                        <label for="delivery" class="fd-type-label">Delivery</label>
                        
                        <input type="radio" name="order_type" id="pickup" value="pickup">
                        <label for="pickup" class="fd-type-label">Pickup</label>
                    </div>

                    <h4 style="margin:0 0 20px 0; font-weight:800; font-size:22px;">Your Cart</h4>
                    <div id="fd-cart-list"></div>

                    <div style="margin-top: 25px; padding-top: 15px; border-top: 2px dashed #eee;">
                        <div id="fd-fee-wrap" style="display:flex; justify-content:space-between; margin-bottom:10px; color:#666;">
                            <span>Delivery Fee</span>
                            <span><?php echo $currency . number_format($delivery_fee, 2); ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-weight:800; font-size: 1.5rem;">
                            <span>Total</span>
                            <span style="color:#d63638;"><?php echo $currency; ?><span id="fd-total">0.00</span></span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url( home_url('/checkout') ); ?>" class="fd-checkout-btn" id="fd-checkout-trigger">Confirm Order</a>
                <?php else : ?>
                    <div class="text-center py-4">
                        <h3 style="font-weight: 800; color:#d63638;">We are Closed</h3>
                        <p>Hours: <?php echo $open_time; ?> - <?php echo $close_time; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($){
    let cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    const deliveryFee = <?php echo $delivery_fee; ?>;
    const currency = "<?php echo $currency; ?>";
    const isLoggedIn = <?php echo $is_logged_in; ?>;

    // --- LIVE SEARCH LOGIC ---
    $('#fd-menu-search').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.fd-food-card').each(function() {
            let title = $(this).data('title');
            $(this).toggle(title.indexOf(val) > -1);
        });
        // Hide categories if all items inside are hidden
        $('.food-menu').each(function() {
            let hasVisible = $(this).find('.fd-food-card:visible').length;
            $(this).toggle(hasVisible > 0);
        });
    });

    // --- CART LOGIC ---
    function updateCart() {
        const container = $('#fd-cart-list');
        container.empty();
        let subtotal = 0;

        if(cart.length === 0) {
            container.html('<div style="color:#bbb; text-align:center; padding: 30px 0;">Empty Cart</div>');
            $('#fd-checkout-trigger').css({'opacity': '0.5', 'pointer-events': 'none'});
        } else {
            $('#fd-checkout-trigger').css({'opacity': '1', 'pointer-events': 'auto'});
        }

        cart.forEach((item, index) => {
            const rowTotal = item.price * item.qty;
            subtotal += rowTotal;
            container.append(`
                <div class="fd-cart-item">
                    <button class="fd-delete" data-index="${index}" style="color:#ff4d4d; background:none; border:none; float:right; cursor:pointer;">&times;</button>
                    <div style="font-weight:700;">${item.name}</div>
                    <div style="display:flex; align-items:center; gap:12px; margin-top:8px;">
                        <button class="fd-minus" data-index="${index}">-</button>
                        <span style="font-weight:bold;">${item.qty}</span>
                        <button class="fd-plus" data-index="${index}">+</button>
                        <span style="margin-left:auto; font-weight:700;">${currency}${rowTotal.toFixed(2)}</span>
                    </div>
                </div>
            `);
        });

        // Toggle Delivery Fee
        let isDelivery = $('input[name="order_type"]:checked').val() === 'delivery';
        if(isDelivery && cart.length > 0) {
            subtotal += deliveryFee;
            $('#fd-fee-wrap').show();
        } else {
            $('#fd-fee-wrap').hide();
        }

        $('#fd-total').text(subtotal.toFixed(2));
        localStorage.setItem('fd_cart_save', JSON.stringify(cart));
    }

    $('input[name="order_type"]').on('change', updateCart);

    $(document).on('click', '.order-btn', function() {
        const name = $(this).data('name'), price = parseFloat($(this).data('price'));
        const existing = cart.find(i => i.name === name);
        if(existing) existing.qty += 1; else cart.push({ name, price, qty: 1 });
        updateCart();
    });

    $(document).on('click', '.fd-plus', function() { cart[$(this).data('index')].qty += 1; updateCart(); });
    $(document).on('click', '.fd-minus', function() {
        const idx = $(this).data('index');
        if(cart[idx].qty > 1) cart[idx].qty -= 1; else cart.splice(idx, 1);
        updateCart();
    });
    $(document).on('click', '.fd-delete', function() { cart.splice($(this).data('index'), 1); updateCart(); });

    updateCart();
});
</script>

<?php
    return ob_get_clean();
}
add_shortcode('fd_food_items','fd_food_items_shortcode');



function food_best_sellers_shortcode() {
    ob_start(); ?>

    <div class="container pb-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="site-heading text-center">
                    <h4 class="sub-title">Awesome Food</h4>
                    <h2 class="title split-text">Popular Food of our Menus</h2>
                    
                    <div class="mt-4 mb-5">
                        <a href="<?php echo home_url('/menu'); ?>" class="btn-all-menus">
                            VIEW ALL MENUS
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6">
                <div id="cat-set-meals" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
                    <h4 class="sub-heading">Set Meals</h4>
                    <ul class="meal-items">
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/1.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Meal for 1 Person</h4></div><div class="price"><span>£16.50</span></div></div>
                                <div class="bottom"><p>Tandoori chicken, tikka masala, vegetable curry, rice & nan.</p></div>
                            </div>
                        </li>
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/2.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Thali for 2</h4></div><div class="price"><span>£24.50</span></div></div>
                                <div class="bottom"><p>Chicken tikka, bhaji, lamb bhuna, chicken curry & naan.</p></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div id="cat-our-specialties" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
                    <h4 class="sub-heading">Our Specialties</h4>
                    <ul class="meal-items">
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/3.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>King Prawn Masala</h4></div><div class="price"><span>£12.95</span></div></div>
                                <div class="bottom"><p>Fresh king prawns cooked in a spicy masala sauce with peppers.</p></div>
                            </div>
                        </li>
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/1.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Lamb Tikka Masala</h4></div><div class="price"><span>£10.95</span></div></div>
                                <div class="bottom"><p>Tender lamb chunks cooked in creamy tomato masala sauce.</p></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div id="cat-starters" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
                    <h4 class="sub-heading">Starters</h4>
                    <ul class="meal-items">
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/2.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Onion Bhaji</h4></div><div class="price"><span>£4.50</span></div></div>
                                <div class="bottom"><p>Spicy onions fried in a crisp batter - a classic favorite.</p></div>
                            </div>
                        </li>
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/3.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Chicken Chat</h4></div><div class="price"><span>£5.50</span></div></div>
                                <div class="bottom"><p>Pieces of chicken cooked in a medium spiced sour sauce.</p></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div id="cat-rice-bread" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
                    <h4 class="sub-heading">Rice & Breads</h4>
                    <ul class="meal-items">
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/1.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Pilau Rice</h4></div><div class="price"><span>£3.50</span></div></div>
                                <div class="bottom"><p>Fragrant basmati rice cooked with aromatic spices.</p></div>
                            </div>
                        </li>
                        <li>
                            <div class="thumbnail"><img src="<?php echo get_template_directory_uri(); ?>/assets/img/food/2.jpg" alt=""></div>
                            <div class="content">
                                <div class="top"><div class="title"><h4>Garlic Naan</h4></div><div class="price"><span>£3.20</span></div></div>
                                <div class="bottom"><p>Freshly baked leavened bread with a garlic topping.</p></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-all-menus {
            background: #d63638;
            color: #fff !important;
            padding: 12px 35px;
            border-radius: 5px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        .btn-all-menus:hover { background: #111; color: #fff !important; }
        .meal-items li { margin-bottom: 25px; list-style: none; display: flex; align-items: flex-start; }
        .meal-items .thumbnail { flex-shrink: 0; margin-right: 20px; }
        .meal-items .content { flex-grow: 1; }
        .meal-items .top { display: flex; justify-content: space-between; border-bottom: 1px dashed #ddd; margin-bottom: 5px; }
    </style>

    <?php
    return ob_get_clean();
}
add_shortcode('best_sellers_menu', 'food_best_sellers_shortcode');

function fd_dynamic_category_carousel_shortcode() {
    ob_start();
    
    // Get terms from your specific taxonomy 'food_category'
    $terms = get_terms([
        'taxonomy'   => 'food_category',
        'hide_empty' => false, // Set to true if you only want categories with items
    ]);

    if (empty($terms) || is_wp_error($terms)) return '';
    ?>

    <div class="food-cat-area default-padding bg-gray" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/shape/3.png);">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="site-heading text-center">
                        <h4 class="sub-title">Food Category</h4>
                        <h2 class="title split-text">Top category of our menus</h2>
                        
                        <div class="mt-4">
                            <a href="<?php echo home_url('/menu'); ?>" class="btn-all-menus">VIEW ALL MENUS</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="food-cat-carousel text-light swiper">
                        <div class="swiper-wrapper">
                            
                            <?php foreach ($terms as $term) : 
                                // Get the image ID from your custom meta 'fd_category_image'
                                $img_id = get_term_meta($term->term_id, 'fd_category_image', true);
                                $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : get_template_directory_uri() . '/assets/img/category/1.jpg';
                                ?>
                                <div class="swiper-slide">
                                    <div class="food-cat-item">
                                        <a href="<?php echo get_term_link($term); ?>" style="background-image: url(<?php echo esc_url($img_url); ?>);">
                                            <h4><?php echo esc_html($term->name); ?></h4>
                                            <span><?php echo $term->count; ?> Items</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-all-menus {
            background: #d63638;
            color: #fff !important;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        .btn-all-menus:hover { background: #111; }
        .food-cat-item a { display: block; height: 300px; background-size: cover; background-position: center; border-radius: 15px; position: relative; overflow: hidden; }
        .food-cat-item a::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.7)); }
        .food-cat-item h4, .food-cat-item span { position: relative; z-index: 2; margin: 0; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swiper !== 'undefined') {
                new Swiper('.food-cat-carousel', {
                    loop: true,
                    slidesPerView: 1,
                    spaceBetween: 20,
                    autoplay: { delay: 4000 },
                    pagination: { el: '.swiper-pagination', clickable: true },
                    breakpoints: {
                        640: { slidesPerView: 2 },
                        991: { slidesPerView: 3 },
                        1200: { slidesPerView: 4 }
                    }
                });
            }
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('food_categories', 'fd_dynamic_category_carousel_shortcode');