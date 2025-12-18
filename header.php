<?php
/**
 * The header for our theme
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
    <style>
        /* Custom Header Icon Styles */
        .attr-nav-flex { display: flex; align-items: center; list-style: none; margin: 0; padding: 0; }
        .attr-nav-flex li { margin-left: 20px; position: relative; display: flex; align-items: center; }
        .attr-nav-flex a { color: #333; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        
        /* Cart Badge */
        .cart-icon-wrapper { position: relative; }
        #header-cart-count {
            position: absolute;
            top: -10px;
            right: -12px;
            background: #d63638;
            color: #fff;
            font-size: 10px;
            height: 18px;
            width: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .user-welcome-text { font-size: 13px; color: #666; }
        @media (max-width: 991px) {
            .attr-nav-flex { margin-top: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        }
    </style>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

    <div class="radio-btn-light">
        <div class="radio-inner-light"></div>
    </div>

    <div id="preloader">
        <div id="restan-preloader" class="restan-preloader">
            <div class="animation-preloader">
                <div class="spinner"></div>
            </div>
            <div class="loader">
                <div class="row">
                    <div class="col-3 loader-section section-left"><div class="bg"></div></div>
                    <div class="col-3 loader-section section-left"><div class="bg"></div></div>
                    <div class="col-3 loader-section section-right"><div class="bg"></div></div>
                    <div class="col-3 loader-section section-right"><div class="bg"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="top-bar-area top-bar-style-one bg-theme text-light">
        <div class="container">
            <div class="row align-center">
                <div class="col-lg-7">
                    <ul class="item-flex">
                        <li><a href="tel:+4733378901"><img src="<?php echo get_template_directory_uri();?>/assets/img/icon/10.png" alt="Icon"> Phone: +4733378901</a></li>
                        <li><a href="mailto:food@restan.com"><img src="<?php echo get_template_directory_uri();?>/assets/img/icon/11.png" alt="Icon"> Email: food@restan.com</a></li>
                    </ul>
                </div>
                <div class="col-lg-5 text-end">
                    <div class="social">
                        <ul>
                            <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                            <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                            <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <header>
        <nav class="navbar mobile-sidenav navbar-sticky navbar-default validnavs dark on no-full">
            <div class="container d-flex justify-content-between align-items-center">            

                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                        <i class="fa fa-bars"></i>
                    </button>
                    <a class="navbar-brand" href="<?php echo site_url();?>">
                        <img src="<?php echo get_template_directory_uri();?>/assets/img/logo.png" class="regular-img logo" alt="Logo">
                        <img src="<?php echo get_template_directory_uri();?>/assets/img/logo.png" class="logo logo-scrolled" alt="Logo">
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="navbar-menu">
                    <?php
                        wp_nav_menu( array(
                            'theme_location' => 'menu-1',
                            'menu_class'     => 'nav navbar-nav navbar-right',
                            'container'      => false,
                            'fallback_cb'    => false,
                        ) );
                    ?>
                </div>

                <div class="attr-right">
                    <ul class="attr-nav-flex">
                        
                        <li class="cart-icon-wrapper">
                            <a href="<?php echo esc_url(home_url('/checkout')); ?>" title="View Cart">
                                <i class="fas fa-shopping-basket"></i>
                                <span id="header-cart-count">0</span>
                            </a>
                        </li>

                        <li>
                            <?php if(is_user_logged_in()): ?>
                                <a href="<?php echo esc_url(home_url('/account/')); ?>">
                                    <i class="fas fa-user-circle"></i>
                                    <span class="user-welcome-text d-none d-lg-inline">Hi, <?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                                </a>
                                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" style="margin-left:10px;" title="Logout">
                                    <i class="fas fa-power-off" style="font-size:14px; color:#d63638;"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo esc_url(home_url('/login/')); ?>">
                                    <i class="fas fa-user-lock"></i> <span class="d-none d-lg-inline">Sign In</span>
                                </a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>

            </div>   
        </nav>
    </header>

    <script>
    /**
     * Global Header Cart Sync
     * This ensures the number updates even if the user refreshes or changes pages
     */
    function syncHeaderCart() {
        try {
            const cart = JSON.parse(localStorage.getItem('fd_cart_save')) || [];
            const count = cart.reduce((total, item) => total + item.qty, 0);
            const badge = document.getElementById('header-cart-count');
            if (badge) {
                badge.innerText = count;
                badge.style.display = count > 0 ? 'block' : 'none';
            }
        } catch (e) { console.error("Cart sync error", e); }
    }

    // Run on load
    document.addEventListener('DOMContentLoaded', syncHeaderCart);
    
    // Listen for storage changes (if user has two tabs open)
    window.addEventListener('storage', syncHeaderCart);

    // If your shortcode script is on the same page, ensure it calls syncHeaderCart()
    // inside its updateCart function.
    </script>