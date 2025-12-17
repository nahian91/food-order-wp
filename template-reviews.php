<?php

/*
Template Name: Reviews
*/

get_header();?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Reviews</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Reviews</li>
                </ul>
            </div>
        </div>
    </div>
</div>


<!-- Start Testimonial 
    ============================================= -->
    <div class="testimonial-style-one-area default-padding bg-gray bg-cover text-center" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/shape/4.jpg);">
        <div class="container">
            <div class="row">
                <?php 
                    $reviews = get_field('reviews', 'option');               
                ?>
                <?php 
                    foreach($reviews as $review) {
                        $review_title = $review['review_title'];
                        $review_description = $review['review_description'];
                        $review_image = $review['review_image'];
                        ?>
                    <div class="col-lg-6">
                    <div class="testimonial-style-one-carousel">
                        <!-- Additional required wrapper -->
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
                                        <h4><?php echo $review_title;?></h4>
                                    </div>
                                    <p><?php echo $review_description;?></p>
                                    <div class="tm-proivder-thumb">
                                        <img src="<?php echo $review_image;?>" alt="">
                                        <img src="<?php echo $review_image;?>" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br><br><br>
                    <!-- End Single item -->
                </div>
                        <?php
                    }
                ?>
            </div>
        </div>
    </div>
    <!-- End Testimonial Area -->

<?php get_footer();?>