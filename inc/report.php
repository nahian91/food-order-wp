<?php
/*--------------------------------------------------------------
# Reports Tab - Default WP Admin Layout
--------------------------------------------------------------*/
function fd_reports_tab() {

    $orders = get_posts([
        'post_type'   => 'food_order',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    $total_revenue = 0;
    $completed = 0;
    $pending = 0;

    foreach ($orders as $o) {
        $total_price = floatval(get_post_meta($o->ID, 'total_price', true));
        $status = get_post_meta($o->ID, 'status', true) ?: 'Pending';
        $total_revenue += $total_price;

        if (strtolower($status) === 'completed') {
            $completed++;
        } else {
            $pending++;
        }
    }

    $currency = get_option('fd_currency', '৳');
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">Reports</h1>

        <!-- Summary Boxes -->
        <div class="metabox-holder columns-4">
            <div class="postbox">
                <h2 class="hndle"><span>Total Orders</span></h2>
                <div class="inside"><?php echo count($orders); ?></div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><span>Total Revenue</span></h2>
                <div class="inside"><?php echo $currency . ' ' . number_format($total_revenue, 2); ?></div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><span>Completed Orders</span></h2>
                <div class="inside"><?php echo $completed; ?></div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><span>Pending Orders</span></h2>
                <div class="inside"><?php echo $pending; ?></div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <h2>Recent Orders</h2>
        <?php if ($orders) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o) :
                        $customer = get_post_meta($o->ID, 'customer_name', true) ?: '-';
                        $total = floatval(get_post_meta($o->ID, 'total_price', true));
                        $status = get_post_meta($o->ID, 'status', true) ?: 'Pending';
                        $date = get_the_date('Y-m-d H:i', $o->ID);
                    ?>
                        <tr>
                            <td>#<?php echo $o->ID; ?></td>
                            <td><?php echo esc_html($customer); ?></td>
                            <td><?php echo $currency . ' ' . number_format($total, 2); ?></td>
                            <td><?php echo ucfirst($status); ?></td>
                            <td><?php echo $date; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </div>

<?php
} // end function
?>
