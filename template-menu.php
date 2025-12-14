<?php

/*
Template Name: Menu
*/

get_header();?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Special Food</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Food</li>
                </ul>
            </div>
        </div>
    </div>
</div>

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
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
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
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
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
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
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
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/4.jpg" alt=""></div>
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
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/5.jpg" alt=""></div>
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

<div id="cat-appetisers" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Appetisers</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Onion Bhaji</h4></div><div class="price"><span>£3.25</span></div></div>
                <div class="bottom"><p></p></div>
                <button class="order-btn" data-name="Onion Bhaji" data-price="3.25">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chingri Puri</h4></div><div class="price"><span>£4.50</span></div></div>
                <div class="bottom"><p>Small sea prawns stir fried in Goan style with spring onions and tomatoes in medium spices, served with home made puri bread.</p></div>
                <button class="order-btn" data-name="Chingri Puri" data-price="4.50">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Samosa Meat</h4></div><div class="price"><span>£3.05</span></div></div>
                <div class="bottom"><p></p></div>
                <button class="order-btn" data-name="Samosa Meat" data-price="3.05">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/4.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>King Prawn Butterfly</h4></div><div class="price"><span>£5.85</span></div></div>
                <div class="bottom"><p>Fried with breadcrumbs.</p></div>
                <button class="order-btn" data-name="King Prawn Butterfly" data-price="5.85">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/5.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Pakora</h4></div><div class="price"><span>£3.95</span></div></div>
                <div class="bottom"><p></p></div>
                <button class="order-btn" data-name="Chicken Pakora" data-price="3.95">Order</button>
            </div>
        </li>

    </ul>
</div>

<div id="cat-tandoori" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Tandoori Specialties</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Tikka</h4></div><div class="price"><span>£5.95</span></div></div>
                <div class="bottom"><p>Chunks of chicken marinated with spices and cooked in the tandoor.</p></div>
                <button class="order-btn" data-name="Chicken Tikka" data-price="5.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Shashlik</h4></div><div class="price"><span>£6.95</span></div></div>
                <div class="bottom"><p>Chicken tikka cooked with onions, peppers and tomato sauce in tandoor.</p></div>
                <button class="order-btn" data-name="Chicken Shashlik" data-price="6.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Tandoori</h4></div><div class="price"><span>£6.50</span></div></div>
                <div class="bottom"><p>Half chicken marinated with yogurt and tandoori spices, cooked in tandoor.</p></div>
                <button class="order-btn" data-name="Chicken Tandoori" data-price="6.50">Order</button>
            </div>
        </li>

    </ul>
</div>
<div id="cat-our-specialties" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Our Specialties</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>King Prawn Masala</h4></div><div class="price"><span>£12.95</span></div></div>
                <div class="bottom"><p>Fresh king prawns cooked in a spicy masala sauce with onions and peppers.</p></div>
                <button class="order-btn" data-name="King Prawn Masala" data-price="12.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Lamb Tikka Masala</h4></div><div class="price"><span>£10.95</span></div></div>
                <div class="bottom"><p>Tender lamb chunks cooked in creamy tomato masala sauce.</p></div>
                <button class="order-btn" data-name="Lamb Tikka Masala" data-price="10.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Korma</h4></div><div class="price"><span>£9.95</span></div></div>
                <div class="bottom"><p>Chicken cooked with cream, almonds, and mild spices for a delicate flavor.</p></div>
                <button class="order-btn" data-name="Chicken Korma" data-price="9.95">Order</button>
            </div>
        </li>

    </ul>
