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
    <style>
        /* Header & Cart Styles */
        .attr-nav-flex { display: flex; align-items: center; list-style: none; margin: 0; padding: 0; }
        .attr-nav-flex li { margin-left: 20px; position: relative; display: flex; align-items: center; }
        .attr-nav-flex a { color: #333; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .cart-icon-wrapper { position: relative; }
        #header-cart-count { position: absolute; top: -10px; right: -12px; background: #d63638; color: #fff; font-size: 10px; height: 18px; width: 18px; line-height: 18px; text-align: center; border-radius: 50%; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .user-welcome-text { font-size: 13px; color: #666; }
        @media (max-width: 991px) { .attr-nav-flex { margin-top: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; } }

        /* Modal Styles */
        .afd-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 999999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .afd-modal-box { background: #fff; width: 95%; max-width: 400px; border-radius: 15px; overflow: hidden; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.4); animation: afdFadeIn 0.3s ease-out forwards; }
        @keyframes afdFadeIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .afd-modal-header { background: #ffeded; padding: 25px; border-bottom: 1px solid #fbc4c4; }
        .closed-icon { width: 50px; height: 50px; background: #f56c6c; color: #fff; font-size: 30px; font-weight: bold; line-height: 50px; border-radius: 50%; margin: 0 auto 15px; }
        .afd-modal-header h2 { margin: 0; color: #f56c6c; font-size: 22px; }
        .afd-modal-body { padding: 30px 20px; }
        .main-msg { font-size: 16px; color: #374151; margin-bottom: 25px; font-weight: 500; line-height: 1.5; }
        .time-info { background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; padding: 15px; }
        .time-info span { font-size: 11px; text-transform: uppercase; color: #909399; font-weight: 600; display: block; margin-bottom: 5px; }
        .time-info p { margin: 0; font-weight: bold; color: #1f2f3d; font-size: 18px; }
        .off-day-text { color: #d63638 !important; }

        .afd-modal-footer { padding: 20px; background: #fafafa; }
        .afd-btn-ok { background: #111827; color: #fff; border: none; padding: 14px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; transition: background 0.2s; }
        .afd-btn-ok:hover { background: #000; }
    </style>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

    <?php 
    if (function_exists('get_afd_restaurant_status')) {
        $store_status = get_afd_restaurant_status(); 

        // Show modal if the store is strictly CLOSED
        if ($store_status['status'] === 'closed') : 
            $schedule     = get_option('afd_schedule', []);
            $current_day  = current_datetime()->format('D');
            $day_settings = isset($schedule[$current_day]) ? $schedule[$current_day] : null;
            $is_off_day   = (!$day_settings || empty($day_settings['enabled']));
            ?>
            <div id="afd-closed-modal" class="afd-modal-overlay">
                <div class="afd-modal-box">
                    <div class="afd-modal-header">
                        <div class="closed-icon">!</div>
                        <h2>Closed Now</h2>
                    </div>
                    <div class="afd-modal-body">
                        <p class="main-msg"><?php echo nl2br(esc_html($store_status['message'])); ?></p>
                        <div class="time-info">
                            <?php if ($is_off_day) : ?>
                                <span>Status</span>
                                <p class="off-day-text">Today is our Day Off</p>
                            <?php else : ?>
                                <span>Today's Operating Hours</span>
                                <p><?php echo esc_html($day_settings['open']); ?> — <?php echo esc_html($day_settings['close']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="afd-modal-footer">
                        <button onclick="document.getElementById('afd-closed-modal').remove()" class="afd-btn-ok">Understood</button>
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