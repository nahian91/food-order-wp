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
 * Theme-based Food Ordering System
 * Custom Table Implementation
 */

if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# 1. Register CPTs and Taxonomy
--------------------------------------------------------------*/
add_action('init', function(){

    // Food Items (Dishes)
    register_post_type('food_item', [
        'labels' => ['name'=>'Food Items','singular_name'=>'Food Item'],
        'public' => false, 'show_ui' => false, 'supports' => ['title','editor','thumbnail'],
    ]);

    // Customers (Optional Profile CPT)
    register_post_type('food_customer', [
        'labels' => ['name'=>'Customers','singular_name'=>'Customer'],
        'public' => false, 'show_ui' => false, 'supports' => ['title','editor'],
    ]);

    // Categories
    register_taxonomy('food_category','food_item',[
        'labels' => ['name'=>'Food Categories','singular_name'=>'Food Category'],
        'hierarchical' => true,
        'show_ui' => false
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
        'fd_main_page', // This function must exist to render the page
        'dashicons-carrot',
        20
    );
});

/*--------------------------------------------------------------
# 1. Database Table Creation (Updated for Split Notes)
--------------------------------------------------------------*/
function afd_create_orders_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';
    $charset_collate = $wpdb->get_charset_collate();

    // Added 'kitchen_notes' and renamed 'notes' to 'delivery_notes' conceptually
    // Note: keeping 'notes' column but treating it as Delivery Notes for compatibility
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        display_id varchar(20) NOT NULL,
        customer_id bigint(20) DEFAULT 0 NOT NULL,
        order_type varchar(50) DEFAULT '' NOT NULL,
        payment_method varchar(30) DEFAULT 'cash' NOT NULL,
        full_name varchar(255) DEFAULT '' NOT NULL,
        email varchar(255) DEFAULT '' NOT NULL,
        phone varchar(50) DEFAULT '' NOT NULL,
        address text NOT NULL,
        kitchen_notes text NOT NULL,
        delivery_notes text NOT NULL,
        scheduled_time varchar(50) DEFAULT 'asap' NOT NULL,
        delay_message text NOT NULL,
        items_json longtext NOT NULL,
        subtotal decimal(10,2) DEFAULT '0.00' NOT NULL,
        service_fee decimal(10,2) DEFAULT '0.00' NOT NULL,
        bag_fee decimal(10,2) DEFAULT '0.00' NOT NULL,
        tip_amount decimal(10,2) DEFAULT '0.00' NOT NULL,
        delivery_fee decimal(10,2) DEFAULT '0.00' NOT NULL,
        total_price decimal(10,2) DEFAULT '0.00' NOT NULL,
        order_status varchar(20) DEFAULT 'pending' NOT NULL,
        order_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY display_id (display_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('admin_init', 'afd_create_orders_table');


/*--------------------------------------------------------------
# 2. Helper: Generate Next Sequential Display ID
--------------------------------------------------------------*/
function afd_generate_unique_display_id() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';

    $today_date  = current_time('Y-m-d');
    $date_prefix = current_time('Ymd');

    $count_today = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(id) FROM $table_name WHERE DATE(order_date) = %s",
            $today_date
        )
    );

    $new_sequence = intval($count_today) + 1;
    return $date_prefix . '-' . str_pad($new_sequence, 3, '0', STR_PAD_LEFT);
}


