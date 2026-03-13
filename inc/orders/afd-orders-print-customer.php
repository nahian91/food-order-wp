<?php
if (!defined('ABSPATH')) exit;

/**
 * 1. GET DYNAMIC ORDER DATA FROM CUSTOM TABLE
 */
global $wpdb;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$table_name = $wpdb->prefix . 'afd_food_orders';

$order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));

if (!$order) {
    wp_die('Order not found.');
}

/**
 * 2. DATA HANDLING WITH SAFETY FALLBACKS
 */
$items          = json_decode($order->items_json ?? '[]', true) ?: [];
$display_id     = !empty($order->display_id) ? $order->display_id : 'INV-' . $order->id;
$order_date     = !empty($order->order_date) ? date('d/m/Y H:i', strtotime($order->order_date)) : date('d/m/Y H:i');

// Financials
$subtotal       = floatval($order->subtotal ?? 0);
$discount_val   = floatval($order->discount_amount ?? 0);
$service_fee    = floatval($order->service_fee ?? 0);
$bag_fee        = floatval($order->bag_fee ?? 0);
$tip            = floatval($order->tip ?? 0);
$delivery_charge = floatval($order->delivery_charge ?? 0);
$grand_total    = floatval($order->total_price ?? 0);

// Customer Info
$cust_name      = $order->full_name ?? 'Valued Customer';
$cust_phone     = $order->phone ?? 'N/A';
$cust_email     = $order->email ?? '';
$cust_address   = $order->address ?? '';
$order_type     = $order->order_type ?? 'delivery'; 
$order_status   = $order->status ?? 'Confirmed';
$scheduled      = $order->scheduled_time ?? 'ASAP';
$pay_method     = $order->payment_method ?? 'Cash';

