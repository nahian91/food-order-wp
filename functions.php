<?php
/**
 * Awesome Food Delivery functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Awesome_Food_Delivery
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function awesome_food_delivery_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Awesome Food Delivery, use a find and replace
		* to change 'awesome-food-delivery' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'awesome-food-delivery', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'awesome-food-delivery' ),
			'menu-2' => esc_html__( 'Footer', 'awesome-food-delivery' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'awesome_food_delivery_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'awesome_food_delivery_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function awesome_food_delivery_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'awesome_food_delivery_content_width', 640 );
}
add_action( 'after_setup_theme', 'awesome_food_delivery_content_width', 0 );


function awesome_food_delivery_scripts() {

	$theme_uri = get_template_directory_uri();

	/* =====================
	 Styles
	===================== */
	wp_enqueue_style( 'bootstrap', $theme_uri . '/assets/css/bootstrap.min.css', array(), _S_VERSION );
	wp_enqueue_style( 'font-awesome', $theme_uri . '/assets/css/font-awesome.min.css', array(), _S_VERSION );
	// wp_enqueue_style( 'flaticon', $theme_uri . '/assets/css/flaticon-set.css', array(), _S_VERSION );
	// wp_enqueue_style( 'magnific-popup', $theme_uri . '/assets/css/magnific-popup.css', array(), _S_VERSION );
	wp_enqueue_style( 'swiper', $theme_uri . '/assets/css/swiper-bundle.min.css', array(), _S_VERSION );
	// wp_enqueue_style( 'animate', $theme_uri . '/assets/css/animate.min.css', array(), _S_VERSION );
	// wp_enqueue_style( 'datepicker', $theme_uri . '/assets/css/bootstrap-datepicker3.css', array(), _S_VERSION );
	wp_enqueue_style( 'validnavs', $theme_uri . '/assets/css/validnavs.css', array(), _S_VERSION );
	wp_enqueue_style( 'helper', $theme_uri . '/assets/css/helper.css', array(), _S_VERSION );
	// wp_enqueue_style( 'unit-test', $theme_uri . '/assets/css/unit-test.css', array(), _S_VERSION );
	// wp_enqueue_style( 'shop', $theme_uri . '/assets/css/shop.css', array(), _S_VERSION );
	wp_enqueue_style( 'main-style', $theme_uri . '/assets/css/style.css', array(), _S_VERSION );
	wp_enqueue_style( 'responsive-style', $theme_uri . '/assets/css/responsive.css', array(), _S_VERSION );

	// Theme main style.css
	wp_enqueue_style( 'awesome-food-delivery-style', get_stylesheet_uri(), array(), _S_VERSION );


	/* =====================
	 Scripts
	===================== */

	// Use WordPress built-in jQuery
	wp_enqueue_script( 'jquery' );

	wp_enqueue_script( 'bootstrap', $theme_uri . '/assets/js/bootstrap.bundle.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'appear', $theme_uri . '/assets/js/jquery.appear.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'swiper', $theme_uri . '/assets/js/swiper-bundle.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'progress-bar', $theme_uri . '/assets/js/progress-bar.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'wow', $theme_uri . '/assets/js/wow.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'isotope', $theme_uri . '/assets/js/isotope.pkgd.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'imagesloaded', $theme_uri . '/assets/js/imagesloaded.pkgd.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'magnific-popup', $theme_uri . '/assets/js/magnific-popup.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'count-to', $theme_uri . '/assets/js/count-to.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'nice-select', $theme_uri . '/assets/js/jquery.nice-select.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'ytplayer', $theme_uri . '/assets/js/YTPlayer.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'loopcounter', $theme_uri . '/assets/js/loopcounter.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'validnavs', $theme_uri . '/assets/js/validnavs.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'datepicker', $theme_uri . '/assets/js/bootstrap-datepicker.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'gsap', $theme_uri . '/assets/js/gsap.js', array(), _S_VERSION, true );
	wp_enqueue_script( 'scrolltrigger', $theme_uri . '/assets/js/ScrollTrigger.min.js', array('gsap'), _S_VERSION, true );
	wp_enqueue_script( 'splittext', $theme_uri . '/assets/js/SplitText.min.js', array('gsap'), _S_VERSION, true );

	// Main JS
	wp_enqueue_script( 'awesome-food-delivery-main', $theme_uri . '/assets/js/main.js', array('jquery'), _S_VERSION, true );

	// Comment reply
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'awesome_food_delivery_scripts' );

function fd_admin_styles() {
    $theme_uri = get_template_directory_uri();

    // Enqueue custom admin CSS
    wp_enqueue_style( 
        'awesome-food-delivery-admin-style', // handle
        $theme_uri . '/assets/css/admin-style.css', // path to your custom admin CSS
        array(), // dependencies
        _S_VERSION // version
    );

	wp_enqueue_script( 
        'awesome-food-delivery-admin-script', 
        $theme_uri . '/assets/js/admin-script.js', 
        array('jquery'), // Important: script needs jQuery to run
        _S_VERSION,
        true // Load in footer
    );
}
add_action( 'admin_enqueue_scripts', 'fd_admin_styles' );


// Save ACF JSON
add_filter('acf/settings/save_json', function( $path ) {
    return get_stylesheet_directory() . '/acf-json';
});

