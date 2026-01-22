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
    $payment_method = !empty($order->payment_method) ? strtoupper($order->payment_method) : 'NOT SPECIFIED';

    // --- FIX: ADDRESS FETCHING LOGIC ---
    $u_id = $order->customer_id;
    $full_address = '';

    if ($u_id) {
        // Try to get SaaS detailed components
        $u_flat     = get_user_meta($u_id, 'fd_flat_no', true);
        $u_building = get_user_meta($u_id, 'fd_building', true);
        $u_door     = get_user_meta($u_id, 'fd_door_no', true);
        $u_road     = get_user_meta($u_id, 'fd_road_name', true);
        $u_postcode = get_user_meta($u_id, 'fd_user_postcode', true);

        $address_parts = array_filter([
            $u_door ? "Door $u_door" : "",
            $u_flat ? "Flat $u_flat" : "",
            $u_building,
            $u_road,
            $u_postcode
        ]);
        $full_address = implode(', ', $address_parts);
    }

    // FALLBACK: If user meta is empty or it's a guest, use the address stored in the order table
    if (empty($full_address) && !empty($order->address)) {
        $full_address = $order->address;
    }

    $rest_discount = get_option('afd_restaurant_discount', '0.00');
    $service_fee   = get_option('afd_service_charge', '0.00');
    $bag_fee       = get_option('afd_bag_charge', '0.00');
    $tips          = isset($order->tips) ? $order->tips : '0.00'; 
    ?>
    <!DOCTYPE html>
    <html>
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
            .afd-print {
                width: 90mm;
                margin: 0;
                padding: 2mm;
                box-sizing: border-box;
            }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
            .main-id { font-size: 38px; font-weight: 900; display: block; margin: 0; line-height: 1.1; }
            .type-badge { color: #000; font-size: 24px; font-weight: bold; margin-top: 5px; display: inline-block; }
            
            .customer-section { margin-bottom: 15px; line-height: 1.3; text-align: left; }
            .cust-name { font-size: 22px; font-weight: 900; text-transform: uppercase; display: block; }
            .cust-phone { font-size: 20px; font-weight: bold; display: block; margin: 3px 0; }
            .cust-addr-full { 
                font-size: 19px; 
                font-weight: 900; 
                border: 2px solid #000; 
                padding: 6px; 
                margin-top: 8px; 
                display: block; 
            }
            
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { text-align: left; font-size: 14px; border-bottom: 2px solid #000; padding-bottom: 5px; }
            .item-row td { padding: 8px 0; border-bottom: 1px dashed #000; vertical-align: top; }
            
            .qty { font-size: 24px; font-weight: 900; width: 40px; line-height: 1; }
            .item-name { font-size: 18px; font-weight: bold; text-transform: uppercase; line-height: 1.1; }
            .item-price { font-size: 16px; text-align: right; width: 60px; font-weight: bold; }
            
            .summary-section { margin-top: 15px; border-top: 1px solid #000; padding-top: 10px; }
            .summary-line { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; margin-bottom: 4px; }
            .summary-line.bold-total { font-size: 22px; border-top: 2px solid #000; margin-top: 8px; padding-top: 5px; }

            .notes-box { border: 2px solid #000; padding: 8px; margin-top: 15px; font-size: 19px; font-weight: bold; text-align: left; }
            
            .footer { text-align: center; margin-top: 10px; font-size: 12px; padding-bottom: 15mm; }
            
            @media print { 
                .no-print { display: none; } 
                body { width: 72mm; margin: 0; padding: 0; } 
            }
        </style>
    </head>
    <body>

        <div class="no-print" style="text-align:center; padding: 10px;">
            <button onclick="window.print()" style="padding: 15px; font-size: 16px; font-weight: bold; background: #2271b1; color: #fff; border: none; border-radius: 5px; cursor: pointer;">PRINT TICKET</button>
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
                
                <?php if($order->order_type === 'delivery' && !empty($full_address)): ?>
                    <div class="cust-addr-full">
                        ADDR: <?php echo esc_html($full_address); ?>
                    </div>
                <?php endif; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>QTY</th>
                        <th>ITEM</th>
                        <th style="text-align:right;">PRICE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(is_array($items)) : foreach($items as $item) : 
                        $item_price = isset($item['price']) ? floatval($item['price']) : 0;
                        $line_total = $item_price * intval($item['qty']);
                    ?>
                        <tr class="item-row">
                            <td class="qty"><?php echo intval($item['qty']); ?>x</td>
                            <td class="item-name">
                                <?php echo esc_html($item['name']); ?>
                                <div style="font-size: 12px; font-weight: normal;">@ £<?php echo number_format($item_price, 2); ?></div>
                            </td>
                            <td class="item-price">£<?php echo number_format($line_total, 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div class="summary-section">
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span>£<?php echo number_format($order->subtotal, 2); ?></span>
                </div>
                <div class="summary-line">
                    <span>Restaurant Discount</span>
                    <span>-£<?php echo number_format((float)$rest_discount, 2); ?></span>
                </div>
                <div class="summary-line" style="border-bottom: 1px dashed #ccc; padding-bottom: 5px;">
                    <span>Order Total</span>
                    <span>£<?php echo number_format(($order->subtotal - (float)$rest_discount), 2); ?></span>
                </div>
                <div class="summary-line" style="margin-top:5px;">
                    <span>Service Charge</span>
                    <span>£<?php echo number_format((float)$service_fee, 2); ?></span>
                </div>
                <div class="summary-line">
                    <span>Delivery Charges</span>
                    <span>£<?php echo number_format($order->delivery_fee, 2); ?></span>
                </div>
                <div class="summary-line">
                    <span>Bag Charge</span>
                    <span>£<?php echo number_format((float)$bag_fee, 2); ?></span>
                </div>
                <div class="summary-line">
                    <span>Tips</span>
                    <span>£<?php echo number_format((float)$tips, 2); ?></span>
                </div>
                <div class="summary-line bold-total">
                    <span>TOTAL DUE</span>
                    <span>£<?php echo number_format($order->total_price, 2); ?></span>
                </div>
                <div class="summary-line" style="margin-top: 10px; font-size: 18px;">
                    <span>METHOD:</span>
                    <span><?php echo esc_html($payment_method); ?></span>
                </div>
            </div>

            <?php if (!empty($order->order_notes)) : ?>
                <div class="notes-box">
                    <span style="font-size:14px; text-decoration:underline; display:block; margin-bottom:4px;">KITCHEN NOTES:</span>
                    <?php echo esc_html($order->order_notes); ?>
                </div>
            <?php endif; ?>

            <div class="footer">
                *** ORDER COPY ***<br>
                Order #<?php echo esc_html($display_id); ?><br>
                *** END OF ORDER ***
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}