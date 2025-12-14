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
                        <ul class="opening-list">
                            <li>
                                Mon  -  Fri <span class="text-end">8:00 AM  -  6:00 PM</span>
                            </li>
                            <li>
                                Saturday <span class="text-end">9:00 AM  -  5:00 PM</span>
                            </li>
                        </ul>                  
                    </div>
                </div>
                <!-- End Singel Item -->

                <!-- Singel Item -->
                <div class="col-lg-2 col-md-6 mt-50 footer-item pl-50 pl-md-15 pl-xs-15">
                    <div class="f-item link">
                        <h4 class="widget-title">Explore</h4>
                        <ul>
                            <li>
                                <a href="about-us.html">Compnay Profile</a>
                            </li>
                            <li>
                                <a href="about-us.html">About</a>
                            </li>
                            <li>
                                <a href="contact-us.html">Help Center</a>
                            </li>
                            <li>
                                <a href="contact-us.html">Career</a>
                            </li>
                            <li>
                                <a href="about-us.html">Features</a>
                            </li>
                            <li>
                                <a href="contact-us.html">Contact</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- End Singel Item -->

                <!-- Singel Item -->
                <div class="col-lg-3 col-md-6 footer-item  mt-50">
                    <div class="f-item contact">
                        <h4 class="widget-title">Contact Info</h4>
                        <ul class="contact-widget">
                            <li>
                                <div class="icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="content">
                                    175 10h Street, Office 375 Berlin, De 21562
                                </div>
                            </li>
                            <li>
                                <div class="icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="content">
                                    <a href="tel:2151234567">+123 34598768</a> <br> <a href="tel:2151234567">+554 34598734</a>
                                </div>
                            </li>
                            <li>
                                <div class="icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="content">
                                    <a href="mailto:name@email.com">food@restan.com</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- End Singel Item -->

                <!-- Singel Item -->
                <div class="col-lg-4 col-md-6 footer-item mt-50">
                    <div class="f-item newsletter">
                        <h4 class="widget-title">Newsletter</h4>
                        <p>
                            Join our subscribers list to get the latest news and special offers.
                        </p>
                        <form action="#">
                            <input type="email" placeholder="Your Email" class="form-control" name="email">
                            <button type="submit"> Subscribe <i class="fas fa-long-arrow-right"></i></button>  
                        </form>
                        <div class="footer-socila-items mt-30">
                            <h4>Social Meida: </h4>
                            <ul class="footer-social">
                                <li>
                                    <a href="#">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
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
