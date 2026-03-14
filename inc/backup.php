<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SHORTCODE: Best Sellers / Featured Menu
 */
function food_best_sellers_shortcode() {
    ob_start();

    // 1. Get Categories marked as Featured using your exact meta key 'fd_is_featured'
    $featured_categories = get_terms([
        'taxonomy'   => 'food_category',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key'     => 'fd_is_featured',
                'value'   => '1',
                'compare' => '='
            ]
        ]
    ]);

    // Fallback: If no categories are toggled "Featured", show the first 2 available
    if (empty($featured_categories) || is_wp_error($featured_categories)) {
        $featured_categories = get_terms([
            'taxonomy'   => 'food_category',
            'hide_empty' => true,
            'number'     => 2
        ]);
    }
    ?>

    <div class="container pb-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="site-heading text-center">
                    <h4 class="sub-title" style="color: #d63638; font-weight: 600;">Awesome Food</h4>
                    <h2 class="title">Popular Food of our Menus</h2>
                    <div class="mt-4 mb-5">
                        <a href="<?php echo home_url('/menu'); ?>" class="btn-all-menus">VIEW ALL MENUS</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php 
            foreach ($featured_categories as $cat) : 
                
                // 2. Fetch 3 Random items using your exact post_type 'food_item'
                $products = new WP_Query([
                    'post_type'      => 'food_item', 
                    'posts_per_page' => 3,
                    'orderby'        => 'rand',
                    'tax_query'      => [[
                        'taxonomy' => 'food_category',
                        'field'    => 'term_id',
                        'terms'    => $cat->term_id,
                    ]],
                ]);

                if ($products->have_posts()) :
            ?>
                <div class="col-lg-6 mb-5">
                    <div class="food-menu-style-two-content food-menu">
                        <h4 class="sub-heading" style="border-left: 4px solid #d63638; padding-left: 15px; margin-bottom: 25px; font-weight:700; text-transform: uppercase;">
                            <?php echo esc_html($cat->name); ?>
                        </h4>
                        
                        <ul class="meal-items" style="padding: 0; margin: 0;">
                            <?php 
                            while ($products->have_posts()) : $products->the_post(); 
                                $price = get_post_meta(get_the_ID(), 'price', true); 
                            ?>
                                <li style="list-style: none; display: flex; align-items: flex-start; margin-bottom: 25px;">
                                    <div class="thumbnail" style="flex-shrink: 0; margin-right: 20px;">
                                        <?php if (has_post_thumbnail()) : 
                                            the_post_thumbnail([70, 70], ['style' => 'width:70px; height:70px; object-fit:cover; border-radius:8px;']); 
                                        else : ?>
                                            <div style="width:70px; height:70px; background:#f0f0f0; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                                                <span class="dashicons dashicons-format-image" style="color:#ccc;"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="content" style="flex-grow: 1;">
                                        <div class="top" style="display: flex; justify-content: space-between; margin-bottom: 5px; align-items: baseline;">
                                            <div class="title"><h4 style="margin:0; font-size:17px; font-weight:600;"><?php the_title(); ?></h4></div>
                                            <div class="price"><span style="color:#d63638; font-weight:700;">
                                                <?php echo $price ? '£' . number_format(floatval($price), 2) : ''; ?>
                                            </span></div>
                                        </div>
                                        <div class="bottom">
                                            <p style="margin:0; font-size:14px; color:#666; line-height: 1.4;">
                                                <?php echo wp_trim_words(get_the_excerpt(), 12); ?>
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            <?php 
                                endwhile; 
                                wp_reset_postdata(); 
                            ?>
                        </ul>
                    </div>
                </div>
            <?php 
                endif; 
            endforeach; 
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('best_sellers_menu', 'food_best_sellers_shortcode');


/**
 * SHORTCODE: Category Carousel
 */