/*--------------------------------------------------------------
# 3. Helper: Insert Custom Order (Updated for Split Notes)
--------------------------------------------------------------*/
function fd_insert_custom_order($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';

    $permanent_id = afd_generate_unique_display_id();

    $time_val   = isset($data['scheduledTime']) ? $data['scheduledTime'] : 'asap';
    $delay_msg  = isset($data['delayMessage']) ? $data['delayMessage'] : '';
    $status_val = ($time_val === 'asap') ? 'pending' : 'preorder';

    $inserted = $wpdb->insert(
        $table_name,
        [
            'display_id'     => $permanent_id,
            'customer_id'    => intval($data['user_id']),
            'order_type'     => sanitize_text_field($data['orderType']),
            'payment_method' => sanitize_text_field($data['paymentMethod']),
            'full_name'      => sanitize_text_field($data['fullName']),
            'email'          => sanitize_email($data['email']),
            'phone'          => sanitize_text_field($data['phone']),
            'address'        => sanitize_textarea_field($data['address']),
            // Mapped from Checkout AJAX keys
            'kitchen_notes'  => sanitize_textarea_field($data['kitchen_notes']),
            'delivery_notes' => sanitize_textarea_field($data['delivery_notes']),
            'scheduled_time' => sanitize_text_field($time_val),
            'delay_message'  => sanitize_textarea_field($delay_msg),
            'items_json'     => wp_json_encode($data['cart']),
            'subtotal'       => floatval($data['subtotal']),
            'service_fee'    => floatval($data['service_fee']),
            'bag_fee'        => floatval($data['bag_fee']),
            'tip_amount'     => floatval($data['tip']),
            'delivery_fee'   => floatval($data['delivery']),
            'total_price'    => floatval($data['total']),
            'order_status'   => $status_val,
            'order_date'     => current_time('mysql')
        ],
        [
            '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', 
            '%s', // kitchen_notes
            '%s', // delivery_notes
            '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s'
        ]
    );

    return $inserted ? $permanent_id : false;
}

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

    $active = $_GET['tab'] ?? 'orders';
    ?>

    <div class="awesome-food-delivery <?php echo (isset($_GET['action']) && $_GET['action'] === 'print') ? 'afd-print' : ''; ?>">
        <?php
        // Only show sidebar if NOT a print page
        if (!(isset($_GET['action']) && $_GET['action'] === 'print')) :
        ?>
        <ul class="afd-left-tabs">
            <!-- <li><a class="<?php //echo ($active === 'dashboard') ? 'active' : ''; ?>" href="<?php //echo admin_url('admin.php?page=awesome_food_delivery&tab=dashboard'); ?>">Dashboard</a></li> -->
            <li><a class="<?php echo ($active === 'orders') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=orders'); ?>">Orders</a></li>
            <li><a class="<?php echo ($active === 'items') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=items'); ?>">Items</a></li>
            <li><a class="<?php echo ($active === 'categories') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=categories'); ?>">Categories</a></li>
            <!-- <li><a class="<?php //echo ($active === 'extras') ? 'active' : ''; ?>" href="<?php //echo admin_url('admin.php?page=awesome_food_delivery&tab=extras'); ?>">Extras</a></li> -->
            <li><a class="<?php echo ($active === 'reports') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=reports'); ?>">Reports</a></li>
            <li><a class="<?php echo ($active === 'customers') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=customers'); ?>">Customers</a></li>
            <li><a class="<?php echo ($active === 'settings') ? 'active' : ''; ?>" href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=settings'); ?>">Settings</a></li>
        </ul>
        <?php endif; ?>

            <div class="afd-right-box">
                <?php
                switch($active){
                    //case 'dashboard':  fd_dashboard_tab(); break;
                    case 'orders':     fd_orders_tab(); break;
                    case 'items':      fd_items_tab(); break;
                    case 'categories': fd_category_tab(); break;
                    //case 'extras':     fd_extras_tab(); break;
                    case 'reports':    fd_reports_tab(); break;
                    case 'customers':  fd_customers_tab(); break;
                    case 'settings':   fd_settings_tab(); break;
                }
                ?>
            </div>
    </div>

    <?php
}

// require_once get_template_directory() . '/inc/dashboard.php';
require_once get_template_directory() . '/inc/orders.php';
require_once get_template_directory() . '/inc/items.php';
require_once get_template_directory() . '/inc/categories.php';
//require_once get_template_directory() . '/inc/extras.php';
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

