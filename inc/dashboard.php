<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Food Delivery Admin Dashboard (FULL)
--------------------------------------------------------------*/
function fd_dashboard_tab() {

    $orders = get_posts([
        'post_type'   => 'food_order',
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    $total_orders = count($orders);
    $completed = $pending = $cancelled = 0;
    $total_revenue = 0;

    foreach ($orders as $order) {
        $status = strtolower(get_post_meta($order->ID, 'status', true) ?: 'pending');
        $total  = floatval(get_post_meta($order->ID, 'total_price', true));
        $total_revenue += $total;

        if ($status === 'completed') $completed++;
        elseif ($status === 'cancelled') $cancelled++;
        else $pending++;
    }

    $currency = '€';
    ?>

    <div class="wrap afd-dashboard">

        <h1>Food Delivery Dashboard</h1>

        <style>
            .afd-dashboard * { box-sizing: border-box; }
            .afd-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
                gap: 20px;
                margin: 25px 0;
            }
            .afd-card {
                background: #fff;
                padding: 20px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 4px 14px rgba(0,0,0,.08);
            }
            .afd-card h3 {
                margin: 0 0 10px;
                font-size: 14px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: .5px;
            }
            .afd-card p {
                font-size: 26px;
                font-weight: 700;
                margin: 0;
            }
            .afd-section {
                background: #fff;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 4px 14px rgba(0,0,0,.08);
            }
            .afd-section h2 {
                margin-top: 0;
            }
            .afd-table th {
                font-weight: 600;
            }
            .afd-status-completed { color: #46b450; font-weight: 600; }
            .afd-status-pending { color: #ffb900; font-weight: 600; }
            .afd-status-cancelled { color: #dc3232; font-weight: 600; }
        </style>

        <!-- OVERVIEW CARDS -->
        <div class="afd-cards">
            <div class="afd-card">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
            <div class="afd-card">
                <h3>Completed</h3>
                <p><?php echo $completed; ?></p>
            </div>
            <div class="afd-card">
                <h3>Pending</h3>
                <p><?php echo $pending; ?></p>
            </div>
            <div class="afd-card">
                <h3>Cancelled</h3>
                <p><?php echo $cancelled; ?></p>
            </div>
            <div class="afd-card">
                <h3>Total Revenue</h3>
                <p><?php echo $currency . number_format($total_revenue, 2); ?></p>
            </div>
        </div>

        <!-- LATEST ORDERS -->
        <div class="afd-section">
            <h2>Latest Orders</h2>

            <?php if ($orders): ?>
                <table class="wp-list-table widefat fixed striped afd-table">
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
                        <?php foreach (array_slice($orders, 0, 6) as $order): 
                            $status = strtolower(get_post_meta($order->ID, 'status', true) ?: 'pending');
                            $customer = get_post_meta($order->ID, 'customer_name', true) ?: '—';
                            $total = get_post_meta($order->ID, 'total_price', true) ?: 0;
                        ?>
                        <tr>
                            <td>#<?php echo $order->ID; ?></td>
                            <td><?php echo esc_html($customer); ?></td>
                            <td><?php echo $currency . number_format($total, 2); ?></td>
                            <td class="afd-status-<?php echo esc_attr($status); ?>">
                                <?php echo ucfirst($status); ?>
                            </td>
                            <td><?php echo get_the_date('Y-m-d H:i', $order->ID); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </div>

    </div>

<?php
}