</div>
<div id="cat-fish" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Fish Specialities</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>King Prawn Curry</h4></div><div class="price"><span>£12.95</span></div></div>
                <div class="bottom"><p>King prawns cooked in a medium spiced curry sauce with fresh herbs.</p></div>
                <button class="order-btn" data-name="King Prawn Curry" data-price="12.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>King Prawn Bhuna</h4></div><div class="price"><span>£12.95</span></div></div>
                <div class="bottom"><p>Prawns cooked in thick spicy bhuna sauce with onions and tomatoes.</p></div>
                <button class="order-btn" data-name="King Prawn Bhuna" data-price="12.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Fish Masala</h4></div><div class="price"><span>£11.95</span></div></div>
                <div class="bottom"><p>Fresh fish fillets cooked in traditional masala sauce with medium spices.</p></div>
                <button class="order-btn" data-name="Fish Masala" data-price="11.95">Order</button>
            </div>
        </li>

    </ul>
</div>
<div id="cat-curry" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Curry Dishes</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Curry</h4></div><div class="price"><span>£8.95</span></div></div>
                <div class="bottom"><p>Classic chicken curry cooked with medium spices and fresh herbs.</p></div>
                <button class="order-btn" data-name="Chicken Curry" data-price="8.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Lamb Curry</h4></div><div class="price"><span>£9.95</span></div></div>
                <div class="bottom"><p>Succulent lamb cooked in traditional curry sauce with medium spices.</p></div>
                <button class="order-btn" data-name="Lamb Curry" data-price="9.95">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Vegetable Curry</h4></div><div class="price"><span>£7.95</span></div></div>
                <div class="bottom"><p>Seasonal vegetables cooked in mild curry sauce with herbs and spices.</p></div>
                <button class="order-btn" data-name="Vegetable Curry" data-price="7.95">Order</button>
            </div>
        </li>

    </ul>
</div>
<div id="cat-balti" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Balti Dishes</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Balti</h4></div><div class="price"><span>£9.50</span></div></div>
                <div class="bottom"><p>Chicken cooked in a thick balti sauce with peppers and onions.</p></div>
                <button class="order-btn" data-name="Chicken Balti" data-price="9.50">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Lamb Balti</h4></div><div class="price"><span>£10.50</span></div></div>
                <div class="bottom"><p>Lamb cooked in traditional balti sauce with medium spices.</p></div>
                <button class="order-btn" data-name="Lamb Balti" data-price="10.50">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Vegetable Balti</h4></div><div class="price"><span>£8.50</span></div></div>
                <div class="bottom"><p>Seasonal vegetables cooked in balti sauce with mild spices.</p></div>
                <button class="order-btn" data-name="Vegetable Balti" data-price="8.50">Order</button>
            </div>
        </li>

    </ul>
</div>

<div id="cat-bhuna" class="food-menu-style-two-content food-menu" style="margin-left: 0;">
    <h4 class="sub-heading">Bhuna Dishes</h4>
    <ul class="meal-items">

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Chicken Bhuna</h4></div><div class="price"><span>£9.00</span></div></div>
                <div class="bottom"><p>Chicken cooked in thick bhuna sauce with aromatic spices.</p></div>
                <button class="order-btn" data-name="Chicken Bhuna" data-price="9.00">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/bhuna-2.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Lamb Bhuna</h4></div><div class="price"><span>£10.00</span></div></div>
                <div class="bottom"><p>Lamb slow-cooked in bhuna sauce with onions and spices.</p></div>
                <button class="order-btn" data-name="Lamb Bhuna" data-price="10.00">Order</button>
            </div>
        </li>

        <li>
            <div class="thumbnail"><img src="<?php echo get_template_directory_uri();?>/assets/img/food/bhuna-3.jpg" alt=""></div>
            <div class="content">
                <div class="top"><div class="title"><h4>Vegetable Bhuna</h4></div><div class="price"><span>£8.00</span></div></div>
                <div class="bottom"><p>Mixed vegetables cooked in bhuna sauce with medium spices.</p></div>
                <button class="order-btn" data-name="Vegetable Bhuna" data-price="8.00">Order</button>
            </div>
        </li>

    </ul>
