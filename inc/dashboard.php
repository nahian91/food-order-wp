<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Professional Food Admin Dashboard (afd- prefixed classes)
--------------------------------------------------------------*/
function fd_dashboard_tab() {

    // Fetch all orders
    $all_orders = get_posts([
        'post_type'      => 'food_order',
        'numberposts'    => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'ASC',
    ]);

    $total_orders = count($all_orders);
    $completed = $pending = $cancelled = 0;
    $total_revenue = 0;
    $item_counts = [];

    foreach ($all_orders as $o) {
        $status = get_post_meta($o->ID, 'status', true) ?: 'Pending';
        $total_price = floatval(get_post_meta($o->ID, 'total_price', true));
        $total_revenue += $total_price;

        switch (strtolower($status)) {
            case 'completed': $completed++; break;
            case 'pending': $pending++; break;
            case 'cancelled': $cancelled++; break;
        }

        $order_items = get_post_meta($o->ID, 'items', true);
        if ($order_items && is_array($order_items)) {
            foreach ($order_items as $i) {
                $item_name = $i['name'] ?? 'Unknown';
                $qty = isset($i['qty']) ? intval($i['qty']) : 1;
                $item_counts[$item_name] = ($item_counts[$item_name] ?? 0) + $qty;
            }
        }
    }

    arsort($item_counts);
    $top_items = array_slice($item_counts, 0, 5, true);
    $currency = get_option('fd_currency', '৳');
    ?>

    <div class="wrap afd-wrap">
        <!-- Overview Cards -->
        <div class="afd-cards">
            <div class="afd-card afd-card-orders">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
            <div class="afd-card afd-card-completed">
                <h3>Completed</h3>
                <p><?php echo $completed; ?></p>
            </div>
            <div class="afd-card afd-card-pending">
                <h3>Pending</h3>
                <p><?php echo $pending; ?></p>
            </div>
            <div class="afd-card afd-card-cancelled">
                <h3>Cancelled</h3>
                <p><?php echo $cancelled; ?></p>
            </div>
            <div class="afd-card afd-card-revenue">
                <h3>Total Revenue</h3>
                <p><?php echo $currency . number_format($total_revenue, 2); ?></p>
            </div>
        </div>

        <!-- Top Selling Items -->
        <div class="afd-section">
            <h2>Top Selling Items</h2>
            <?php if ($top_items) : ?>
                <ul class="afd-top-items">
                    <?php foreach ($top_items as $name => $count) :
                        $item_post = get_page_by_title($name, 'OBJECT', 'food_item');
                        $thumb = $item_post ? get_the_post_thumbnail($item_post->ID, [50, 50], ['class'=>'afd-item-thumb']) : '';
                    ?>
                        <li class="afd-item">
                            <?php echo $thumb; ?>
                            <span class="afd-item-name"><strong><?php echo esc_html($name); ?></strong> - <?php echo $count; ?> sold</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>No items sold yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Orders Table -->
        <div class="afd-section">
            <h2>Recent Orders</h2>
            <?php if ($all_orders) : ?>
                <div class="afd-table-wrapper">
                    <table class="afd-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent = array_slice($all_orders, -5);
                            foreach ($recent as $o) :
                                $status = get_post_meta($o->ID, 'status', true) ?: 'Pending';
                                $customer = get_post_meta($o->ID, 'customer_name', true) ?: '-';
                                $total = get_post_meta($o->ID, 'total_price', true) ?: 0;
                            ?>
                                <tr>
                                    <td>#<?php echo $o->ID; ?></td>
                                    <td><?php echo esc_html($customer); ?></td>
                                    <td><?php echo $currency . number_format($total, 2); ?></td>
                                    <td><?php echo ucfirst($status); ?></td>
                                    <td><?php echo get_the_date('Y-m-d H:i', $o->ID); ?></td>
                                    <td>
                                        <a href="?page=fd_orders&view=<?php echo $o->ID; ?>">View</a> |
                                        <a href="?page=fd_orders&edit=<?php echo $o->ID; ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>No orders yet.</p>
            <?php endif; ?>
        </div>
    </div>

<?php
}
?>
