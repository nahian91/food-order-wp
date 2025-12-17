<?php
/*--------------------------------------------------------------
# Frontend Shortcode: Food Menu + Cart (REFRESH & REMOVE FIX)
--------------------------------------------------------------*/
function fd_food_items_shortcode(){

    $args = [
        'post_type'      => 'food_item',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'ASC'
    ];

    $items = get_posts($args);
    if(!$items) return '<p>No food items found.</p>';

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

<!-- LEFT SIDE CATEGORY LIST (Sticky) -->
        <div class="col-lg-3 col-md-4 mt-5">
            <div class="menu-category-sidebar sticky-left">
                <h4 class="sidebar-title">
                    Categories (<span id="catCount">0</span>)
                </h4>
                <ul class="category-list" id="categoryList">

                    <li><a href="#cat-set-meals">Set Meals</a></li>
                    <li><a href="#cat-appetisers">Appetisers</a></li>
                    <li><a href="#cat-tandoori">Tandoori Specialties</a></li>
                    <li><a href="#cat-our-specialties">Our Specialties</a></li>
                    <li><a href="#cat-fish">Fish Specialities</a></li>
                    <li><a href="#cat-curry">Curry Dishes</a></li>
                    <li><a href="#cat-balti">Balti Dishes</a></li>
                    <li><a href="#cat-bhuna">Bhuna Dishes</a></li>
                    <li><a href="#cat-madras">Madras</a></li>
                    <li><a href="#cat-vindaloo">Vindaloo</a></li>
                    <li><a href="#cat-korma">Korma Dishes</a></li>
                    <li><a href="#cat-dupiaza">Dupiaza Dishes</a></li>
                    <li><a href="#cat-rogan">Rogan Dishes</a></li>
                    <li><a href="#cat-mixed">Mixed Dishes</a></li>
                    <li><a href="#cat-vegetarian">Vegetarian Dishes</a></li>
                    <li><a href="#cat-persian">Persian Dishes</a></li>
                    <li><a href="#cat-veg-side">Vegetable Side Dish</a></li>
                    <li><a href="#cat-biryani">Biryani</a></li>
                    <li><a href="#cat-rice">Rice</a></li>
                    <li><a href="#cat-noodle">Noodle Dishes</a></li>
                    <li><a href="#cat-breads">Breads</a></li>
                    <li><a href="#cat-kids">Kids' Meals</a></li>

                </ul>
            </div>
        </div>

 <!-- MIDDLE Product Lists -->
        <div class="col-lg-6 col-md-8 mt-5">

            <div id="cat-set-meals" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Set Meals</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top">
                    <div class="title"><h4>Meal for 1 Person</h4></div>
                    <div class="price"><span>£16.50</span></div>
                </div>
                <div class="bottom">
                    <p>Starter: tandoori chicken, Main: chicken or lamb tikka masala, vegetable curry, pilau rice, nan bread and papadom.</p>
                </div>
                <button class="order-btn" data-name="Meal for 1 Person" data-price="16.50">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top">
                    <div class="title"><h4>Non Vegetarian Thali for 2 People</h4></div>
                    <div class="price"><span>£24.50</span></div>
                </div>
                <div class="bottom">
                    <p>Chicken tikka, onion bhaji; lamb bhuna, chicken curry, mushroom bhaji, naan, pilau rice, papadom with mint sauce & onion salad.</p>
                </div>
                <button class="order-btn" data-name="Non Vegetarian Thali for 2 People" data-price="24.50">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top">
                    <div class="title"><h4>Vegetable Set Meal for 1 Person</h4></div>
                    <div class="price"><span>£13.50</span></div>
                </div>
                <div class="bottom">
                    <p>Onion bhaji, vegetable masala, Bombay potato, pilau rice, papadom.</p>
                </div>
                <button class="order-btn" data-name="Vegetable Set Meal for 1 Person" data-price="13.50">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="assets/img/food/4.jpg" alt=""></div>
            <div class="content">
                <div class="top">
                    <div class="title"><h4>Meal for 2 Anarkali</h4></div>
                    <div class="price"><span>£32.00</span></div>
                </div>
                <div class="bottom">
                    <p>Chicken tikka, seekh kebab, lamb bhuna, chicken korma, vegetable curry, aloo gobi, naan, 2 pilau rice & 2 papadoms with mint sauce & onion.</p>
                </div>
                <button class="order-btn" data-name="Meal for 2 Anarkali" data-price="32.00">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="assets/img/food/5.jpg" alt=""></div>
            <div class="content">
                <div class="top">
                    <div class="title"><h4>Meal for 2 Mahraja</h4></div>
                    <div class="price"><span>£35.00</span></div>
                </div>
                <div class="bottom">
                    <p>Tandoori chicken, tandoori king prawn; chicken tikka masala, karahi lamb, cauliflower bhaji, sag aloo, 2 special fried rice, 1 keema naan & papadoms with mint sauce and onion salad.</p>
                </div>
                <button class="order-btn" data-name="Meal for 2 Mahraja" data-price="35.00">Order</button>
            </div>
        </li>

    </ul>
</div>

<div class="col-lg-3 d-none d-lg-block mt-5">
            <div class="mini-cart-box sticky-right">
                <h4 class="sidebar-title">Your Order</h4>

                <ul class="mini-cart-items" id=""></ul>

                <div class="mini-cart-total">
                    <p><strong>Total:</strong> $<span id="cartTotal">0</span></p>
                </div>

                <a href="#" class="btn btn-primary btn-block mt-3">Checkout</a>
            </div>
        </div>

</div>
</div>


<?php
return ob_get_clean();
}
add_shortcode('fd_food_items','fd_food_items_shortcode');