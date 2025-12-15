<?php
/*--------------------------------------------------------------
# Frontend Shortcode: Food Menu + Cart (FINAL FIX)
--------------------------------------------------------------*/
function fd_food_items_shortcode(){

    $args = [
        'post_type'      => 'food_item',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'ASC' // IMPORTANT: default entry order
    ];

    $items = get_posts($args);
    if(!$items) return '<p>No food items found.</p>';

    // Group by category
    $items_by_cat = [];
    foreach($items as $item){
        $cats = wp_get_post_terms($item->ID,'food_category');
        if($cats){
            foreach($cats as $c){
                $items_by_cat[$c->slug]['name'] = $c->name;
                $items_by_cat[$c->slug]['items'][] = $item;
            }
        }
    }

    ob_start(); ?>
    
<div class="container">
<div class="row">

<!-- LEFT CATEGORY -->
<div class="col-lg-3 col-md-4 mt-5">
    <div class="menu-category-sidebar sticky-left">
        <h4 class="sidebar-title">
            Categories (<span><?php echo count($items_by_cat); ?></span>)
        </h4>
        <ul class="category-list">
            <?php foreach($items_by_cat as $slug=>$cat): ?>
                <li>
                    <a href="#cat-<?php echo esc_attr($slug); ?>">
                        <?php echo esc_html($cat['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- MENU -->
<div class="col-lg-6 col-md-8 mt-5">

<?php foreach($items_by_cat as $slug=>$cat): ?>
<div id="cat-<?php echo esc_attr($slug); ?>" class="food-menu-style-two-content food-menu">
    <h4 class="sub-heading"><?php echo esc_html($cat['name']); ?></h4>

    <ul class="meal-items">
    <?php foreach($cat['items'] as $item):
        $price = (float) get_post_meta($item->ID,'price',true);
        ?>
        <li>
            <div class="thumbnail">
                <?php echo get_the_post_thumbnail($item->ID,'thumbnail'); ?>
            </div>

            <div class="content">
                <div class="top">
                    <div class="title">
                        <h4><?php echo esc_html($item->post_title); ?></h4>
                    </div>
                    <div class="price">
                        <span>$<?php echo number_format($price,2); ?></span>
                    </div>
                </div>

                <div class="bottom">
                    <p><?php echo esc_html($item->post_content); ?></p>
                </div>

                <button class="order-btn"
                    data-name="<?php echo esc_attr($item->post_title); ?>"
                    data-price="<?php echo esc_attr($price); ?>">
                    Add to Order
                </button>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endforeach; ?>

</div>

<!-- MINI CART -->
<div class="col-lg-3 d-none d-lg-block mt-5">
    <div class="mini-cart-box sticky-right">
        <h4 class="sidebar-title">Your Order</h4>

        <ul class="mini-cart-items" id="cartItems"></ul>

        <div class="mini-cart-total">
            <strong>Total: $<span id="cartTotal">0</span></strong>
        </div>

        <a href="<?php echo site_url('/checkout'); ?>"
           class="btn btn-primary btn-block mt-3">
            Checkout
        </a>
    </div>
</div>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    let cart = JSON.parse(localStorage.getItem('fd_cart')) || [];
    const cartWrap = document.getElementById('cartItems');
    const totalEl  = document.getElementById('cartTotal');

    function renderCart(){
        cartWrap.innerHTML = '';
        let total = 0;

        cart.forEach((item,index)=>{
            total += parseFloat(item.price);

            cartWrap.innerHTML += `
                <li>
                    ${item.name}
                    <span>$${parseFloat(item.price).toFixed(2)}</span>
                </li>
            `;
        });

        totalEl.innerText = total.toFixed(2);
        localStorage.setItem('fd_cart', JSON.stringify(cart));
    }

    document.querySelectorAll('.order-btn').forEach(btn=>{
        btn.addEventListener('click', function(){
            cart.push({
                name: this.dataset.name,
                price: this.dataset.price
            });
            renderCart();
        });
    });

    renderCart();
});
</script>

<?php
return ob_get_clean();
}
add_shortcode('fd_food_items','fd_food_items_shortcode');
