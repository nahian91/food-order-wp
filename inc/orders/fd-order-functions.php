<?php
if (!defined('ABSPATH')) exit;

function fd_delete_order() {
    if (!wp_verify_nonce($_GET['_wpnonce'],'fd_delete_order_'.$_GET['order_id'])) {
        wp_die('Unauthorized');
    }
    wp_delete_post(intval($_GET['order_id']),true);
    wp_redirect(admin_url('admin.php?page=fd_orders'));
    exit;
}

function fd_register_admin_pages() {
    add_submenu_page(null,'Orders','Orders','manage_options','fd_orders','fd_orders_tab');
    add_submenu_page(null,'Edit Order','Edit Order','manage_options','fd_edit_order','fd_edit_order_page');
    add_submenu_page(null,'View Order','View Order','manage_options','fd_view_order','fd_view_order_page');
    add_submenu_page(null,'Print Order','Print Order','manage_options','fd_print_order','fd_print_order_page');
}
