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
    <div class="col-lg-3 col-md-6 footer-item mt-50">
        <div class="f-item about">
            <p>
                Welcome to Spice of India, where every dish tells a story of tradition, passion, and the vibrant culture of the East.
            </p>                
        </div>
    </div>

    <div class="col-lg-2 col-md-6 mt-50 footer-item">
        <div class="f-item link">
            <h4 class="widget-title">Quick Links</h4>
            <?php wp_nav_menu(['theme_location' => 'menu-2']); ?>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 mt-50 footer-item">
        <div class="f-item opening-hours">
            <h4 class="widget-title">Opening Hours</h4>
            <ul style="list-style: none; padding: 0; margin: 0; color: #fff;">
                <?php 
                $schedule = get_option('afd_schedule', []);
                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                
                foreach ($days as $day) : 
                    $data = isset($schedule[$day]) ? $schedule[$day] : null;
                    $is_open = ($data && !empty($data['enabled']));
                    ?>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">
                        <span style="font-weight: 600;"><?php echo $day; ?></span>
                        <span>
                            <?php echo $is_open ? esc_html($data['open'] . ' - ' . $data['close']) : '<span style="color: #ef4444;">Closed</span>'; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 footer-item mt-50">
        <div class="f-item contact">
            <h4 class="widget-title">Contact Info</h4>
            <ul>
                <?php 
                $contact_info = get_field('contact_info', 'option');
                if ( $contact_info ) :
                    foreach ( $contact_info as $info ) : ?>
                        <li class="wow fadeInUp" style="margin-bottom:15px">
                            <div class="content">
                                <h5 style="margin-bottom: 0; font-size: 15px;"><?php echo esc_html($info['contact_info_title']); ?></h5>
                                <small><?php echo esc_html($info['contact_info_description']); ?></small>
                            </div>
                        </li>
                    <?php endforeach; 
                endif; ?>
            </ul>
        </div>
    </div>
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
