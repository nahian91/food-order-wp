<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('FD_Orders_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

    class FD_Orders_List_Table extends WP_List_Table {

        function __construct() {
            parent::__construct([
                'singular' => 'food_order',
                'plural'   => 'food_orders',
                'ajax'     => false
            ]);
        }

        function get_columns() {
            return [
                'cb'       => '<input type="checkbox"/>',
                'order_id' => 'Order ID',
                'items'    => 'Items Ordered',
                'total'    => 'Total Price (€)',
                'status'   => 'Status',
                'date'     => 'Date',
                'actions'  => 'Actions'
            ];
        }

        function column_cb($item) {
            return sprintf('<input type="checkbox" name="order[]" value="%s"/>', $item->ID);
        }

        function column_order_id($item) { return $item->ID; }

        function column_items($item) {
            $items = get_post_meta($item->ID, 'items', true);
            if (!$items || !is_array($items)) return '-';
            $out = '';
            foreach ($items as $i) {
                $name  = $i['name'] ?? '-';
                $qty   = intval($i['qty'] ?? 1);
                $price = floatval($i['price'] ?? 0);
                $out .= esc_html($name) . ' × ' . $qty . ' - €' . number_format($price, 2) . '<br>';
            }
            return $out;
        }

        function column_total($item) {
            $total = get_post_meta($item->ID, 'total_price', true);
            return $total ? '€' . number_format(floatval($total), 2) : '-';
        }

        function column_status($item) {
            return ucfirst(get_post_meta($item->ID, 'status', true) ?: 'Pending');
        }

        function column_date($item) {
            return get_the_date('Y-m-d H:i', $item->ID);
        }

        function column_actions($item) {
            $edit_link   = admin_url('admin.php?page=fd_edit_order&order_id=' . $item->ID);
            $view_link   = admin_url('admin.php?page=fd_view_order&order_id=' . $item->ID);
            $delete_link = wp_nonce_url(
                admin_url('admin-post.php?action=fd_delete_order&order_id=' . $item->ID),
                'fd_delete_order_' . $item->ID
            );
            $print_link = admin_url('admin.php?page=fd_print_order&order_id=' . $item->ID);

            return '
                <a href="'.$edit_link.'" class="button">Edit</a>
                <a href="'.$view_link.'" class="button">View</a>
                <a href="'.$delete_link.'" class="button button-link-delete" onclick="return confirm(\'Are you sure?\')">Delete</a>
                <a href="'.$print_link.'" class="button" target="_blank">Print</a>
            ';
        }

        function prepare_items() {
            $columns  = $this->get_columns();
            $hidden   = [];
            $sortable = ['order_id' => ['order_id', true], 'date' => ['date', true]];
            $this->_column_headers = [$columns, $hidden, $sortable];

            $per_page     = 20;
            $current_page = $this->get_pagenum();
            $total_items  = wp_count_posts('food_order')->publish ?? 0;

            $this->items = get_posts([
                'post_type'   => 'food_order',
                'numberposts' => $per_page,
                'offset'      => ($current_page - 1) * $per_page,
                'orderby'     => 'date',
                'order'       => 'DESC'
            ]);

            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page
            ]);
        }
    }
}