</div>


        </div>

        <!-- RIGHT SIDE MINI CART (Sticky) -->
        <div class="col-lg-3 d-none d-lg-block mt-5">
            <div class="mini-cart-box sticky-right">
                <h4 class="sidebar-title">Your Order</h4>

                <ul class="mini-cart-items" id="cartItems"></ul>

                <div class="mini-cart-total">
                    <p><strong>Total:</strong> $<span id="cartTotal">0</span></p>
                </div>

                <a href="#" class="btn btn-primary btn-block mt-3">Checkout</a>
            </div>
        </div>

    </div>
</div>

<style>
    /* Sticky Panels */
.sticky-left {
    position: sticky;
    top: 20px;
}
.sticky-right {
    position: sticky;
    top: 20px;
}

/* Sidebar */
.menu-category-sidebar {
    padding: 20px;
    background: #fafafa;
    border-radius: 10px;
}
.category-list {
    list-style: none;
    padding: 0;
}
.category-list li {
    margin-bottom: 8px;
}
.category-list a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

/* Order Button */
.order-btn {
    margin-top: 10px;
    padding: 8px 15px;
    border: none;
    background: #ff5722;
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}
.order-btn:hover {
    background: #e94c1a;
}

/* Mini Cart */
.mini-cart-box {
    padding: 20px;
    background: #f7f7f7;
    border-radius: 10px;
}
.mini-cart-items {
    list-style: none;
    margin: 0;
    padding: 0;
}
.cart-item {
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
}

.cart-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.qty-controls {
    display: flex;
    gap: 5px;
    align-items: center;
}

.qty-btn {
    padding: 2px 8px;
    background: #ddd;
    cursor: pointer;
    border-radius: 3px;
    font-weight: bold;
}

.remove-btn {
    color: red;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
}

</style>

<?php get_footer();?>


<script>
// ==========================
// CART SYSTEM
// ==========================

let cart = JSON.parse(localStorage.getItem("mini_cart")) || {};

// Save cart
function saveCart() {
    localStorage.setItem("mini_cart", JSON.stringify(cart));
}

// Render right-side mini cart
function renderCart() {
    const cartBox = document.getElementById("cartItems");
    const totalBox = document.getElementById("cartTotal");

    cartBox.innerHTML = "";

    let total = 0;

    Object.keys(cart).forEach(id => {
        const item = cart[id];
        const itemTotal = item.price * item.qty;

        total += itemTotal;

        cartBox.innerHTML += `
            <li class="cart-item">
                <div class="cart-item-row">
                    <strong>${item.name}</strong>
                    <span>$${itemTotal.toFixed(2)}</span>
                </div>

                <div class="cart-item-row mt-2">

                    <div class="qty-controls">
                        <span class="qty-btn" onclick="decreaseQty('${id}')">-</span>
                        <strong>${item.qty}</strong>
                        <span class="qty-btn" onclick="increaseQty('${id}')">+</span>
                    </div>

                    <span class="remove-btn" onclick="removeItem('${id}')">&times;</span>
                </div>
            </li>
        `;
    });

    totalBox.innerText = total.toFixed(2);
}

// Increase qty
function increaseQty(id) {
    cart[id].qty++;
    saveCart();
    renderCart();
}

// Decrease qty
function decreaseQty(id) {
    if (cart[id].qty > 1) {
        cart[id].qty--;
    } else {
        delete cart[id];
    }
    saveCart();
    renderCart();
}

// Remove item
function removeItem(id) {
    delete cart[id];
    saveCart();
    renderCart();
}

// Add to Cart
document.querySelectorAll(".order-btn").forEach(btn => {
    btn.addEventListener("click", function () {

        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        const id = name.replace(/\s+/g, "_").toLowerCase();

        if (!cart[id]) {
            cart[id] = {
                name: name,
                price: price,
                qty: 1
            };
        } else {
            cart[id].qty++;
        }

        saveCart();
        renderCart();
    });
});

// Initialize cart on load
renderCart();
</script>
