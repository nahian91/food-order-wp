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

<?php 
    $about_subtitle = get_field('about_subtitle', 'option');
    $about_title = get_field('about_title', 'option');
    $about_image = get_field('about_image', 'option');
    $about_description = get_field('about_description', 'option');
    $about_button_title = get_field('about_button_title', 'option');
    $about_button_text = get_field('about_button_text', 'option');
?>

    <!-- Start About
    ============================================= -->
    <div class="about-style-one-area default-padding">
        <div class="container">
            <div class="row align-center">
                <div class="col-lg-6">
                    <div class="thumb-style-one">
                        <img class="wow fadeInUp" src="<?php echo esc_url($about_image['url']);?>" alt="<?php echo esc_html($about_title);?>">
                        <div class="contact-card-one wow fadeInLeft" data-wow-delay="200ms">
                            <a href="tel:<?php echo esc_attr($about_button_text);?>">
                                <div class="icon">
                                    <i class="fa fa-phone"></i>
                                </div>
                                <div class="info">
                                    <span><?php echo esc_html($about_button_title);?></span>
                                    <h4><?php echo esc_html($about_button_text);?></h4>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 offset-lg-1">
                    <div class="about-style-one-info">
                        <h4 class="sub-heading"><?php echo esc_html($about_subtitle);?></h4>
                        <h2 class="title split-text"><?php echo esc_html($about_title);?></h2>
                        <div class="content mt-50">
                            <p class="split-text">
                                <?php echo esc_html($about_description);?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End About -->
     
   <?php get_footer();?>