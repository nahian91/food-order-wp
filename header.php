<?php
/**
 * The header for our theme
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

    <?php 
    // Only execute on the home page
    if ( is_front_page() && function_exists('get_afd_restaurant_status') ) {
        $store_status = get_afd_restaurant_status(); 

        // Show modal if the store is strictly CLOSED
        if ( isset($store_status['status']) && $store_status['status'] === 'closed' ) : 
            $schedule     = get_option('afd_schedule', []);
            $current_day  = current_datetime()->format('D');
            $day_settings = isset($schedule[$current_day]) ? $schedule[$current_day] : null;
            $is_off_day   = ( ! $day_settings || empty($day_settings['enabled']) );
            
            $menu_url = home_url('/menu/'); 
            ?>
            <div id="afd-closed-modal" class="afd-modal-overlay">
                <div class="afd-modal-box">
                    <div class="afd-modal-header">
                        <div class="closed-icon" style="background: #f59e0b;">🕒</div>
                        <h2><?php esc_html_e( "We're Currently Closed", 'text-domain' ); ?></h2>
                    </div>
                    <div class="afd-modal-body">
                        <p class="main-msg"><?php echo nl2br( esc_html( $store_status['message'] ) ); ?></p>
                        
                        <div class="time-info">
                            <?php if ($is_off_day) : ?>
                                <span><?php esc_html_e( 'Status', 'text-domain' ); ?></span>
                                <p class="off-day-text"><?php esc_html_e( 'Today is our Day Off', 'text-domain' ); ?></p>
                            <?php else : ?>
                                <span><?php esc_html_e( "Today's Operating Hours", 'text-domain' ); ?></span>
                                <p><?php echo esc_html( $day_settings['open'] ); ?> — <?php echo esc_html( $day_settings['close'] ); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <p style="font-size: 13px; color: #64748b; margin-top: 15px;">
                            <?php echo wp_kses_post( __( 'You can still place a <strong>Pre-Order</strong> for our next opening time!', 'text-domain' ) ); ?>
                        </p>
                    </div>
                    <div class="afd-modal-footer" style="display: flex; gap: 10px;">
                        <button onclick="document.getElementById('afd-closed-modal').remove()" class="afd-btn-close" style="background: #e2e8f0; color: #475569; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            <?php esc_html_e( 'Close', 'text-domain' ); ?>
                        </button>
                        
                        <a href="<?php echo esc_url( $menu_url ); ?>" class="afd-btn-preorder" style="background: #ef4444; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; flex-grow: 1; text-align: center;">
                            <?php esc_html_e( 'Pre-Order Now', 'text-domain' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; 
    } ?>

    <div class="top-bar-area top-bar-style-one bg-theme text-light">
        <div class="container">
            <div class="row align-center">
                <div class="col-lg-7">
                    <?php 
                        $header_phone = get_field('header_phone', 'option');
                        $header_email = get_field('header_email', 'option');
                    ?>
                    <ul class="item-flex">
                        <li><a href="tel:<?php echo $header_phone;?>"><img src="<?php echo get_template_directory_uri();?>/assets/img/icon/10.png" alt="Icon"> Phone: <?php echo $header_phone;?></a></li>
                        <li><a href="mailto:<?php echo $header_email;?>"><img src="<?php echo get_template_directory_uri();?>/assets/img/icon/11.png" alt="Icon"> Email: <?php echo $header_email;?></a></li>
                    </ul>
                </div>
                <div class="col-lg-5 text-end">
                    <div class="social">
                        <?php 
                        $header_socials = get_field('header_socials', 'option');
                        if ( $header_socials ) : ?>
                            <ul>
                                <?php foreach ( $header_socials as $social ) : 
                                    $icon_data = $social['header_social_icon'];
                                    $url = $social['header_social_icon_url'];
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url($url); ?>" target="_blank">
                                            <span class="dashicons <?php echo esc_attr($icon_data['value']); ?>"></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
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
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="navbar-menu">
    <div class="navbar-mobile-header d-lg-none">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
            <i class="fa fa-times"></i>
        </button>
    </div>

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
                            <a href="<?php echo esc_url(home_url('/checkout')); ?>">
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
    document.addEventListener('DOMContentLoaded', syncHeaderCart);
    window.addEventListener('storage', syncHeaderCart);
    </script>