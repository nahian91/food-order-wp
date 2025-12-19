<?php
if (!defined('ABSPATH')) exit;

function fd_print_order_page() {
    if (!isset($_GET['order_id'])) { echo 'Order ID missing'; return; }

    $order_id = intval($_GET['order_id']);
    $order = get_post($order_id);
    if (!$order) { echo 'Order not found'; return; }

    $items    = get_post_meta($order_id,'items',true) ?: [];
    $total    = floatval(get_post_meta($order_id,'total_price',true) ?: 0);
    $customer = get_post_meta($order_id,'customer_name',true);
    $status   = get_post_meta($order_id,'status',true) ?: 'Pending';
    $date     = get_the_date('d M Y H:i', $order_id);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Order #<?php echo $order_id; ?> - Print</title>
        <style>
            body{font-family:Arial,sans-serif;margin:20px;}
            h1{margin-bottom:10px;}
            table{width:100%;border-collapse:collapse;margin-top:20px;}
            table,th,td{border:1px solid #000;}
            th,td{padding:8px;text-align:left;}
            .total{text-align:right;font-weight:bold;margin-top:10px;}
        </style>
    </head>
    <body onload="window.print()">
        <h1>Order #<?php echo $order_id; ?></h1>
        <p><strong>Customer:</strong> <?php echo esc_html($customer); ?></p>
        <p><strong>Status:</strong> <?php echo esc_html($status); ?></p>
        <p><strong>Date:</strong> <?php echo esc_html($date); ?></p>

        <table>
            <thead>
                <tr><th>Item</th><th>Qty</th><th>Price (€)</th></tr>
            </thead>
            <tbody>
                <?php foreach($items as $i): ?>
                    <tr>
                        <td><?php echo esc_html($i['name']); ?></td>
                        <td><?php echo esc_html($i['qty']); ?></td>
                        <td>€<?php echo number_format($i['price'],2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="total">Total: €<?php echo number_format($total,2); ?></p>
    </body>
    </html>
    <?php
}
