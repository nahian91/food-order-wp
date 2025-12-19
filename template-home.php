<?php

/*
Template Name: Home
*/

get_header();?>

    <!-- Start Banner Area 
    ============================================= -->
    <div class="banner-area banner-style-two navigation-circle navigation-dark overflow-hidden">
        <!-- Slider main container -->
        <div class="banner-fade swiper">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">

                <!-- Single Item -->
                <div class="swiper-slide">
                    <div class="container">
                        <div class="content">
                            <div class="row align-center">
                                <div class="col-lg-6">
                                    <h2>Super deal Special lunch</h2>
                                    <h4>Purchase today. just <strong>$65</strong></h4>
                                    <p>
                                        Plan upon yet way get cold spot its week. Almost do am or limits hearts. Resolve parties but why she shewing know.
                                    </p>
                                    <div class="button mt-40">
                                        <a class="btn btn-theme btn-md animation" href="shop.html">Order Now</a>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="thumb">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/illustration/8.png" alt="Image Not Found">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/shape/1.png" alt="Image Not Found">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Single Item -->

                <!-- Single Item -->
                <div class="swiper-slide">
                    <div class="container">
                        <div class="content">
                            <div class="row align-center">
                                <div class="col-lg-6">
                                    <h2>Super deal Special lunch</h2>
                                    <h4>Purchase today. just <strong>$65</strong></h4>
                                    <p>
                                        Plan upon yet way get cold spot its week. Almost do am or limits hearts. Resolve parties but why she shewing know.
                                    </p>
                                    <div class="button mt-40">
                                        <a class="btn btn-theme btn-md animation" href="shop.html">Order Now</a>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="thumb">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/illustration/15.png" alt="Image Not Found">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/shape/1.png" alt="Image Not Found">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Single Item -->

            </div>

            <!-- Navigation -->
            <div class="swiper-nav-left">
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>

        </div>  
    </div>
    <!-- End Banner -->

    <?php echo do_shortcode('[best_sellers_menu]'); ?>

    <?php echo do_shortcode('[food_categories]'); ?>

    <!-- Start Testimonial 
    ============================================= -->
    <div class="testimonial-style-one-area default-padding bg-gray bg-cover text-center" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/shape/4.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="testimonial-style-one-carousel swiper">
                        <!-- Additional required wrapper -->
                        <div class="swiper-wrapper">
                            <!-- Single item -->
                            <div class="swiper-slide">
                                <div class="testimonial-style-one">
                                    <div class="item">
                                        <div class="content">
                                            <div class="tm-review">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="provider">
                                                <h4>Best Chicken Fry</h4>
                                            </div>
                                            <p>
                                                "Thanks to your web agency team for their professional work. The website they created for my business exceeded my expectations, and my clients have given positive feedback about its design and user-friendliness."
                                            </p>
                                            <div class="tm-proivder-thumb">
                                                <img src="<?php echo get_template_directory_uri();?>/assets/img/food/1.jpg" alt="Image Not Found">
                                                <img src="<?php echo get_template_directory_uri();?>/assets/img/team/10.jpg" alt="Image Not Found">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Single item -->
                            <!-- Single item -->
                            <div class="swiper-slide">
                                <div class="testimonial-style-one">
                                    <div class="item">
                                        <div class="content">
                                            <div class="tm-review">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="provider">
                                                <h4>This pizza is so good</h4>
                                            </div>
                                            <p>
                                                "Thanks to your web agency team for their professional work. The website they created for my business exceeded my expectations, and my clients have given positive feedback about its design and user-friendliness."
                                            </p>
                                            <div class="tm-proivder-thumb">
                                                <img src="<?php echo get_template_directory_uri();?>/assets/img/food/6.jpg" alt="Image Not Found">
                                                <img src="<?php echo get_template_directory_uri();?>/assets/img/team/11.jpg" alt="Image Not Found">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Single item -->
                        </div>

                    </div>
                    <!-- Navigation -->
                    <div class="testimonial-pagination">
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Testimonial Area -->

    <?php get_footer();?>