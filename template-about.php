<?php

/*
Template Name: About
*/

get_header();?>    

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>About Us</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>About Us</li>
                </ul>
            </div>
        </div>
    </div>
</div>

    <!-- Start About
    ============================================= -->
    <div class="about-style-one-area default-padding">
        <div class="container">
            <div class="row align-center">
                <div class="col-lg-6">
                    <div class="thumb-style-one">
                        <img class="wow fadeInUp" src="<?php echo get_template_directory_uri();?>/assets/img/about/1.jpg" alt="Image Not Found">
                        <div class="contact-card-one wow fadeInLeft" data-wow-delay="200ms">
                            <a href="tel:+442084432500">
                                <div class="icon">
                                    <i class="fa fa-phone"></i>
                                </div>
                                <div class="info">
                                    <span>HOTLINE 24/7</span>
                                    <h4>+442084432500</h4>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 offset-lg-1">
                    <div class="about-style-one-info">
                        <h4 class="sub-heading">About Spice of India</h4>
                        <h2 class="title split-text">A Journey Through the Heart of Authentic Indian Flavors</h2>
                        <div class="content mt-50">
                            <p class="split-text">
                                Welcome to Spice of India, where every dish tells a story of tradition, passion, and the vibrant culture of the East. Located in the heart of the community, we are dedicated to bringing you the true essence of Indian culinary arts, prepared with the finest ingredients and a pinch of love.
                            </p>
                            <p>At Spice of India, we believe that great food starts with authenticity. Our chefs draw inspiration from the diverse regions of India—from the robust, tandoori-grilled delights of the North to the aromatic, coconut-infused curries of the South. We don't just cook; we craft experiences.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End About -->
     
   <?php get_footer();?>