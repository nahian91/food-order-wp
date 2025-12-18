<?php
if (!defined('ABSPATH')) exit;

// -------------------- Kitchen Print --------------------
if (isset($_GET['action'], $_GET['order_id']) && $_GET['action'] === 'print') {

    $order_id = intval($_GET['order_id']);
    $customer_name    = get_post_meta($order_id, 'customer_name', true);
    $customer_phone   = get_post_meta($order_id, 'customer_phone', true);
    $customer_address = get_post_meta($order_id, 'customer_address', true);
    $notes            = get_post_meta($order_id, 'notes', true);
    $status           = get_post_meta($order_id, 'status', true);
    $items            = get_post_meta($order_id, 'items', true);
    $total            = get_post_meta($order_id, 'total_price', true);
    $order_date       = get_the_date('d/m/Y H:i', $order_id);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Kitchen Receipt - #<?php echo $order_id; ?></title>
        <style>
            @page { margin: 0; }
            body {
                font-family: "Courier New", Courier, monospace; /* Classic receipt font */
                width: 80mm; /* Standard thermal printer width */
                margin: 0 auto;
                padding: 10px;
                color: #000;
                line-height: 1.2;
            }
            .center { text-align: center; }
            .bold { font-weight: bold; }
            
            .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
            .order-title { font-size: 22px; margin: 5px 0; }
            
            .customer-info { font-size: 14px; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 5px; }
            th { border-bottom: 1px solid #000; text-align: left; padding: 5px 0; font-size: 13px; }
            td { padding: 8px 0; vertical-align: top; border-bottom: 1px dashed #ccc; }
            
            .qty-col { width: 40px; font-size: 18px; font-weight: bold; }
            .item-col { font-size: 16px; font-weight: bold; }
            
            .notes-section { 
                margin-top: 15px; 
                padding: 8px; 
                border: 2px solid #000; 
                font-size: 15px; 
                background: #f0f0f0;
            }
            
            .summary { margin-top: 15px; text-align: right; border-top: 1px solid #000; padding-top: 5px; }
            .total-row { font-size: 18px; font-weight: bold; }
            
            .footer-msg { margin-top: 20px; font-size: 11px; text-align: center; }

            /* Print-only adjustments */
            @media print {
                body { width: 100%; padding: 5mm; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header center">
            <div class="bold">KITCHEN COPY</div>
            <h2 class="order-title">#<?php echo $order_id; ?></h2>
            <div style="font-size: 12px;"><?php echo $order_date; ?></div>
        </div>

        <div class="customer-info">
            <?php if($customer_name): ?>
                <div><strong>Cust:</strong> <?php echo esc_html($customer_name); ?></div>
            <?php endif; ?>
            <?php if($customer_phone): ?>
                <div><strong>Tel:</strong> <?php echo esc_html($customer_phone); ?></div>
            <?php endif; ?>
            <?php if($customer_address): ?>
                <div style="margin-top:4px;"><strong>Addr:</strong> <?php echo esc_html($customer_address); ?></div>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="qty-col">QTY</th>
                    <th class="item-col">ITEM DESCRIPTION</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if(is_array($items) && !empty($items)):
                    foreach($items as $item):
                        $qty = isset($item['qty']) ? intval($item['qty']) : 1;
                ?>
                    <tr>
                        <td class="qty-col"><?php echo $qty; ?>x</td>
                        <td class="item-col"><?php echo esc_html($item['name']); ?></td>
                    </tr>
                <?php
                    endforeach;
                else:
                ?>
                    <tr><td colspan="2" class="center">No items found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($notes): ?>
            <div class="notes-section">
                <strong>🚨 KITCHEN NOTES:</strong><br>
                <?php echo nl2br(esc_html($notes)); ?>
            </div>
        <?php endif; ?>

        <div class="summary">
            <div class="total-row">Total: <?php echo number_format(floatval($total), 2, ',', '.'); ?> €</div>
            <div style="font-size: 12px; margin-top: 5px;">Status: <?php echo esc_html(ucfirst($status)); ?></div>
        </div>

        <div class="footer-msg">
            --- END OF ORDER ---
        </div>

        <script>
            window.onload = function() {
                window.print();
                // window.onafterprint = function() { window.close(); };
            };
        </script>
    </body>
    </html>
    <?php
    exit; 
}