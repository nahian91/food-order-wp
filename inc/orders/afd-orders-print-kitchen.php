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

    // --- ADDRESS FETCHING LOGIC ---
    $u_id = $order->customer_id;
    $addr_list = [];

    if ($u_id) {
        $u_flat     = get_user_meta($u_id, 'fd_flat_no', true);
        $u_building = get_user_meta($u_id, 'fd_building', true);
        $u_door     = get_user_meta($u_id, 'fd_door_no', true);
        $u_road     = get_user_meta($u_id, 'fd_road_name', true);
        $u_postcode = get_user_meta($u_id, 'fd_user_postcode', true);

        if(!empty($u_flat))     $addr_list[] = "Flat No. " . $u_flat;
        if(!empty($u_building)) $addr_list[] = "Building " . $u_building;
        if(!empty($u_door))     $addr_list[] = "Door No. " . $u_door;
        if(!empty($u_road))     $addr_list[] = "Road Name " . $u_road;
        if(!empty($u_postcode)) $addr_list[] = "Postcode: " . $u_postcode;
    }

    // --- NOTES LOGIC ---
    $kitchen_notes = !empty($order->kitchen_notes) ? $order->kitchen_notes : (!empty($order->notes) ? $order->notes : '');
    $delivery_notes = !empty($order->delivery_notes) ? $order->delivery_notes : '';

   // --- FINANCIAL CALCULATIONS ---
