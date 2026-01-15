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
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 0; }
            body { 
                font-family: "Courier New", Courier, monospace; 
                width: 72mm; 
                margin: 0; 
                padding: 0; 
                color: #000;
                background-color: #fff;
            }

            /* --- Your Provided Compact Styles --- */
            .awesome-food-delivery.afd-print {
                margin: 0 5px;
            }
            .awesome-food-delivery.afd-print .afd-right-box {
                max-width: 280px !important;
                margin: 0 auto;
            }
            .awesome-food-delivery.afd-print .afd-right-box .main-id {
                font-size: 16px;
                font-weight: bold;
                display: block;
                text-align: center;
            }
            .awesome-food-delivery.afd-print .afd-right-box .type-badge {
                font-size: 15px;
                background: #000;
                color: #fff;
                padding: 2px 5px;
                display: inline-block;
                margin: 5px 0;
            }
            .awesome-food-delivery.afd-print .afd-right-box .cust-name, 
            .awesome-food-delivery.afd-print .afd-right-box .cust-phone {
                font-size: 14px;
                display: block;
            }
            .awesome-food-delivery.afd-print .afd-right-box .qty {
                vertical-align: middle;
                font-size: 16px;
                width: 35px;
                font-weight: bold;
            }
            .awesome-food-delivery.afd-print .afd-right-box .item-name {
                font-size: 14px;
                line-height: 14px;
                font-weight: bold;
                text-transform: uppercase;
            }

            /* --- Structural Printing Styles --- */
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
            .customer-section { margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; }
            .item-row td { padding: 5px 0; border-bottom: 1px solid #eee; }
            .notes-box { border: 1px solid #000; padding: 5px; margin-top: 10px; font-size: 13px; }
            .footer { text-align: center; margin-top: 15px; font-size: 11px; }
            .cut-feed { height: 15mm; } /* Space for the physical cutter */

            @media print { 
                .no-print { display: none; } 
                body { width: 72mm; }
            }
        </style>
    </head>
    <body>

        <div class="no-print" style="text-align:center; padding: 10px;">
            <button onclick="window.print()" style="padding: 15px; background: #2271b1; color: #fff; border: none; cursor: pointer; font-weight: bold;">PRINT KITCHEN TICKET</button>
        </div>

        <div class="awesome-food-delivery afd-print">
            <div class="afd-right-box">
                
                <div class="header">
                    <span class="main-id">#<?php echo esc_html($display_id); ?></span>
                    <div class="type-badge"><?php echo strtoupper(esc_html($order->order_type)); ?></div>
                    <div style="font-size: 12px;"><?php echo date('d/m/Y - H:i', strtotime($order->order_date)); ?></div>
                </div>

                <div class="customer-section">
                    <span class="cust-name">NAME: <?php echo esc_html($order->full_name); ?></span>
                    <span class="cust-phone">TEL: <?php echo esc_html($order->phone); ?></span>
                    <?php if($order->order_type === 'delivery' && !empty($order->address)): ?>
                        <div style="font-size: 13px; margin-top: 5px; font-weight: bold;">
                            ADDR: <?php echo esc_html($order->address); ?>
                        </div>
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
                        <strong>NOTES:</strong><br>
                        <?php echo nl2br(esc_html($order->order_notes)); ?>
                    </div>
                <?php endif; ?>

                <div class="footer">
                    *** KITCHEN COPY ***<br>
                    Order #<?php echo esc_html($display_id); ?>
                </div>

                <div class="cut-feed"></div>
            </div>
        </div>

    </body>
    </html>
    <?php
    exit;
}