<?php

/*
Template Name: Gallery
*/

get_header();?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Gallery</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Gallery</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Start Food Gallery 
    ============================================= -->
    <div class="gallery-style-two-area default-padding">
        <div class="container">
            <div class="row">
                <div class="gallery-content-items">
                    <div id="portfolio-grid" class="gallery-items colums-3">
                        <?php 
                            $gallery = get_field('gallery', 'option');  
                            
                            foreach($gallery as $gal) {
                                ?>
<!-- Single Item -->
                        <div class="pf-item wow fadeInUp">
                            <div class="gallery-style-one">
                                <div class="item">
                                    <a href="#" class="popup-gallery">
                                        <img src="<?php echo $gal['gallery_image'];?>" alt="">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                                <?php
                            }
                        ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Food Gallery -->

<?php get_footer();?>