// Load ACF JSON
add_filter('acf/settings/load_json', function( $paths ) {
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
});



/**
 * Theme-based Food Ordering System with Items Sub-tabs including Extras
 * Paste into your theme's functions.php
 */

if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# 1. Register CPTs and Taxonomy
--------------------------------------------------------------*/
add_action('init', function(){

    register_post_type('food_item', [
        'labels'=>['name'=>'Food Items','singular_name'=>'Food Item'],
        'public'=>false,'show_ui'=>false,'supports'=>['title','editor','thumbnail'],
    ]);

    register_post_type('food_order', [
        'labels'=>['name'=>'Orders','singular_name'=>'Order'],
        'public'=>false,'show_ui'=>false,'supports'=>['title','editor'],
    ]);

    register_post_type('food_customer', [
        'labels'=>['name'=>'Customers','singular_name'=>'Customer'],
        'public'=>false,'show_ui'=>false,'supports'=>['title','editor'],
    ]);

    register_taxonomy('food_category','food_item',[
        'labels'=>['name'=>'Food Categories','singular_name'=>'Food Category'],
        'hierarchical'=>true,
        'show_ui'=>false
    ]);
});

/*--------------------------------------------------------------
# 2. Admin Menu
--------------------------------------------------------------*/
add_action('admin_menu', function(){
    add_menu_page(
        'Food Delivery',
        'Food Delivery',
        'manage_options',
        'awesome_food_delivery',
        'fd_main_page',
        'dashicons-carrot',
        20
    );
});

/*--------------------------------------------------------------
# Main Page with LEFT Tabs + Right Content
--------------------------------------------------------------*/
function fd_main_page(){

    $tabs = [
        'dashboard'  => 'Dashboard',
        'orders'     => 'Orders',
        'items'      => 'Items',
        'categories' => 'Categories',
        'extras'     => 'Extras',
        'reports'    => 'Reports',
        'customers'  => 'Customers',
        'settings'   => 'Settings' // 1. Added to array
    ];

    $active = $_GET['tab'] ?? 'dashboard';
    ?>

    <div class="awesome-food-delivery">

        <?php
        // Only show sidebar if NOT a print page
        if (!(isset($_GET['action']) && $_GET['action'] === 'print')) :
        ?>
        <ul class="afd-left-tabs">
            <li><a class="<?php echo ($active === 'dashboard') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=dashboard'); ?>">Dashboard</a></li>
            <li><a class="<?php echo ($active === 'orders') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=orders'); ?>">Orders</a></li>
            <li><a class="<?php echo ($active === 'items') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=items'); ?>">Items</a></li>
            <li><a class="<?php echo ($active === 'categories') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=categories'); ?>">Categories</a></li>
            <li><a class="<?php echo ($active === 'extras') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=extras'); ?>">Extras</a></li>
            <li><a class="<?php echo ($active === 'reports') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=reports'); ?>">Reports</a></li>
            <li><a class="<?php echo ($active === 'customers') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=customers'); ?>">Customers</a></li>
            <li><a class="<?php echo ($active === 'settings') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=settings'); ?>">Settings</a></li>
        </ul>
        <?php endif; ?>

            <div class="afd-right-box">
                <?php
                switch($active){
                    case 'dashboard':  fd_dashboard_tab(); break;
                    case 'orders':     fd_orders_tab(); break;
                    case 'items':      fd_items_tab(); break;
                    case 'categories': fd_category_tab(); break;
                    case 'extras':     fd_extras_tab(); break;
                    case 'reports':    fd_reports_tab(); break;
                    case 'customers':  fd_customers_tab(); break;
                    case 'settings':   fd_settings_tab(); break;
                }
                ?>
            </div>
    </div>

    <?php
}

require_once get_template_directory() . '/inc/dashboard.php';
require_once get_template_directory() . '/inc/orders.php';
require_once get_template_directory() . '/inc/items.php';
require_once get_template_directory() . '/inc/categories.php';
require_once get_template_directory() . '/inc/extras.php';
require_once get_template_directory() . '/inc/report.php';
require_once get_template_directory() . '/inc/customers.php';
require_once get_template_directory() . '/inc/settings.php';
require_once get_template_directory() . '/inc/frontend.php';

add_action('admin_head', function() {
    $screen = get_current_screen();

    // Check if we are on your Food Delivery page
    if($screen && $screen->id === 'toplevel_page_awesome_food_delivery') {
        echo '<style>
            /* Hide WP default admin UI */
            #wpadminbar, /* top bar */
            #adminmenu, #adminmenuback, #adminmenuwrap, /* left menu */
            #wpfooter { display: none !important; }
            
            /* Make content full width */
            #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            
            body.wp-admin { background: #f1f1f1; }
        </style>';
    }
});


add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user) {

    // Check if $user is a WP_User object
    if( isset($user->roles) && is_array($user->roles) ) {

        // You can target specific roles if you want
        if( in_array('administrator', $user->roles) || in_array('editor', $user->roles) ) {
            // Redirect to your Food Delivery page
            return admin_url('admin.php?page=awesome_food_delivery');
        }
    }

    // Default redirect for other users
    return $redirect_to;
}, 10, 3);
