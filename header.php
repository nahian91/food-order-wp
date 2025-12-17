<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Awesome_Food_Delivery
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

    <div class="radio-btn-light">
        <div class="radio-inner-light"></div>
    </div>

    <!-- Start Preloader 
    ============================================= -->
    <div id="preloader">
        <div id="restan-preloader" class="restan-preloader">
            <div class="animation-preloader">
                <div class="spinner"></div>
            </div>
            <div class="loader">
                <div class="row">
                    <div class="col-3 loader-section section-left">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-left">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-right">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-right">
                        <div class="bg"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Preloader -->

    <!-- Start Header Top 
    ============================================= -->
    <div class="top-bar-area top-bar-style-one bg-theme text-light">
        <div class="container">
            <div class="row align-center">
                <div class="col-lg-7">
                    <ul class="item-flex">
                        <li>
                             <a href="tel:+4733378901"> 
                                <img src="<?php echo get_template_directory_uri();?>/assets/img/icon/10.png" alt="Icon"> Phone: +4733378901
                            </a>
                        </li>
                        <li>
                            <a href="mailto:name@email.com">
                                <img src="<?php echo get_template_directory_uri();?>/assets/img/icon/11.png" alt="Icon"> Email: food@restan.com
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-5 text-end">
                    <div class="item-flex">
                        <div class="social">
                            <ul>
                                <li>
                                    <a href="#">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fab fa-youtube"></i>
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
            </div>
        </div>
    </div>
    <!-- End Header Top -->

    <!-- Header 
    ============================================= -->
    <header>
        <!-- Start Navigation -->
        <nav class="navbar mobile-sidenav navbar-sticky navbar-default validnavs dark on no-full">

            <!-- Start Top Search -->
            <div class="top-search">
                <div class="container-xl">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search">
                        <span class="input-group-addon close-search"><i class="fa fa-times"></i></span>
                    </div>
                </div>
            </div>
            <!-- End Top Search -->


            <div class="container d-flex justify-content-between align-items-center">            

                <!-- Start Header Navigation -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                        <i class="fa fa-bars"></i>
                    </button>
                    <a class="navbar-brand" href="<?php echo site_url();?>">
                        <img src="<?php echo get_template_directory_uri();?>/assets/img/logo.png" class="regular-img logo logo-display" alt="Logo">
                        <img src="<?php echo get_template_directory_uri();?>/assets/img/logo-light.png" class="dark-img logo logo-display" alt="Logo">
                        <img src="<?php echo get_template_directory_uri();?>/assets/img/logo.png" class="logo logo-scrolled" alt="Logo">
                    </a>
                </div>
                <!-- End Header Navigation -->

                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="navbar-menu">

                    <img src="<?php echo get_template_directory_uri();?>/assets/img/logo.png" alt="Logo">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                        <i class="fa fa-times"></i>
                    </button>
                    
                    <?php
wp_nav_menu( array(
    'theme_location' => 'menu-1',
    'menu_class'     => 'nav navbar-nav navbar-right', // your existing classes
    'container'      => false, // remove <div> wrapper
    'fallback_cb'    => false, // no fallback menu
) );
?>
                </div><!-- /.navbar-collapse -->

                <?php
if(is_user_logged_in()){
    $current_user = wp_get_current_user();
    echo '<span>Welcome, ' . esc_html($current_user->display_name) . '</span>';
    echo ' | <a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a>';
} else {
    echo '<a href="' . esc_url(home_url('/login/')) . '">Sign In</a>';
}
?>

            </div>   

        </nav>
        <!-- End Navigation -->
    </header>
    <!-- End Header -->