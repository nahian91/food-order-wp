<?php
if (!defined('ABSPATH')) exit;

function fd_print_order_page() {
    // 1. Basic Setup & Security
    if (!isset($_GET['order_id'])) { echo 'Order ID missing'; return; }
    $order_id = intval($_GET['order_id']);
    $order = get_post($order_id);
    if (!$order) { echo 'Order not found'; return; }

    // 2. CHECK THE TYPE (This is what makes the buttons different)
    $print_type = (isset($_GET['type']) && $_GET['type'] === 'kitchen') ? 'kitchen' : 'customer';

    // 3. Fetch Data
    $items    = get_post_meta($order_id, 'items', true) ?: [];
    $total    = floatval(get_post_meta($order_id, 'total_price', true) ?: 0);
    $customer = get_post_meta($order_id, 'customer_name', true) ?: 'Guest';
    $phone    = get_post_meta($order_id, 'customer_phone', true);
    $address  = get_post_meta($order_id, 'customer_address', true);
    $notes    = get_post_meta($order_id, 'order_notes', true);
    $order_type = get_post_meta($order_id, 'order_type', true); // Delivery or Collection
    $date     = get_the_date('d M Y H:i', $order_id);
    $display_id = get_the_title($order_id);

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo strtoupper($print_type); ?> - <?php echo $display_id; ?></title>
        <style>
            /* BASE STYLES (Receipt Style) */
            body { font-family: 'Courier New', Courier, monospace; margin: 10px; color: #000; line-height: 1.2; }
            .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
            .order-id { font-size: 24px; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { border-bottom: 1px solid #000; text-align: left; padding: 5px; font-size: 12px; }
            td { padding: 8px 5px; vertical-align: top; border-bottom: 1px solid #eee; }
            .total-area { margin-top: 15px; border-top: 2px solid #000; padding-top: 10px; text-align: right; }
            .notes-area { margin-top: 15px; padding: 10px; border: 1px solid #000; background: #f9f9f9; }

            /* KITCHEN OVERRIDES (Big Font, No Money) */
            <?php if ($print_type === 'kitchen'): ?>
                body { font-size: 20px; } 
                .price-col, .total-area { display: none !important; } /* HIDE PRICES */
                .qty-cell { font-size: 30px; font-weight: bold; border: 2px solid #000; padding: 2px 8px !important; display: inline-block; }
                .item-name { font-size: 26px; font-weight: bold; }
                .notes-area { background: #000; color: #fff; font-size: 24px; font-weight: bold; }
            <?php else: ?>
                /* CUSTOMER OVERRIDES */
                body { font-size: 14px; }
            <?php endif; ?>

            @media print {
                .no-print { display: none; }
            }
        </style>
    </head>
    <body onload="window.print();">

        <div class="header">
            <h2 style="margin:0;"><?php echo ($print_type === 'kitchen') ? '--- KITCHEN TICKET ---' : 'ORDER RECEIPT'; ?></h2>
            <div class="order-id"><?php echo esc_html($display_id); ?></div>
            <div><?php echo esc_html($date); ?></div>
            <div style="font-size: 20px; font-weight: bold; border: 2px solid #000; display: inline-block; padding: 5px 15px; margin-top: 5px;">
                <?php echo strtoupper(esc_html($order_type)); ?>
            </div>
        </div>

        <div class="customer-info">
            <p><strong>Customer:</strong> <?php echo esc_html($customer); ?><br>
            <strong>Phone:</strong> <?php echo esc_html($phone); ?></p>
            <?php if ($order_type === 'delivery'): ?>
                <p><strong>Address:</strong><br><?php echo nl2br(esc_html($address)); ?></p>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="15%">Qty</th>
                    <th>Item Description</th>
                    <th width="20%" class="price-col">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i): ?>
                    <tr>
                        <td><span class="qty-cell">x<?php echo esc_html($i['qty']); ?></span></td>
                        <td><span class="item-name"><?php echo esc_html($i['name']); ?></span></td>
                        <td class="price-col">€<?php echo number_format($i['price'] * $i['qty'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-area">
            <p style="font-size: 22px; font-weight: bold;">TOTAL: €<?php echo number_format($total, 2); ?></p>
        </div>

        <?php if (!empty($notes)): ?>
            <div class="notes-area">
                <strong>SPECIAL INSTRUCTIONS:</strong><br>
                <?php echo nl2br(esc_html($notes)); ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px; font-size: 10px; border-top: 1px solid #ccc; padding-top: 10px;">
            <?php if ($print_type === 'customer'): ?>
                <p>Thank you for your business!</p>
            <?php endif; ?>
            <p>Printed at: <?php echo date('H:i:s'); ?> | <?php echo strtoupper($print_type); ?> COPY</p>
        </div>

    </body>
    </html>
    <?php
}