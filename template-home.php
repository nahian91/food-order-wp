<?php

/*
Template Name: Home
*/

get_header();?>

   <div class="banner-area banner-style-two navigation-circle navigation-dark overflow-hidden">
    <div class="banner-fade swiper">
        <div class="swiper-wrapper">

            <?php 
            $home_banners = get_field('home_banner', 'option');
            
            if ($home_banners) :
                foreach ($home_banners as $banner) : 
                    // Mapping variables from the ACF array
                    $title       = $banner['home_banner_title'];
                    $subtitle    = $banner['home_banner_subtitle'];
                    $description = $banner['home_banner_description'];
                    $button_text = $banner['home_banner_button_text'];
                    $button_url  = $banner['home_banner_button_url'];
                    $image_url   = $banner['home_banner_image']['url']; // Accessing the URL from the image array
            ?>

                <div class="swiper-slide">
                    <div class="container">
                        <div class="content">
                            <div class="row align-center">
                                <div class="col-lg-6">
                                    <h2><?php echo esc_html($title); ?></h2>
                                    <h4><?php echo wp_kses_post($subtitle); ?></h4>
                                    <p><?php echo esc_html($description); ?></p>
                                    
                                    <?php if ($button_url) : ?>
                                        <div class="button mt-40">
                                            <a class="btn btn-theme btn-md animation" href="<?php echo esc_url($button_url); ?>">
                                                <?php echo esc_html($button_text ? $button_text : 'Order Now'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-6">
                                    <div class="thumb">
                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/shape/1.png" alt="Shape">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                endforeach; 
            endif; 
            ?>

        </div>

        <div class="swiper-nav-left">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>

    </div>  
</div>

    <?php echo do_shortcode('[fd_food_items]'); ?>

    <?php // echo do_shortcode('[food_categories]'); ?>

    <div class="testimonial-style-one-area default-padding bg-gray bg-cover text-center" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/shape/4.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="testimonial-style-one-carousel swiper">
                    <div class="swiper-wrapper">
                        
                        <?php 
                        $reviews = get_field('reviews', 'option'); 
                        if( $reviews ):
                            foreach($reviews as $review):
                                $review_title       = $review['review_title'];
                                $review_description = $review['review_description'];
                                $review_image       = $review['review_image'];
                                ?>
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
                                                    <h4><?php echo esc_html($review_title); ?></h4>
                                                </div>
                                                <p>
                                                    "<?php echo esc_html($review_description); ?>"
                                                </p>
                                                <div class="tm-proivder-thumb">
                                                    <img src="<?php echo esc_url($review_image); ?>" alt="Review Image">
                                                    <img src="<?php echo esc_url($review_image); ?>" alt="Review Image">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php 
                            endforeach; 
                        endif; 
                        ?>

                    </div>

                    <div class="testimonial-pagination">
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <?php get_footer();?>