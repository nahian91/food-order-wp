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
                <div class="col-lg-8 offset-lg-2">
                    <div class="site-heading text-center">
                        <h4 class="sub-title">Food Item</h4>
                        <h2 class="title split-text">Our Restaurant Gallery</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="gallery-content-items">
                    <div id="portfolio-grid" class="gallery-items colums-3">
                        
                        <!-- Single Item -->
                        <div class="pf-item wow fadeInUp">
                            <div class="gallery-style-one">
                                <div class="item">
                                    <a href="<?php echo get_template_directory_uri();?>/assets/img/gallery/2.jpg" class="popup-gallery">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/gallery/2.jpg" alt="Image Not Found">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                         <!-- Single Item -->
                        <div class="pf-item wow fadeInUp">
                            <div class="gallery-style-one">
                                <div class="item">
                                    <a href="<?php echo get_template_directory_uri();?>/assets/img/gallery/1.jpg" class="popup-gallery">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/gallery/1.jpg" alt="Image Not Found">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                          <!-- Single Item -->
                        <div class="pf-item wow fadeInUp">
                            <div class="gallery-style-one">
                                <div class="item">
                                    <a href="<?php echo get_template_directory_uri();?>/assets/img/gallery/5.jpg" class="popup-gallery">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/gallery/5.jpg" alt="Image Not Found">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                        <!-- Single Item -->
                        <div class="pf-item wow fadeInUp">
                            <div class="gallery-style-one">
                                <div class="item">
                                    <a href="<?php echo get_template_directory_uri();?>/assets/img/gallery/6.jpg" class="popup-gallery">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/gallery/6.jpg" alt="Image Not Found">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                       <!-- Single Item -->
                        <div class="pf-item wow fadeInUp">
                            <div class="gallery-style-one">
                                <div class="item">
                                    <a href="<?php echo get_template_directory_uri();?>/assets/img/gallery/3.jpg" class="popup-gallery">
                                        <img src="<?php echo get_template_directory_uri();?>/assets/img/gallery/3.jpg" alt="Image Not Found">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Food Gallery -->

<?php get_footer();?>