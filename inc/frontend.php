<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * FULL WORKING FRONTEND: Menu visible to all, Login check on Checkout click + Header Sync
 */
function fd_food_items_shortcode() {

    // Fetch Food Items
    $items = get_posts([
        'post_type'      => 'food_item',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC'
    ]);

    if (!$items) return '<p>No food items found.</p>';

    // Group by Category
    $items_by_cat = [];
    foreach ($items as $item) {
        $terms = wp_get_post_terms($item->ID, 'food_category');
        $cat_name = !empty($terms) ? $terms[0]->name : 'Other';
        $cat_slug = !empty($terms) ? $terms[0]->slug : 'other';
        $cat_id   = !empty($terms) ? $terms[0]->term_id : 0;

        if (!isset($items_by_cat[$cat_slug])) {
            $cat_img = get_term_meta($cat_id, 'image', true); 
            $items_by_cat[$cat_slug] = [
                'name' => $cat_name,
                'img'  => $cat_img ? wp_get_attachment_url($cat_img) : '', 
                'items' => []
            ];
        }
        $items_by_cat[$cat_slug]['items'][] = $item;
    }

    $afon_currency = '€'; 
    $is_logged_in = is_user_logged_in() ? 'true' : 'false';
    $login_url = site_url('/login/');

    ob_start(); ?>

<style>
    .fd-main-wrapper { max-width: 1100px; margin: 0 auto; font-family: sans-serif; padding: 20px; }
    .fd-cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin-bottom: 40px; }
    .fd-cat-card { text-decoration: none; color: inherit; text-align: center; transition: 0.3s; }
    .fd-cat-thumb { width: 100%; aspect-ratio: 1/1; border-radius: 15px; object-fit: cover; background: #f0f0f1; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; margin-bottom: 8px; overflow: hidden; }
    .fd-container { display: flex; flex-wrap: wrap; gap: 30px; }
    .fd-menu-section { flex: 1; min-width: 320px; }
    .fd-cart-sidebar { width: 340px; }
    .fd-cat-header { background: #f8f9fa; padding: 10px 15px; border-left: 5px solid #d63638; margin: 30px 0 20px; font-size: 20px; font-weight: bold; scroll-margin-top: 20px; }
    .fd-item-row { display: flex; border: 1px solid #eee; padding: 15px; border-radius: 12px; margin-bottom: 15px; background: #fff; }
    .fd-item-img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; margin-right: 15px; }
    .fd-sticky-cart { position: sticky; top: 20px; border: 1px solid #ddd; border-radius: 15px; padding: 20px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .fd-cart-item { border-bottom: 1px solid #eee; padding: 10px 0; }
    .fd-qty-wrap { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
    .fd-btn-qty { background: #eee; border: none; width: 25px; height: 25px; cursor: pointer; border-radius: 4px; }
    .fd-add-btn { background: #d63638; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 10px; font-weight: bold; }
    .fd-checkout-btn { display: block; text-align: center; text-decoration: none; width:100%; background:#2271b1; color:#fff; border:none; padding:12px; border-radius:8px; margin-top:15px; font-weight:bold; cursor:pointer; transition: 0.3s; }
    .fd-checkout-btn:hover { background: #1a5a8e; color: #fff; }
</style>

<div class="fd-main-wrapper">
    <div class="fd-cat-grid">
        <?php foreach ($items_by_cat as $slug => $cat) : ?>
            <a href="#cat-<?php echo esc_attr($slug); ?>" class="fd-cat-card">
                <div class="fd-cat-thumb">
                    <?php if($cat['img']): ?>
                        <img src="<?php echo esc_url($cat['img']); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <span style="font-size: 24px; color:#d63638;"><?php echo esc_html(substr($cat['name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <span style="font-weight:bold; font-size:14px;"><?php echo esc_html($cat['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="fd-container">
        <div class="fd-menu-section">
            <?php foreach ($items_by_cat as $slug => $cat_data) : ?>
                <div class="fd-cat-header" id="cat-<?php echo esc_attr($slug); ?>"><?php echo esc_html($cat_data['name']); ?></div>
                <?php foreach ($cat_data['items'] as $item) : 
                    $price = get_post_meta($item->ID, 'price', true) ?: 0;
                    $img = get_the_post_thumbnail_url($item->ID, 'thumbnail');
                ?>
                    <div class="fd-item-row">
                        <?php if($img): ?> <img src="<?php echo $img; ?>" class="fd-item-img"> <?php endif; ?>
                        <div style="flex-grow:1;">
                            <div style="display:flex; justify-content:space-between;">
                                <strong><?php echo esc_html($item->post_title); ?></strong>
                                <span><?php echo number_format($price, 2); ?> <?php echo $afon_currency; ?></span>
                            </div>
                            <p><?php echo wp_trim_words($item->post_content, 10); ?></p>
                            <button class="fd-add-btn" data-name="<?php echo esc_attr($item->post_title); ?>" data-price="<?php echo esc_attr($price); ?>">+ Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <div class="fd-cart-sidebar">
            <div class="fd-sticky-cart">
                <h4 style="margin:0 0 15px 0;">Your Order</h4>
                <div id="fd-cart-list"></div>
                <hr>
                <div style="display:flex; justify-content:space-between; font-weight:bold; font-size: 1.1rem;">
                    <span>Total:</span>
                    <span style="color:#d63638;"><span id="fd-total">0.00</span> <?php echo $afon_currency; ?></span>
                </div>
                
                <a href="<?php echo esc_url( home_url('/checkout') ); ?>" class="fd-checkout-btn" id="fd-checkout-trigger">
                    Checkout Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($){
    let cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
    const isLoggedIn = <?php echo $is_logged_in; ?>;
    const loginUrl = "<?php echo $login_url; ?>";

    function updateCart() {
        const container = $('#fd-cart-list');
        container.empty();
        let total = 0;

        if(cart.length === 0) {
            container.html('<p style="color:#999; text-align:center; padding: 20px 0;">Your cart is empty</p>');
            $('#fd-checkout-trigger').css('opacity', '0.5');
        } else {
            $('#fd-checkout-trigger').css('opacity', '1');
        }

        cart.forEach((item, index) => {
            const rowTotal = item.price * item.qty;
            total += rowTotal;
            container.append(`
                <div class="fd-cart-item">
                    <button class="fd-remove-btn fd-delete" data-index="${index}" style="color:#d63638; background:none; border:none; float:right; cursor:pointer;">✕</button>
                    <div style="font-weight:bold; font-size:14px;">${item.name}</div>
                    <div class="fd-qty-wrap">
                        <button class="fd-btn-qty fd-minus" data-index="${index}">-</button>
                        <span>${item.qty}</span>
                        <button class="fd-btn-qty fd-plus" data-index="${index}">+</button>
                        <span style="margin-left:auto; font-weight:bold;">${rowTotal.toFixed(2)}</span>
                    </div>
                </div>
            `);
        });

        $('#fd-total').text(total.toFixed(2));
        localStorage.setItem('fd_cart_save', JSON.stringify(cart));

        // SYNC WITH HEADER CART COUNT
        if (typeof syncHeaderCart === "function") {
            syncHeaderCart();
        }
    }

    // INTERCEPT CHECKOUT CLICK
    $('#fd-checkout-trigger').on('click', function(e) {
        if(cart.length === 0) {
            e.preventDefault();
            alert('Your cart is empty!');
            return;
        }

        if(!isLoggedIn) {
            e.preventDefault();
            window.location.href = loginUrl + "?redirect_to=" + encodeURIComponent(window.location.href);
        }
    });

    // Add Item
    $('.fd-add-btn').on('click', function() {
        const name = $(this).data('name');
        const price = parseFloat($(this).data('price'));
        const existing = cart.find(i => i.name === name);
        if(existing) { existing.qty += 1; } 
        else { cart.push({ name: name, price: price, qty: 1 }); }
        updateCart();
    });

    // Plus/Minus/Delete
    $(document).on('click', '.fd-plus', function() {
        cart[$(this).data('index')].qty += 1;
        updateCart();
    });
    $(document).on('click', '.fd-minus', function() {
        const idx = $(this).data('index');
        if(cart[idx].qty > 1) { cart[idx].qty -= 1; } 
        else { cart.splice(idx, 1); }
        updateCart();
    });
    $(document).on('click', '.fd-delete', function() {
        cart.splice($(this).data('index'), 1);
        updateCart();
    });

    updateCart();
});
</script>

<?php
    return ob_get_clean();
}
add_shortcode('fd_food_items','fd_food_items_shortcode');