$items_subtotal = 0;
if (is_array($items)) {
    foreach ($items as $item) { 
        // 1. Get Base Price and Variant Price
        $base_p    = isset($item['price']) ? floatval($item['price']) : 0;
        $variant_p = isset($item['vPrice']) ? floatval($item['vPrice']) : 0;
        $qty       = isset($item['qty']) ? intval($item['qty']) : 0;

        // 2. Formula: (Base + Variant) * Qty
        $items_subtotal += ($base_p + $variant_p) * $qty; 
    }
}
    $service_fee   = floatval($order->service_fee);
    $bag_fee       = floatval($order->bag_fee);
    $delivery_charge  = floatval($order->delivery_charge);
    $tips          = isset($order->tip_amount) ? floatval($order->tip_amount) : 0.00; 

    $gross_total = $items_subtotal + $service_fee + $bag_fee + $delivery_charge + $tips;
    $dynamic_discount = $gross_total - floatval($order->total_price);

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
            .afd-print {
                width: 88mm;
                margin: 0;
                padding: 2mm;
                box-sizing: border-box;
            }
            .header { text-align: center; border-bottom: 4px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
            .main-id { font-size: 50px; font-weight: 900; display: block; margin: 0; line-height: 1; }
            .type-badge { color: #000; font-size: 32px; font-weight: 900; margin-top: 5px; display: inline-block; padding: 4px 0; text-transform: uppercase; }
            
            .customer-section { margin-top: 20px; line-height: 1.1; text-align: left; }
            .cust-name { font-size: 28px; font-weight: 900; text-transform: uppercase; display: block; }
            .cust-phone { font-size: 26px; font-weight: bold; display: block; margin: 5px 0; }
            
            .cust-addr-list { 
                font-size: 24px; 
                font-weight: 900; 
                padding: 10px 0; 
                margin-top: 10px; 
                display: block; 
            }
            .addr-item { display: block; margin-bottom: 4px; padding-bottom: 2px; }

            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { text-align: left; font-size: 18px; border-bottom: 4px solid #000; padding-bottom: 8px; }
            .item-row td { padding: 15px 0; border-bottom: 2px dashed #000; vertical-align: top; }
            
            .qty { font-size: 38px; font-weight: 900; width: 60px; line-height: 1; }
            .item-name { font-size: 24px; font-weight: 900; text-transform: uppercase; line-height: 1.1; padding-left: 5px; display: block; }
            .unit-price { font-size: 20px; font-weight: bold; padding-left: 5px; display: block; margin-top: 4px; color: #000; }
            .item-price { font-size: 22px; text-align: right; width: 90px; font-weight: 900; }
            
            .summary-section { margin-top: 20px; border-top: 3px solid #000; padding-top: 12px; }
            .summary-line { display: flex; justify-content: space-between; font-size: 22px; font-weight: bold; margin-bottom: 8px; }
            .summary-line.bold-total { font-size: 32px; border-top: 4px solid #000; margin-top: 10px; padding-top: 10px; font-weight: 900; }

            .notes-box { padding: 12px 0; margin-top: 15px; font-size: 28px; font-weight: 900; text-align: left; line-height: 1.2; }
            .notes-label { font-size: 20px; text-decoration: underline; display: block; margin-bottom: 5px; font-weight: 900; }
            
            .footer { text-align: center; margin-top: 25px; font-size: 16px; padding-bottom: 25mm; font-weight: bold; }
            
            @media print { 
                .no-print { display: none; } 
                body { width: 72mm; margin: 0; padding: 0; } 
            }
        </style>
    </head>
    <body>

        <div class="no-print" style="text-align:center; padding: 10px;">
            <button onclick="window.print()" style="padding: 20px; font-size: 22px; font-weight: bold; background: #2271b1; color: #fff; border: none; border-radius: 5px; cursor: pointer; width: 80%;">PRINT TICKET</button>
        </div>

        <div class="afd-print">
            <div class="header">
                <span class="main-id">#<?php echo esc_html($display_id); ?></span>
                <div class="type-badge"><?php echo strtoupper($order->order_type); ?></div>
                <div style="font-size: 18px; margin-top: 8px; font-weight: 900;">
                    <?php echo date('d/m/Y - H:i', strtotime($order->order_date)); ?>
                </div>
            </div>

            <?php if (!empty($order->order_notes)) : ?>
                <div class="notes-box">
                    <span class="notes-label">CUSTOMER REQUEST:</span>
                    <?php echo esc_html($order->order_notes); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($kitchen_notes)) : ?>
                <div class="notes-box">
                    <span class="notes-label">KITCHEN NOTE:</span>
                    <?php echo esc_html($kitchen_notes); ?>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>QTY</th>
                        <th>ITEM</th>
                        <th style="text-align:right;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
    <?php if(is_array($items)) : foreach($items as $item) : 
        // 1. Get Base Price, Variant Price, and Quantity
        $base_p    = isset($item['price']) ? floatval($item['price']) : 0;
        $variant_p = isset($item['vPrice']) ? floatval($item['vPrice']) : 0;
        $qty       = isset($item['qty']) ? intval($item['qty']) : 1;

        // 2. Calculation: (Base + Variant) * Qty
        $combined_unit_p = $base_p + $variant_p; 
        $line_total      = $combined_unit_p * $qty;
    ?>
        <tr class="item-row">
            <td class="qty"><?php echo $qty; ?>x</td>
            
            <td>
                <span class="item-name" style="display:block; font-weight:600;">
                    <?php echo esc_html($item['name']); ?>
                </span>
                
                <span class="unit-price">
                    £<?php echo number_format($combined_unit_p, 2); ?> each
                </span>

                <?php if (!empty($item['vName'])) : ?>
                    <b class="variant-name"><?php echo esc_html($item['vName']); ?></b>
                <?php endif; ?>
            </td>
            
            <td class="item-price" style="text-align:right;">
                £<?php echo number_format($line_total, 2); ?>
            </td>
        </tr>
    <?php endforeach; endif; ?>
</tbody>
            </table>

            <div class="summary-section">
    <div class="summary-line">
        <span>Subtotal</span>
        <span>£<?php echo number_format($items_subtotal, 2); ?></span>
    </div>

    <?php 
// Add this line to define the variable from the order object
$order_type = isset($order->order_type) ? strtolower($order->order_type) : 'delivery'; 
?>

<div class="summary-line">
    <span>
        <?php echo ($order_type === 'delivery') ? 'Discount' : 'Discount'; ?>
    </span>
    <span>
        -£<?php 
            // Select the discount value based on order type
            $active_discount = ($order_type === 'delivery') 
                ? (float)($order->delivery_discount ?? 0) 
                : (float)($order->collection_discount ?? 0);

            echo number_format(max(0, $active_discount), 2); 
        ?>
    </span>
</div>

    <div class="summary-line">
        <span>Service Charge</span>
        <span>£<?php echo number_format((float)$service_fee, 2); ?></span>
    </div>

    <div class="summary-line">
        <span>Bag Charge</span>
        <span>£<?php echo number_format((float)$bag_fee, 2); ?></span>
    </div>
    
    <div class="summary-line">
        <span>Delivery</span>
        <span>£<?php echo number_format($delivery_charge, 2); ?></span>
    </div>

    <div class="summary-line">
        <span>Tip</span>
        <span>£<?php echo number_format(max(0, (float)$tips), 2); ?></span>
    </div>

    <div class="summary-line bold-total">
        <span>TOTAL</span>
        <span>£<?php echo number_format(floatval($order->total_price), 2); ?></span>
    </div>
    
    <div class="summary-line" style="margin-top: 10px; font-size: 24px;">
        <span>METHOD:</span>
        <span><?php echo esc_html($payment_method); ?></span>
    </div>
</div>

            <div class="customer-section">
                <span class="cust-name"><?php echo esc_html($order->full_name); ?></span>
                <span class="cust-phone"><?php echo esc_html($order->phone); ?></span>
            </div>

            <?php if($order->order_type === 'delivery'): ?>
                <div class="cust-addr-list">
    <span class="notes-label">DELIVERY ADDRESS:</span>
    
    <?php 
    // Scenario 1: Use the pre-formatted address list if available
    if (!empty($addr_list)): ?>
        <?php foreach($addr_list as $line): ?>
            <span class="addr-item"><?php echo esc_html($line); ?></span>
        <?php endforeach; ?>

    <?php 
    // Scenario 2: Use the single 'address' text field if list is empty
    elseif (!empty($order->address)): ?>
        <span class="addr-item"><?php echo nl2br(esc_html($order->address)); ?></span>

    <?php 
    // Scenario 3: Fallback to individual database columns if everything else is empty
    else: 
        $parts = [];
        if (!empty($order->flat_no))      $parts[] = "Flat " . $order->flat_no;
        if (!empty($order->door_no))      $parts[] = "Door " . $order->door_no;
        if (!empty($order->building_name)) $parts[] = $order->building_name;
        if (!empty($order->road_name))     $parts[] = $order->road_name;
        if (!empty($order->postcode))      $parts[] = strtoupper($order->postcode);

        if (!empty($parts)):
            foreach ($parts as $part): ?>
                <span class="addr-item"><?php echo esc_html($part); ?></span>
            <?php endforeach; 
        else: ?>
            <span class="addr-item" style="color: #999;">No address provided</span>
        <?php endif; ?>
    <?php endif; ?>
</div>
            <?php endif; ?>

            <?php if (!empty($delivery_notes)) : ?>
                <div class="notes-box">
                    <span class="notes-label">DELIVERY NOTE:</span>
                    <?php echo esc_html($delivery_notes); ?>
                </div>
            <?php endif; ?>

            <div class="footer">
                *** ORDER COPY ***<br>
                #<?php echo esc_html($display_id); ?><br>
                *** END OF ORDER ***
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}