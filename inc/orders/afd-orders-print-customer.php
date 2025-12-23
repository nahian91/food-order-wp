<?php
if (!defined('ABSPATH')) exit;

/**
 * 1. GET DYNAMIC ORDER DATA
 */
$order_id = intval($_GET['order_id']);
$order    = get_post($order_id);

if (!$order) {
    wp_die('Order not found.');
}

// Item Retrieval (Handles both common meta keys)
$items = get_post_meta($order_id, 'order_items', true);
if (empty($items)) {
    $items = get_post_meta($order_id, 'items', true) ?: [];
}

// Financials
$total = get_post_meta($order_id, 'total_price', true) ?: '0.00';

// Dynamic Customer Info
$cust_name    = get_post_meta($order_id, 'customer_name', true) ?: 'Guest Customer';
$cust_phone   = get_post_meta($order_id, 'phone', true) ?: 'N/A';
$cust_email   = get_post_meta($order_id, 'email', true) ?: 'N/A';
$cust_address = get_post_meta($order_id, 'address', true);
$order_type   = get_post_meta($order_id, 'order_type', true) ?: 'Delivery';
$order_status = get_post_meta($order_id, 'status', true) ?: 'Pending';

// Date Formatting
$wp_date_format = get_option('date_format');
$formatted_date = get_the_date($wp_date_format, $order_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo esc_html(get_the_title($order_id)); ?></title>
    <style>
        /* A4 Page Setup */
        @page { size: A4; margin: 10mm; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            margin: 0; padding: 0; color: #1e293b; background: #fff; line-height: 1.4;
        }
        .invoice-container { width: 190mm; margin: auto; padding: 5mm; }

        /* Header & Branding */
        .header { 
            display: flex; justify-content: space-between; align-items: flex-start; 
            border-bottom: 3px solid #4338ca; padding-bottom: 20px; margin-bottom: 25px; 
        }
        .brand h1 { margin: 0; color: #4338ca; font-size: 30px; font-weight: 900; text-transform: uppercase; letter-spacing: -1px; }
        .brand p { margin: 2px 0; font-size: 13px; color: #64748b; font-weight: 500; }
        
        .meta-box { text-align: right; }
        .meta-box h2 { margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; }
        .meta-box p { margin: 0; font-size: 14px; color: #64748b; font-weight: 600; }
        .status-badge { 
            display: inline-block; padding: 3px 12px; border-radius: 5px; font-size: 11px; 
            font-weight: 800; background: #e0e7ff; color: #4338ca; margin-top: 8px; text-transform: uppercase;
        }

        /* Customer Info Grid */
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .details-box h3 { font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 8px; font-weight: 700; }
        .details-box p { margin: 0; font-size: 15px; font-weight: 600; color: #334155; }
        .address-box { 
            background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; 
            font-weight: 400 !important; color: #475569 !important; font-size: 14px !important;
        }

        /* Items Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th { background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; padding: 12px 15px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #475569; }
        .col-qty { text-align: center; font-weight: 700; width: 10%; }
        .col-price { text-align: right; width: 15%; }
        .col-total { text-align: right; width: 20%; font-weight: 800; color: #0f172a; }

        /* Summary Area */
        .footer-flex { display: flex; justify-content: space-between; align-items: flex-start; }
        .notes-section { width: 55%; font-size: 13px; color: #64748b; }
        .totals-section { width: 35%; background: #f8fafc; padding: 20px; border-radius: 12px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 15px; }
        .row.grand { 
            margin-top: 12px; padding-top: 12px; border-top: 2px solid #e2e8f0; 
            color: #4338ca; font-size: 22px; font-weight: 900; 
        }

        /* Branding Footer */
        .print-footer { 
            margin-top: 50px; text-align: center; font-size: 11px; color: #94a3b8; 
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
                <p>Phone: +44 208 443 2500</p>
                <p>Email: info@spiceofindia.online</p>
            </div>
            <div class="meta-box">
                <h2>INVOICE</h2>
                <p>Order #<?php echo esc_html(get_the_title($order_id)); ?></p>
                <p>Date: <?php echo $formatted_date; ?></p>
                <span class="status-badge"><?php echo esc_html($order_status); ?></span>
            </div>
        </header>

        <section class="details-grid">
            <div class="details-box">
                <h3>Customer Details</h3>
                <p><?php echo esc_html($cust_name); ?></p>
                <p style="font-weight:400; color:#64748b; font-size: 13px; margin-top:5px;">
                    <strong>Tel:</strong> <?php echo esc_html($cust_phone); ?><br>
                    <strong>Email:</strong> <?php echo esc_html($cust_email); ?>
                </p>
            </div>
            <div class="details-box">
                <h3><?php echo esc_html($order_type); ?> Address</h3>
                <p class="address-box">
                    <?php 
                    if (!empty($cust_address)) {
                        echo nl2br(esc_html($cust_address));
                    } else {
                        echo "<strong>STORE COLLECTION</strong><br>524 Hertford Road, London, EN3 5SS";
                    }
                    ?>
                </p>
            </div>
        </section>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-price">Unit Price</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): 
                    $price = (float)($item['price'] ?? 0);
                    $qty   = (int)($item['qty'] ?? 1);
                    $line_total = $price * $qty;
                ?>
                <tr>
                    <td><strong><?php echo esc_html($item['name']); ?></strong></td>
                    <td class="col-qty"><?php echo $qty; ?></td>
                    <td class="col-price">£<?php echo number_format($price, 2); ?></td>
                    <td class="col-total">£<?php echo number_format($line_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <footer class="footer-flex">
            <div class="notes-section">
                <p style="margin:0 0 5px 0; color:#0f172a; font-weight:700;">Thank You!</p>
                <p style="margin:0;">We appreciate your business. If you have any questions about this invoice, please reach out to us at info@spiceofindia.online.</p>
                <p style="margin-top:15px; font-weight:700; color:#4338ca;">Enjoy your Spice of India meal!</p>
            </div>
            <div class="totals-section">
                <div class="row">
                    <span>Subtotal</span>
                    <span>£<?php echo number_format((float)$total, 2); ?></span>
                </div>
                <div class="row">
                    <span>Delivery Fee</span>
                    <span>£0.00</span>
                </div>
                <div class="row grand">
                    <span>Total</span>
                    <span>£<?php echo number_format((float)$total, 2); ?></span>
                </div>
            </div>
        </footer>

        <div class="print-footer">
            Spice of India • 524 Hertford Road, London, EN3 5SS • www.spiceofindia.online
        </div>
    </div>

</body>
</html>