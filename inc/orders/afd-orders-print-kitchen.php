<?php
if (!defined('ABSPATH')) exit;

if (isset($_GET['action']) && $_GET['action'] === 'print' && $_GET['type'] === 'kitchen') {
    
    global $wpdb;
    $order_id = intval($_GET['order_id']);
    $table_name = $wpdb->prefix . 'afd_food_orders';
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));

    if (!$order) wp_die('Order not found.');

    $display_id = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;
    $items = json_decode($order->items_json, true);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            /* Reset all margins for POS printer alignment */
            @page {
                margin: 0;
            }
            body { 
                font-family: "Courier New", Courier, monospace; 
                width: 72mm; 
                margin: 0;
                padding: 0; 
                color: #000;
                background-color: #fff;
            }
            .awesome-food-delivery.afd-print .afd-right-box {
    width: 100%;
}
            .afd-print {
                width: 90mm;
                margin: 0;
                padding: 2mm; /* Internal padding for text safety */
                box-sizing: border-box;
            }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
            .main-id { font-size: 38px; font-weight: 900; display: block; margin: 0; line-height: 1.1; }
            .type-badge { color: #000; font-size: 26px; font-weight: bold; margin-top: 5px; display: inline-block; }
            
            .customer-section { margin-bottom: 15px; line-height: 1.2; text-align: left; }
            .cust-name { font-size: 22px; font-weight: 900; text-transform: uppercase; display: block; }
            .cust-phone { font-size: 20px; font-weight: bold; display: block; margin: 3px 0; }
            .cust-addr { font-size: 18px; font-weight: bold; border: 2px solid #000; padding: 4px; margin-top: 5px; display: block; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .item-row td { padding: 12px 0; border-bottom: 1px dashed #000; vertical-align: top; }
            .qty { font-size: 34px; font-weight: 900; width: 55px; line-height: 1; text-align: left; }
            .item-name { font-size: 21px; font-weight: bold; text-transform: uppercase; line-height: 1.1; text-align: left; }
            
            .notes-box { border: 2px solid #000; padding: 8px; margin-top: 15px; font-size: 19px; font-weight: bold; text-align: left; }
            .notes-title { font-size: 14px; text-decoration: underline; display: block; margin-bottom: 4px; }
            
            .footer { text-align: center; margin-top: 10px; font-size: 12px; padding-bottom: 15mm; }
            
            @media print { 
                .no-print { display: none; } 
                body { width: 72mm; margin: 0; padding: 0; } 
            }
        </style>
    </head>
    <body>

        <div class="no-print" style="text-align:center; padding: 10px;">
            <button onclick="window.print()" style="padding: 15px; font-size: 16px; font-weight: bold; background: #2271b1; color: #fff; border: none; border-radius: 5px; cursor: pointer;">PRINT KITCHEN TICKET</button>
        </div>

        <div class="afd-print">
            <div class="header">
                <span class="main-id">#<?php echo esc_html($display_id); ?></span>
                <div class="type-badge"><?php echo strtoupper($order->order_type); ?></div>
                <div style="font-size: 14px; margin-top: 5px; font-weight: bold;">
                    <?php echo date('d/m/Y - H:i', strtotime($order->order_date)); ?>
                </div>
            </div>

            <div class="customer-section">
                <span class="cust-name"><?php echo esc_html($order->full_name); ?></span>
                <span class="cust-phone"><?php echo esc_html($order->phone); ?></span>
                <?php if($order->order_type === 'delivery' && !empty($order->address)): ?>
                    <span class="cust-addr">ADDR: <?php echo esc_html($order->address); ?></span>
                <?php endif; ?>
            </div>

            <table>
                <?php if(is_array($items)) : foreach($items as $item) : ?>
                    <tr class="item-row">
                        <td class="qty"><?php echo intval($item['qty']); ?>x</td>
                        <td class="item-name"><?php echo esc_html($item['name']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>

            <?php if (!empty($order->order_notes)) : ?>
                <div class="notes-box">
                    <span class="notes-title">KITCHEN NOTES:</span>
                    <?php echo esc_html($order->order_notes); ?>
                </div>
            <?php endif; ?>

            <div class="footer">
                *** KITCHEN COPY ***<br>
                Order #<?php echo esc_html($display_id); ?><br>
                *** END OF ORDER ***
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}