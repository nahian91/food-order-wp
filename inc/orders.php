<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Full Orders Manager with Sidebar & Editable Items (EURO)
--------------------------------------------------------------*/

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

        function column_order_id($item) {
            return $item->ID;
        }

        function column_items($item) {
            $items = get_post_meta($item->ID, 'items', true);
            if (!$items || !is_array($items)) return '-';

            $out = '';
            foreach ($items as $i) {
                $name  = $i['name'] ?? '-';
                $qty   = intval($i['qty'] ?? 1);
                $price = floatval($i['price'] ?? 0);
                $out  .= esc_html($name) . ' × ' . $qty . ' - €' . number_format($price, 2) . '<br>';
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

            return '
                <a href="'.$edit_link.'" class="button">Edit</a>
                <a href="'.$view_link.'" class="button">View</a>
                <a href="'.$delete_link.'" class="button button-link-delete" onclick="return confirm(\'Are you sure?\')">Delete</a>
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

/*--------------------------------------------------------------
# Orders Page
--------------------------------------------------------------*/
function fd_orders_tab() {
    echo '<div class="wrap"><h1>Latest Orders</h1>';
    $table = new FD_Orders_List_Table();
    $table->prepare_items();
    echo '<form method="post">';
    $table->display();
    echo '</form></div>';
}

/*--------------------------------------------------------------
# Single Order View / Edit Layout
--------------------------------------------------------------*/
function fd_single_order_layout($order_id, $editable = false) {

    $order = get_post($order_id);
    if (!$order) {
        echo '<div class="notice notice-error"><p>Order not found.</p></div>';
        return;
    }

    $items    = get_post_meta($order_id, 'items', true) ?: [];
    $total    = floatval(get_post_meta($order_id, 'total_price', true) ?: 0);
    $customer = get_post_meta($order_id, 'customer_name', true);
    $status   = get_post_meta($order_id, 'status', true) ?: 'Pending';
    $date     = get_the_date('d M Y H:i', $order_id);

    if ($editable && isset($_POST['update_order'])) {
        $status = sanitize_text_field($_POST['status']);
        update_post_meta($order_id, 'status', $status);

        $new_items = [];
        $total_price = 0;

        foreach ($_POST['items'] as $i) {
            if (!empty($i['remove'])) continue;
            $name  = sanitize_text_field($i['name']);
            $qty   = intval($i['qty']);
            $price = floatval($i['price']);

            if ($name && $qty > 0) {
                $new_items[] = compact('name', 'qty', 'price');
                $total_price += $qty * $price;
            }
        }

        update_post_meta($order_id, 'items', $new_items);
        update_post_meta($order_id, 'total_price', $total_price);

        $items = $new_items;
        $total = $total_price;

        echo '<div class="notice notice-success"><p>Order updated successfully.</p></div>';
    }
    ?>

    <div class="wrap">
        <h1><?php echo $editable ? 'Edit' : 'View'; ?> Order #<?php echo $order_id; ?></h1>

        <p><strong>Customer:</strong> <?php echo esc_html($customer); ?></p>
        <p><strong>Status:</strong> <?php echo esc_html($status); ?></p>
        <p><strong>Date:</strong> <?php echo esc_html($date); ?></p>
        <p><strong>Total:</strong> €<?php echo number_format($total, 2); ?></p>

        <form method="post">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price (€)</th>
                        <?php if ($editable): ?><th>Remove</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $i): ?>
                        <tr>
                            <td>
                                <?php if ($editable): ?>
                                    <input type="text" name="items[<?php echo $index; ?>][name]" value="<?php echo esc_attr($i['name']); ?>">
                                <?php else: echo esc_html($i['name']); endif; ?>
                            </td>
                            <td>
                                <?php if ($editable): ?>
                                    <input type="number" min="1" name="items[<?php echo $index; ?>][qty]" value="<?php echo esc_attr($i['qty']); ?>">
                                <?php else: echo esc_html($i['qty']); endif; ?>
                            </td>
                            <td>
                                <?php if ($editable): ?>
                                    <input type="number" step="0.01" name="items[<?php echo $index; ?>][price]" value="<?php echo esc_attr($i['price']); ?>">
                                <?php else: echo '€' . number_format($i['price'], 2); endif; ?>
                            </td>
                            <?php if ($editable): ?>
                                <td><input type="checkbox" name="items[<?php echo $index; ?>][remove]"></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($editable): ?>
                <p>
                    <select name="status">
                        <option value="Pending" <?php selected($status, 'Pending'); ?>>Pending</option>
                        <option value="Processing" <?php selected($status, 'Processing'); ?>>Processing</option>
                        <option value="Completed" <?php selected($status, 'Completed'); ?>>Completed</option>
                    </select>
                    <input type="submit" name="update_order" class="button button-primary" value="Update Order">
                </p>
            <?php endif; ?>
        </form>

        <p><a href="<?php echo admin_url('admin.php?page=fd_orders'); ?>" class="button">Back</a></p>
    </div>
    <?php
}

/*--------------------------------------------------------------
# Hidden Pages
--------------------------------------------------------------*/
function fd_edit_order_page() {
    if (isset($_GET['order_id'])) {
        fd_single_order_layout(intval($_GET['order_id']), true);
    }
}

function fd_view_order_page() {
    if (isset($_GET['order_id'])) {
        fd_single_order_layout(intval($_GET['order_id']), false);
    }
}

add_action('admin_menu', function() {
    add_submenu_page(null, 'Orders', 'Orders', 'manage_options', 'fd_orders', 'fd_orders_tab');
    add_submenu_page(null, 'Edit Order', 'Edit Order', 'manage_options', 'fd_edit_order', 'fd_edit_order_page');
    add_submenu_page(null, 'View Order', 'View Order', 'manage_options', 'fd_view_order', 'fd_view_order_page');
});

/*--------------------------------------------------------------
# Delete Order
--------------------------------------------------------------*/
add_action('admin_post_fd_delete_order', function() {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'fd_delete_order_' . $_GET['order_id'])) {
        wp_die('Unauthorized');
    }
    wp_delete_post(intval($_GET['order_id']), true);
    wp_redirect(admin_url('admin.php?page=fd_orders'));
    exit;
});
