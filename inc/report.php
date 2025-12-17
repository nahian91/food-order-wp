<?php
/*--------------------------------------------------------------
# Reports Tab – Modern Clean Admin UI (No JS, WP-friendly)
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
        $status = strtolower(get_post_meta($o->ID, 'status', true) ?: 'pending');

        $total_revenue += $total_price;
        ($status === 'completed') ? $completed++ : $pending++;
    }

    $currency = '€'; // EURO
    ?>

    <style>
        .fd-report-cards{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:16px;
            margin:20px 0;
        }
        .fd-report-card{
            background:#fff;
            border-radius:10px;
            padding:20px;
            box-shadow:0 5px 15px rgba(0,0,0,.06);
            border-left:5px solid #2271b1;
        }
        .fd-report-card h3{
            margin:0 0 10px;
            font-size:14px;
            color:#555;
            text-transform:uppercase;
        }
        .fd-report-card p{
            margin:0;
            font-size:28px;
            font-weight:700;
        }
        .fd-report-card.revenue{border-color:#2ecc71}
        .fd-report-card.completed{border-color:#27ae60}
        .fd-report-card.pending{border-color:#f39c12}

        .fd-table-wrap{
            background:#fff;
            padding:20px;
            border-radius:10px;
            box-shadow:0 5px 15px rgba(0,0,0,.06);
        }

        .fd-status{
            padding:4px 10px;
            border-radius:20px;
            font-size:12px;
            font-weight:600;
            display:inline-block;
        }
        .fd-status.completed{background:#eafaf1;color:#27ae60}
        .fd-status.pending{background:#fff3e0;color:#e67e22}
    </style>

    <div class="wrap">
        <h1 class="wp-heading-inline">📊 Reports</h1>

        <!-- Summary Cards -->
        <div class="fd-report-cards">
            <div class="fd-report-card">
                <h3>Total Orders</h3>
                <p><?php echo count($orders); ?></p>
            </div>

            <div class="fd-report-card revenue">
                <h3>Total Revenue</h3>
                <p><?php echo $currency . number_format($total_revenue, 2); ?></p>
            </div>

            <div class="fd-report-card completed">
                <h3>Completed Orders</h3>
                <p><?php echo $completed; ?></p>
            </div>

            <div class="fd-report-card pending">
                <h3>Pending Orders</h3>
                <p><?php echo $pending; ?></p>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="fd-table-wrap">
            <h2>Latest Orders</h2>

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
                        <?php foreach (array_slice($orders, 0, 10) as $o) :
                            $customer = get_post_meta($o->ID, 'customer_name', true) ?: '-';
                            $total = floatval(get_post_meta($o->ID, 'total_price', true));
                            $status = strtolower(get_post_meta($o->ID, 'status', true) ?: 'pending');
                            $date = get_the_date('d M Y, H:i', $o->ID);
                        ?>
                            <tr>
                                <td><strong>#<?php echo $o->ID; ?></strong></td>
                                <td><?php echo esc_html($customer); ?></td>
                                <td><?php echo $currency . number_format($total, 2); ?></td>
                                <td>
                                    <span class="fd-status <?php echo esc_attr($status); ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($date); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </div>
    </div>

<?php
}
?>
