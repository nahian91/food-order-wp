<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Awesome_Food_Delivery
 */

?>
<!-- Start Footer 
    ============================================= -->
    <footer class="bg-dark footer-style-one text-light">
        <div class="container">
            <div class="row">
                <!-- Singel Item -->
                <div class="col-lg-3 col-md-6 footer-item mt-50">
                    <div class="f-item about">
                        <p>
                            Discover culinary delights recipes and inspiration in our food haven.
                        </p>                 
                    </div>
                </div>
                <!-- End Singel Item -->

                <!-- Singel Item -->
                <div class="col-lg-2 col-md-6 mt-50 footer-item pl-50 pl-md-15 pl-xs-15">
                    <div class="f-item link">
                        <h4 class="widget-title">Quick Links</h4>
                        <?php
wp_nav_menu([
    'theme_location' => 'menu-2']);
?>
                    </div>
                </div>
                <!-- End Singel Item -->

                <!-- Singel Item -->
                <div class="col-lg-3 col-md-6 footer-item  mt-50">
                    <div class="f-item contact">
                        <h4 class="widget-title">Contact Info</h4>
                        <ul>
    <?php 
    $contact_info = get_field('contact_info', 'option');

    if ( $contact_info ) :
        foreach ( $contact_info as $info ) : ?>
            <li class="wow fadeInUp" style="visibility: visible; animation-name: fadeInUp; margin-bottom:30px">
                <div class="icon">
                    <span class="dashicons <?php echo esc_attr($info['contact_info_icon']); ?>"></span>
                </div>
                <div class="content">
                    <h5><?php echo esc_html($info['contact_info_title']); ?></h5>
                    <a href="#"><?php echo esc_html($info['contact_info_description']); ?></a>
                </div>
            </li>
        <?php 
        endforeach; 
    endif; 
    ?>
</ul>
                    </div>
                </div>
                <!-- End Singel Item -->

                <!-- Singel Item -->
                <div class="col-lg-4 col-md-6 footer-item mt-50">
                    <div class="f-item newsletter">
                        <h4 class="widget-title">Join Us</h4>
                        <p>
                            Join our subscribers list to get the latest news and special offers.
                        </p>
                        <form action="#">
                            <input type="email" placeholder="Your Email" class="form-control" name="email">
                            <button type="submit"> Subscribe <i class="fas fa-long-arrow-right"></i></button>  
                        </form>
                    </div>
                </div>
                <!-- End Singel Item -->


            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="row align-center">
                    <div class="col-lg-6">
                        <p>
                            © Copyright 2025. All Rights Reserved
                        </p>
                    </div>
                </div>
            </div>
            <!-- End Footer Bottom -->
        </div>
    </footer>
    <!-- End Footer -->

<?php wp_footer(); ?>

</body>
</html>