// Notes
$k_notes        = $order->kitchen_notes ?? '';
$d_notes        = $order->delivery_notes ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - #<?php echo esc_html($display_id); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 10mm; }
        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; padding: 0; color: #1e293b; background: #fff; line-height: 1.5;
        }
        .invoice-container { width: 190mm; margin: auto; padding: 5mm; }

        /* Header */
        .header { 
            display: flex; justify-content: space-between; align-items: flex-start; 
            border-bottom: 2px solid #e2e8f0; padding-bottom: 30px; margin-bottom: 30px; 
        }
        .brand h1 { margin: 0; color: #d63638; font-size: 28px; font-weight: 800; text-transform: uppercase; }
        .brand p { margin: 2px 0; font-size: 13px; color: #64748b; }
        
        .meta-box { text-align: right; }
        .meta-box h2 { margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .meta-box p { margin: 0; font-size: 14px; color: #64748b; font-weight: 500; }
        
        .status-badge { 
            display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 11px; 
            font-weight: 700; background: #fef2f2; color: #d63638; margin-top: 10px; text-transform: uppercase;
            border: 1px solid #fee2e2;
        }

        /* Information Grid */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .info-box h3 { font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 700; }
        .info-box p { margin: 0; font-size: 14px; font-weight: 600; color: #334155; }
        .info-box span { display: block; font-size: 13px; color: #64748b; font-weight: 400; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; padding: 12px 15px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: top; }
        .col-qty { text-align: center; font-weight: 700; width: 50px; }
        .col-total { text-align: right; font-weight: 700; color: #0f172a; width: 100px; }
        .variant-text { display: block; font-size: 12px; color: #64748b; font-weight: 400; font-style: italic; }

        /* Footer Layout */
        .footer-layout { display: flex; justify-content: space-between; gap: 40px; }
        .notes-area { flex: 1; }
        .totals-area { width: 280px; }

        .note-card { 
            background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 12px; border-left: 4px solid #e2e8f0;
        }
        .note-card strong { display: block; font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 3px; }
        .note-card p { margin: 0; font-size: 13px; color: #475569; font-style: italic; }

        /* Totals */
        .total-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px; color: #475569; }
        .total-row.discount { color: #10b981; font-weight: 600; }
        .total-row.grand { 
            margin-top: 15px; padding-top: 15px; border-top: 2px solid #f1f5f9; 
            color: #d63638; font-size: 20px; font-weight: 800; 
        }

        .print-footer { 
            margin-top: 60px; text-align: center; font-size: 11px; color: #94a3b8; 
            border-top: 1px solid #f1f5f9; padding-top: 20px; 
        }

        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="invoice-container">
        <header class="header">
            <div class="brand">
                <h1>Spice of India</h1>
                <p>524 Hertford Road, London, EN3 5SS</p>
                <p>Tel: +44 208 443 2500 | spiceofindia.online</p>
            </div>
            <div class="meta-box">
                <h2>INVOICE</h2>
                <p>#<?php echo esc_html($display_id); ?></p>
                <p><?php echo esc_html($order_date); ?></p>
                <span class="status-badge"><?php echo esc_html($order_status); ?></span>
            </div>
        </header>

        <section class="info-grid">
            <div class="info-box">
                <h3>Customer</h3>
                <p><?php echo esc_html($cust_name); ?></p>
                <span><?php echo esc_html($cust_phone); ?></span>
                <span><?php echo esc_html($cust_email); ?></span>
            </div>
            <div class="info-box">
                <h3>Address</h3>
                <p>
                    <?php 
                    $type_clean = strtolower($order_type);
                    echo ($type_clean === 'pickup' || $type_clean === 'collection') ? 'Store Collection' : nl2br(esc_html($cust_address)); 
                    ?>
                </p>
            </div>
            <div class="info-box">
                <h3>Details</h3>
                <p><?php echo ucfirst(esc_html($order_type)); ?></p>
                <span>Scheduled: <strong><?php echo esc_html($scheduled); ?></strong></span>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th class="col-qty">Qty</th>
                    <th>Item Description</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $item): 
                    $i_base_price = floatval($item['price'] ?? 0);
                    $i_v_price    = floatval($item['vPrice'] ?? 0);
                    $i_qty        = intval($item['qty'] ?? 1);
                    
                    // Calculation: (Base Price + Variant Price) * Quantity
                    $unit_price   = $i_base_price + $i_v_price;
                    $row_total    = $unit_price * $i_qty;
                ?>
                <tr>
                    <td class="col-qty"><?php echo $i_qty; ?>x</td>
                    <td>
                        <strong><?php echo esc_html($item['name'] ?? 'Unknown Item'); ?></strong>
                        <?php if($i_v_price > 0): ?>
                            <span class="variant-text">+ Variation/Extra: £<?php echo number_format($i_v_price, 2); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">£<?php echo number_format($unit_price, 2); ?></td>
                    <td class="col-total">£<?php echo number_format($row_total, 2); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="footer-layout">
            <div class="notes-area">
                <?php if(!empty($k_notes)): ?>
                    <div class="note-card">
                        <strong>Kitchen Notes:</strong>
                        <p><?php echo nl2br(esc_html($k_notes)); ?></p>
                    </div>
                <?php endif; ?>

                <?php if(!empty($d_notes) && strtolower($order_type) !== 'pickup'): ?>
                    <div class="note-card">
                        <strong>Delivery Notes:</strong>
                        <p><?php echo nl2br(esc_html($d_notes)); ?></p>
                    </div>
                <?php endif; ?>
                
                <p style="font-size: 12px; color: #94a3b8; margin-top: 20px;">
                    Thank you for your order. If you have any allergies, please call us immediately.
                </p>
            </div>

            <div class="totals-area">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>£<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <?php if($discount_val > 0): ?>
                <div class="total-row discount">
                    <span>Discount</span>
                    <span>-£<?php echo number_format($discount_val, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>Service Fee</span>
                    <span>£<?php echo number_format($service_fee, 2); ?></span>
                </div>
                <?php if(strtolower($order_type) !== 'pickup' && strtolower($order_type) !== 'collection'): ?>
                <div class="total-row">
                    <span>Delivery Fee</span>
                    <span>£<?php echo number_format($delivery_charge, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>Bag Charge</span>
                    <span>£<?php echo number_format($bag_fee, 2); ?></span>
                </div>
                <?php if($tip > 0): ?>
                <div class="total-row">
                    <span>Driver Tip</span>
                    <span>£<?php echo number_format($tip, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row grand">
                    <span>Total Due</span>
                    <span>£<?php echo number_format($grand_total, 2); ?></span>
                </div>
                
                <p style="text-align: right; font-size: 11px; color: #94a3b8; margin-top: 10px;">
                    Payment: <strong><?php echo strtoupper(esc_html($pay_method)); ?></strong>
                </p>
            </div>
        </div>

        <div class="print-footer">
            Spice of India • 524 Hertford Road, London, EN3 5SS • www.spiceofindia.online
        </div>
    </div>

</body>
</html>