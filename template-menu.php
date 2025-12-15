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

<?php echo do_shortcode('[fd_food_items]'); ?>

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