function fd_dynamic_category_carousel_shortcode() {
    ob_start();
    
    $terms = get_terms([
        'taxonomy'   => 'food_category',
        'hide_empty' => false,
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
                    <div class="food-cat-carousel swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($terms as $term) : 
                                $img_id = get_term_meta($term->term_id, 'fd_category_image', true);
                                $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : get_template_directory_uri() . '/assets/img/category/1.jpg';
                                $target_link = home_url('/menu/#cat-' . $term->slug);
                                ?>
                                <div class="swiper-slide">
                                    <div class="food-cat-item">
                                        <a href="<?php echo esc_url($target_link); ?>" style="background-image: url(<?php echo esc_url($img_url); ?>);">
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


/**
 * SHORTCODE: Main Food Ordering System (Menu + Cart)
 * Features: Original Desktop View + Mobile Hamburger Categories + Mobile Slide-out Cart
 */
function fd_food_items_shortcode() {
    wp_enqueue_style('dashicons');

    // 1. SETTINGS & LIVE STATUS
    $store_status = function_exists('get_afd_restaurant_status') ? get_afd_restaurant_status() : ['status' => 'open', 'is_open' => true];
    $is_actually_open = ($store_status['status'] === 'open' || $store_status['status'] === 'warning');
    
    $currency = '£';
    $saved_delivery_fee = get_option('afd_delivery_charge', '0.00');
    $saved_service_fee  = get_option('afd_service_charge', '0.00');
    $saved_bag_fee      = get_option('afd_bag_charge', '0.00');
    $saved_discount     = get_option('afd_restaurant_discount', '0'); 

    // --- DYNAMIC TIME CALCULATION ---
    $schedule    = get_option('afd_schedule', []);
    $current_day = current_datetime()->format('D');
    $time_options = [];

    if (!empty($schedule[$current_day]) && isset($schedule[$current_day]['enabled']) && $schedule[$current_day]['enabled']) {
        $start_ts = strtotime($schedule[$current_day]['open']);
        $end_ts   = strtotime($schedule[$current_day]['close']);
        if ($end_ts <= $start_ts) { $end_ts += 86400; }

        for ($t = $start_ts; $t <= $end_ts; $t += 3600) {
            $time_options[date('H:i', $t)] = date('h:i A', $t);
        }
    }

    // 2. FETCH DATA
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
    /* --- ORIGINAL DESKTOP STYLES (NO CHANGE) --- */
    .fd-main-wrapper { max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: 'Inter', sans-serif; background: #fcfcfc; }
    .fd-search-container { margin-bottom: 40px; position: relative; }
    .fd-menu-search { width: 100%; padding: 18px 25px 18px 55px; border-radius: 15px; border: 1px solid #ddd; font-size: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); outline: none; transition: 0.3s; }
    .fd-menu-search:focus { border-color: #d63638; box-shadow: 0 5px 20px rgba(214, 54, 56, 0.15); }
    .fd-search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 20px; }
    
    .fd-category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; margin-bottom: 50px; }
    .fd-cat-grid-item { text-decoration: none !important; text-align: center; transition: 0.3s ease; display: block; }
    .fd-cat-grid-item:hover { transform: translateY(-5px); }
    .fd-cat-grid-thumb { width: 60px; height: 60px; border-radius: 50%; background: #fff; margin: 0 auto 8px; overflow: hidden; border: 4px solid #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; }
    .fd-cat-grid-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .fd-cat-grid-item span { display: block; font-weight: 700; color: #1a1a1a; font-size: 14px; }
    
    .fd-container { display: flex; flex-wrap: wrap; gap: 40px; }
    .fd-menu-section { flex: 1; min-width: 320px; }
    .fd-cart-sidebar { width: 380px; }
    
    .sub-heading { font-size: 28px; font-weight: 800; margin-bottom: 30px; color: #1a1a1a; border-left: 6px solid #d63638; padding-left: 20px; margin-top: 40px; }
    .meal-items { list-style: none; padding: 0; margin: 0; }
    .meal-items li { display: flex; align-items: flex-start; background: #fff; padding: 25px; border-radius: 20px; margin-bottom: 25px; border: 1px solid #f1f1f1; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .meal-items li .thumbnail { width: 130px; height: 130px; min-width: 130px; margin-right: 30px; overflow: hidden; border-radius: 15px; }
    .meal-items li .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
    .meal-items li .content { flex: 1; }
    .meal-items li .content .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .meal-items li .content .top .title h4 { margin: 0; font-size: 21px; font-weight: 700; color: #1a1a1a; }
    .meal-items li .content .top .price span { color: #d63638; font-weight: 800; font-size: 21px; }
    
    .fd-sticky-panel { background: #fff; border-radius: 25px; padding: 30px; border: 1px solid #eee; box-shadow: 0 20px 50px rgba(0,0,0,0.08); display: flex; flex-direction: column; }
    .fd-order-type { display: flex; gap: 10px; margin-bottom: 15px; background: #f0f0f1; padding: 6px; border-radius: 12px; }
    .fd-type-label { flex: 1; text-align: center; cursor: pointer; padding: 10px; border-radius: 8px; font-weight: 700; transition: 0.3s; color: #666; }
    .fd-order-type input { display: none; }
    .fd-order-type input:checked + .fd-type-label { background: #d63638; color: #fff; }
    
    .fd-cart-item { border-bottom: 1px solid #f8f8f8; padding: 15px 0; }
    .fd-checkout-btn { display: block; text-align: center; background:#d63638; color:#fff !important; padding:18px; border-radius:15px; margin-top:10px; font-weight:800; text-decoration: none !important; box-shadow: 0 10px 20px rgba(214, 54, 56, 0.2); }
    .order-btn { padding: 12px 30px; background: #d63638; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
    .fd-summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #666; }
    .fd-summary-row.total-row { border-top: 2px dashed #eee; padding-top: 15px; margin-top: 10px; font-weight: 800; font-size: 22px; color: #1a1a1a; }
    .fd-qty-selector { display: flex; align-items: center; gap: 10px; background: #f8f8f8; padding: 5px 10px; border-radius: 10px; border: 1px solid #eee; }
    .fd-qty-selector span { font-weight: 800; font-size: 16px; min-width: 20px; text-align: center; }
    .order-btn-align { display: flex; align-items: center; gap: 15px; margin-top: 15px; }
    .fd-minus, .fd-plus, .fd-item-minus, .fd-item-plus { width: 28px; height: 28px; border-radius: 50%; border: 1px solid #ddd; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }

    /* --- MOBILE CATEGORY HAMBURGER TRIGGER --- */
    .fd-mobile-cat-header { display: none; align-items: center; justify-content: space-between; margin-bottom: 20px; background: #fff; padding: 10px 15px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .fd-cat-trigger { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 16px; cursor: pointer; color: #d63638; }
    .fd-cat-trigger span.dashicons { font-size: 24px; width: 24px; height: 24px; }

    /* --- MOBILE CATEGORY DRAWER --- */
    .fd-cat-drawer { position: fixed; top: 0; left: -100%; width: 280px; height: 100%; background: #fff; z-index: 2005; transition: 0.4s ease; overflow-y: auto; box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
    .fd-cat-drawer.active { left: 0; }
    .fd-cat-drawer-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .fd-cat-drawer-list { padding: 10px 0; list-style: none; margin: 0; }
    .fd-cat-drawer-list a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none !important; color: #1a1a1a; font-weight: 700; border-bottom: 1px solid #f9f9f9; }
    .fd-cat-drawer-list img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #eee; }

    /* --- MOBILE STYLES (MAX 991px) --- */
    @media (max-width: 991px) {
        .fd-category-grid { display: none; }
        .fd-mobile-cat-header { display: flex; }
        
        /* Mobile Slide-out Cart */
        .fd-cart-sidebar {
            position: fixed; top: 0; right: -100%; width: 100%; height: 100%;
            background: #fff; z-index: 2000; transition: 0.4s ease; padding: 0; overflow-y: auto;
        }
        .fd-cart-sidebar.active { right: 0; }
        .fd-sticky-panel { border: none; box-shadow: none; border-radius: 0; min-height: 100vh; }
        .fd-mobile-cart-header { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; position: sticky; top: 0; background: #fff; z-index: 10; }
        .fd-close-cart { font-size: 24px; cursor: pointer; margin-right: 15px; }

        /* Bottom Cart Bar */
        .fd-bottom-cart-bar {
            display: flex; position: fixed; bottom: 20px; left: 15px; right: 15px;
            background: #d63638; color: #fff; padding: 15px 20px; border-radius: 15px;
            z-index: 1000; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 25px rgba(214, 54, 56, 0.4); cursor: pointer;
        }
        .fd-bottom-cart-bar .info { font-weight: 800; font-size: 16px; }

        .fd-container { flex-direction: column; }
        .meal-items li .thumbnail { width: 100px; height: 100px; min-width: 100px; margin-right: 15px; }
        .meal-items li { padding: 15px; }
        .sub-heading { font-size: 22px; }
        
        /* Overlay for drawers */
        .fd-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1999; }
        .fd-overlay.active { display: block; }
    }

    @media (min-width: 992px) {
        .fd-bottom-cart-bar, .fd-mobile-cart-header, .fd-overlay { display: none !important; }
    }
</style>

<div class="fd-main-wrapper">
    <div class="fd-search-container">
        <span class="fd-search-icon">🔍</span>
        <input type="text" id="fd-menu-search" class="fd-menu-search" placeholder="Search for your favorite food...">
    </div>

    <div class="fd-mobile-cat-header">
        <div class="fd-cat-trigger" id="fd-open-cats">
            <span class="dashicons dashicons-menu"></span>
            <span>Menu Categories</span>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #666;">Select a section</div>
    </div>

    <div class="fd-category-grid">
        <?php foreach ($items_by_cat as $slug => $cat) : ?>
            <a href="#cat-<?php echo esc_attr($slug); ?>" class="fd-cat-grid-item">
                <div class="fd-cat-grid-thumb"><img src="<?php echo esc_url($cat['img']); ?>" alt="Category"></div>
                <span><?php echo esc_html($cat['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="fd-container">
        <div class="fd-menu-section">
            <?php foreach ($items_by_cat as $slug => $cat_data) : ?>
                <div id="cat-<?php echo esc_attr($slug); ?>" class="food-menu">
                    <h4 class="sub-heading"><?php echo esc_html($cat_data['name']); ?></h4>
                    <ul class="meal-items">
                        <?php foreach ($cat_data['items'] as $item) : 
                            $price = get_post_meta($item->ID, 'price', true) ?: '0.00';
                            $img = get_the_post_thumbnail_url($item->ID, 'medium') ?: 'https://via.placeholder.com/130';
                        ?>
                            <li class="fd-food-card" data-title="<?php echo esc_attr(strtolower($item->post_title)); ?>">
                                <div class="thumbnail"><img src="<?php echo esc_url($img); ?>" alt="Food"></div>
                                <div class="content">
                                    <div class="top">
                                        <div class="title"><h4><?php echo esc_html($item->post_title); ?></h4></div>
                                        <div class="price"><span><?php echo $currency . number_format((float)$price, 2); ?></span></div>
                                    </div>
                                    <div class="bottom"><p><?php echo wp_kses_post($item->post_content); ?></p></div>
                                    <div class="order-btn-align">
                                        <div class="fd-qty-selector">
                                            <button class="fd-item-minus"><span class="dashicons dashicons-minus"></span></button>
                                            <span class="fd-item-qty">1</span>
                                            <button class="fd-item-plus"><span class="dashicons dashicons-plus"></span></button>
                                        </div>
                                        <button class="order-btn" data-name="<?php echo esc_attr($item->post_title); ?>" data-price="<?php echo esc_attr($price); ?>">Add to Order</button>
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
                    <input type="radio" name="order_type" id="pickup" value="pickup">
                    <label for="pickup" class="fd-type-label">Pickup</label>
                </div>

                <div class="fd-schedule-wrap" style="margin-bottom:20px;">
                    <select id="fd-scheduled-time" class="fd-schedule-select" style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd; font-weight:600;">
                        <?php if ($is_actually_open) : ?><option value="asap">ASAP (Fastest Delivery)</option><?php else : ?><option value="" disabled selected>Select Pre-Order Time</option><?php endif; ?>
                        <?php foreach($time_options as $val => $lbl) echo '<option value="'.esc_attr($val).'">'.esc_html($lbl).'</option>'; ?>
                    </select>
                </div>

                <h4 style="margin:0 0 15px 0; font-weight:800; font-size:20px;">Your Order</h4>
                <div id="fd-cart-list"></div>

                <div class="fd-summary-container">
                    <div class="fd-summary-row"><span>Subtotal</span><span><?php echo $currency; ?><span id="br-subtotal">0.00</span></span></div>
                    <div class="fd-summary-row"><span>Discount (<?php echo esc_html($saved_discount); ?>%)</span><span>-<?php echo $currency; ?><span id="br-discount-value">0.00</span></span></div>
                    <div class="fd-summary-row" style="font-weight:700; color:#1a1a1a;"><span>Order total</span><span><?php echo $currency; ?><span id="br-order-total">0.00</span></span></div>
                    <div class="fd-summary-row"><span>Service Charge</span><span><?php echo $currency . number_format((float)$saved_service_fee, 2); ?></span></div>
                    <div id="fd-delivery-row" class="fd-summary-row"><span>Delivery</span><span><?php echo $currency . number_format((float)$saved_delivery_fee, 2); ?></span></div>
                    <div class="fd-summary-row"><span>Bag Charge</span><span><?php echo $currency . number_format((float)$saved_bag_fee, 2); ?></span></div>
                    <div class="fd-summary-row"><span>Tips</span><span><?php echo $currency; ?><input type="number" id="fd-tip-amount" class="fd-tip-input" value="0.00" step="0.50" min="0"></span></div>
                    <div class="fd-summary-row total-row"><span>Total Due</span><span style="color:#d63638;"><?php echo $currency; ?><span id="fd-total-due">0.00</span></span></div>
                    <textarea id="fd-kitchen-notes" class="fd-kitchen-notes" rows="2" placeholder="Any allergies?" style="width:100%; padding:10px; border-radius:10px; border:1px solid #ddd; margin-top:15px;"></textarea>
                </div>

                <a href="#" class="fd-checkout-btn" id="fd-checkout-trigger"><?php echo $is_actually_open ? 'Confirm Order' : 'Place Pre-Order'; ?></a>
            </div>
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
    const delFee = parseFloat("<?php echo $saved_delivery_fee; ?>") || 0;
    const srvFee = parseFloat("<?php echo $saved_service_fee; ?>") || 0;
    const bagFee = parseFloat("<?php echo $saved_bag_fee; ?>") || 0;
    const resDiscountPercent = parseFloat("<?php echo $saved_discount; ?>") || 0; 
    const currency = "<?php echo $currency; ?>";

    function updateCart() {
        const container = $('#fd-cart-list');
        container.empty();
        let subtotal = 0, count = 0;

        if(cart.length === 0) {
            container.html('<div style="color:#bbb; text-align:center; padding: 20px 0;">Your cart is empty</div>');
            $('#fd-checkout-trigger').css({'opacity': '0.5', 'pointer-events': 'none'});
            $('#fd-mobile-trigger').fadeOut();
        } else {
            $('#fd-checkout-trigger').css({'opacity': '1', 'pointer-events': 'auto'});
            $('#fd-mobile-trigger').css('display', 'flex').fadeIn();
        }

        cart.forEach((item, index) => {
            const rowTotal = item.price * item.qty;
            subtotal += rowTotal; count += item.qty;
            container.append(`
                <div class="fd-cart-item">
                    <div style="display:flex; justify-content:space-between; font-weight:700;">
                        <span>${item.name}</span>
                        <button class="fd-delete" data-index="${index}" style="color:#ff4d4d; background:none; border:none; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; margin-top:5px;">
                        <button class="fd-minus" data-index="${index}"><span class="dashicons dashicons-minus"></span></button>
                        <span style="font-weight:800;">${item.qty}</span>
                        <button class="fd-plus" data-index="${index}"><span class="dashicons dashicons-plus"></span></button>
                        <span style="margin-left:auto; font-weight:700; color:#d63638;">${currency}${rowTotal.toFixed(2)}</span>
                    </div>
                </div>
            `);
        });

        let isDel = $('input[name="order_type"]:checked').val() === 'delivery';
        let tip = parseFloat($('#fd-tip-amount').val()) || 0;
        let disc = (subtotal * resDiscountPercent) / 100;
        let ordTotal = Math.max(0, subtotal - disc);
        
        $('#br-subtotal').text(subtotal.toFixed(2));
        $('#br-discount-value').text(disc.toFixed(2));
        $('#br-order-total').text(ordTotal.toFixed(2));
        if(!isDel) $('#fd-delivery-row').hide(); else $('#fd-delivery-row').show();

        let totalDue = subtotal > 0 ? (ordTotal + srvFee + (isDel ? delFee : 0) + bagFee + tip) : 0;
        $('#fd-total-due, #m-total').text(totalDue.toFixed(2));
        $('#m-count').text(count);
        localStorage.setItem('fd_cart_save', JSON.stringify(cart));
    }

    // --- Drawer Controls ---
    function closeDrawers() { $('.fd-cat-drawer, .fd-cart-sidebar, #fd-overlay').removeClass('active'); }
    $('#fd-open-cats').on('click', function() { $('#fd-cat-drawer, #fd-overlay').addClass('active'); });
    $('#fd-mobile-trigger').on('click', function() { $('#fd-cart-sidebar, #fd-overlay').addClass('active'); });
    $('#fd-close-cats, .fd-close-cart, #fd-overlay, .fd-drawer-cat-link').on('click', function() { closeDrawers(); });

    // --- Original UI Interactions ---
    $(document).on('click', '.fd-item-plus', function(){ let s = $(this).siblings('.fd-item-qty'); s.text(parseInt(s.text()) + 1); });
    $(document).on('click', '.fd-item-minus', function(){ let s = $(this).siblings('.fd-item-qty'); if(parseInt(s.text()) > 1) s.text(parseInt(s.text()) - 1); });

    $(document).on('click', '.order-btn', function() {
        const name = $(this).data('name'), price = parseFloat($(this).data('price'));
        const qty = parseInt($(this).siblings('.fd-qty-selector').find('.fd-item-qty').text());
        const exist = cart.find(i => i.name === name);
        if(exist) exist.qty += qty; else cart.push({ name, price, qty });
        $(this).siblings('.fd-qty-selector').find('.fd-item-qty').text(1);
        updateCart();
    });

    $(document).on('click', '.fd-plus', function() { cart[$(this).data('index')].qty += 1; updateCart(); });
    $(document).on('click', '.fd-minus', function() {
        const idx = $(this).data('index');
        if(cart[idx].qty > 1) cart[idx].qty -= 1; else cart.splice(idx, 1);
        updateCart();
    });
    $(document).on('click', '.fd-delete', function() { cart.splice($(this).data('index'), 1); updateCart(); });
    
    $('input[name="order_type"], #fd-tip-amount').on('change input', updateCart);
    $('#fd-menu-search').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.fd-food-card').each(function() { $(this).toggle($(this).data('title').indexOf(val) > -1); });
    });

    $(document).on('click', '#fd-checkout-trigger', function(e) {
        e.preventDefault();
        localStorage.setItem('fd_scheduled_time', $('#fd-scheduled-time').val());
        localStorage.setItem('fd_kitchen_notes', $('#fd-kitchen-notes').val());
        window.location.href = <?php echo $is_logged_in; ?> ? "<?php echo home_url('/checkout'); ?>" : "<?php echo $login_url; ?>?redirect_to=" + encodeURIComponent(window.location.href);
    });

    updateCart();
});
</script>

<?php return ob_get_clean(); }
add_shortcode('fd_food_items','fd_food_items_shortcode');











//// Print

/*--------------------------------------------------------------
# 0. INVOICE GENERATION LOGIC (PDF/Print)
--------------------------------------------------------------*/
if (isset($_GET['action']) && $_GET['action'] === 'print' && isset($_GET['type']) && $_GET['type'] === 'customer') {
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $afon_order_id));
    if (!$order) wp_die('Order not found.');

    $items = json_decode($order->items_json, true) ?: [];


    echo '<div class="invoice-box">';
    echo '<button class="no-print" onclick="window.print()">Print Invoice</button>';
    echo '<h1>Invoice #' . esc_html(!empty($order->display_id) ? $order->display_id : 'REC-'.$order->id) . '</h1>';
    echo '<p>Date: ' . esc_html($order->order_date) . '<br>';
    echo 'Customer: ' . esc_html($order->full_name) . '<br>';
    echo 'Phone: ' . esc_html($order->phone) . '<br>';
    echo 'Email: ' . esc_html($order->customer_email) . '</p>';

    echo '<table><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>';
    foreach ($items as $item) {
        echo '<tr>
            <td>' . esc_html($item['name']) . '</td>
            <td>' . intval($item['qty']) . '</td>
            <td>£' . number_format($item['price'], 2) . '</td>
            <td>£' . number_format($item['qty'] * $item['price'], 2) . '</td>
        </tr>';
    }
    echo '</tbody><tfoot>
        <tr><td colspan="3">Subtotal</td><td>£' . number_format($order->total_price - $order->delivery_fee - $order->tip_amount, 2) . '</td></tr>
        <tr><td colspan="3">Delivery</td><td>£' . number_format($order->delivery_fee, 2) . '</td></tr>
        <tr><td colspan="3">Tip</td><td>£' . number_format($order->tip_amount, 2) . '</td></tr>
        <tr class="total-row"><td colspan="3">Total</td><td>£' . number_format($order->total_price, 2) . '</td></tr>
    </tfoot></table>';
    echo '</div>';
    echo '<script>window.onload = function() { window.print(); }</script>';
    exit;
}