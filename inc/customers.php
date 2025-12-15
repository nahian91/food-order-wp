<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# View Registered Users and Their Orders - Procedural
--------------------------------------------------------------*/
function fd_customers_tab() {

    // View single customer details
    if (isset($_GET['view'])) {
        $user_id = intval($_GET['view']);
        $user = get_userdata($user_id);
        if (!$user) return;

        // Get user orders
        $orders = get_posts([
            'post_type' => 'food_order',
            'meta_key' => 'customer_id',
            'meta_value' => $user_id,
            'numberposts' => -1
        ]);

        echo '<div class="wrap"><h2>Customer #'.$user->ID.' Details</h2>';
        echo '<p><strong>Name:</strong> '.esc_html($user->display_name).'</p>';
        echo '<p><strong>Email:</strong> '.esc_html($user->user_email).'</p>';
        echo '<p><strong>Role:</strong> '.implode(', ', $user->roles).'</p>';

        echo '<h3>Orders</h3>';
        if ($orders) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Order ID</th><th>Total</th><th>Status</th><th>Date</th><th>View</th></tr></thead>';
            echo '<tbody>';
            foreach ($orders as $o) {
                $status = get_post_meta($o->ID,'status',true) ?: 'Pending';
                $total = get_post_meta($o->ID,'total_price',true) ?: 0;
                echo '<tr>';
                echo '<td>#'.$o->ID.'</td>';
                echo '<td>'.get_option('fd_currency','৳').number_format($total,2).'</td>';
                echo '<td>'.ucfirst($status).'</td>';
                echo '<td>'.get_the_date('Y-m-d H:i',$o->ID).'</td>';
                echo '<td><a href="?page=fd_orders&view='.$o->ID.'">View</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No orders yet.</p>';
        }

        echo '<p><a href="?page=fd_customers">Back to Customers</a></p></div>';
        return;
    }

    // Default: list all registered users
    $all_users = get_users();
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1,intval($_GET['paged'])) : 1;
    $total_items = count($all_users);
    $users = array_slice($all_users, ($current_page-1)*$per_page, $per_page);

    echo '<div class="wrap"><h1 class="wp-heading-inline">Customers</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Total Orders</th><th>Actions</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        $orders = get_posts([
            'post_type' => 'food_order',
            'meta_key' => 'customer_id',
            'meta_value' => $user->ID,
            'numberposts' => -1
        ]);
        echo '<tr>';
        echo '<td>'.$user->ID.'</td>';
        echo '<td>'.esc_html($user->display_name).'</td>';
        echo '<td>'.esc_html($user->user_email).'</td>';
        echo '<td>'.implode(', ',$user->roles).'</td>';
        echo '<td>'.count($orders).'</td>';
        echo '<td><a href="?page=fd_customers&view='.$user->ID.'">View</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Pagination
    $total_pages = ceil($total_items / $per_page);
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        for ($i=1;$i<=$total_pages;$i++) {
            $class = ($i==$current_page)?'current-page':'';
            echo '<a class="'.$class.'" href="?page=fd_customers&paged='.$i.'">'.$i.'</a> ';
        }
        echo '</div></div>';
    }

    echo '</div>'; // wrap
}
?>
