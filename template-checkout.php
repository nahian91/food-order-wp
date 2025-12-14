<?php

/*
Template Name: Checkout
*/

get_header();?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Checkout</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Checkout</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Billing Details -->
        <div class="col-lg-6 mb-4">
            <h4>Billing Details</h4>
            <form>
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

        <!-- Order Summary -->
        <div class="col-lg-6 mb-4">
            <h4>Order Summary</h4>
            <div class="card">
                <div class="card-body">
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Chicken Alfredo x 1
                            <span>$20</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Fish & Chips x 2
                            <span>$72</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Muffins x 3
                            <span>$66</span>
                        </li>
                    </ul>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subtotal</span>
                            <strong>$158</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Delivery</span>
                            <strong>$10</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total</span>
                            <strong>$168</strong>
                        </li>
                    </ul>

                    <button class="btn btn-success w-100 mt-4">Place Order</button>
                </div>
            </div>
        </div>
    </div>
</div>

  <?php get_footer();?>