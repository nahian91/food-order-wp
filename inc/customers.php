<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# View Registered Users and Their Orders - Clean HTML Format
--------------------------------------------------------------*/
function fd_customers_tab() {

    $currency = get_option('fd_currency', '৳');

    // ----- Single Customer View -----
    if (isset($_GET['view'])) :
        $user_id = intval($_GET['view']);
        $user = get_userdata($user_id);
        if (!$user) return;

        $orders = get_posts([
            'post_type'   => 'food_order',
            'meta_key'    => 'customer_id',
            'meta_value'  => $user_id,
            'numberposts' => -1,
        ]);
        ?>
        <div class="wrap">
            <h2>Customer #<?php echo $user->ID; ?> Details</h2>
            <p><strong>Name:</strong> <?php echo esc_html($user->display_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($user->user_email); ?></p>
            <p><strong>Role:</strong> <?php echo esc_html(implode(', ', $user->roles)); ?></p>

            <h3>Orders</h3>
            <?php if ($orders) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o) :
                            $status = get_post_meta($o->ID, 'status', true) ?: 'Pending';
                            $total = floatval(get_post_meta($o->ID, 'total_price', true));
                        ?>
                            <tr>
                                <td>#<?php echo $o->ID; ?></td>
                                <td><?php echo $currency . number_format($total, 2); ?></td>
                                <td><?php echo ucfirst($status); ?></td>
                                <td><?php echo get_the_date('Y-m-d H:i', $o->ID); ?></td>
                                <td><a href="?page=fd_orders&view=<?php echo $o->ID; ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No orders yet.</p>
            <?php endif; ?>

            <p><a href="?page=fd_customers">Back to Customers</a></p>
        </div>
        <?php
        return;
    endif;

    // ----- Default: List All Customers -----
    $all_users = get_users();
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $total_items = count($all_users);
    $users = array_slice($all_users, ($current_page - 1) * $per_page, $per_page);
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">Customers</h1>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Total Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) :
                    $orders = get_posts([
                        'post_type'   => 'food_order',
                        'meta_key'    => 'customer_id',
                        'meta_value'  => $user->ID,
                        'numberposts' => -1,
                    ]);
                ?>
                    <tr>
                        <td><?php echo $user->ID; ?></td>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                        <td><?php echo count($orders); ?></td>
                        <td><a href="?page=fd_customers&view=<?php echo $user->ID; ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php
        $total_pages = ceil($total_items / $per_page);
        if ($total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $total_pages; $i++) :
                        $class = ($i == $current_page) ? 'current-page' : '';
                    ?>
                        <a class="<?php echo $class; ?>" href="?page=fd_customers&paged=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
<?php
} // end function
?>
