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


<?php get_footer();?>