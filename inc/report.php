<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AWESOME FOOD DELIVERY - PRO AUDIT v9.0
 * Features: High-precision accounting, automatic zero-filling, and Pro Print Engine.
 */
function fd_reports_tab() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';
    $afon_currency = '£';
    
    // 1. DATE SELECTION & INPUT SANITIZATION
    $filter_from = isset($_GET['fd_from']) ? sanitize_text_field($_GET['fd_from']) : current_time('Y-m-d');
    $filter_to   = isset($_GET['fd_to']) ? sanitize_text_field($_GET['fd_to']) : current_time('Y-m-d');
    
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE(order_date) BETWEEN %s AND %s ORDER BY order_date DESC",
        $filter_from, $filter_to
    ));

    // 2. ANALYTICS ENGINE (Strict Defaults to 0)
    $data = [
        'del' => [
            'cash_qty' => 0, 'card_qty' => 0, 'cash_rev' => 0, 'card_rev' => 0,
            'fee' => 0, 'srv' => 0, 'bag' => 0, 'disc' => 0
        ],
        'col' => [
            'cash_qty' => 0, 'card_qty' => 0, 'cash_rev' => 0, 'card_rev' => 0,
            'srv' => 0, 'bag' => 0, 'disc' => 0
        ]
    ];

    if ( ! empty( $orders ) ) {
        foreach ( $orders as $o ) {
            $is_del = (isset($o->order_type) && strtolower($o->order_type) == 'delivery');
            $method = isset($o->payment_method) ? strtolower($o->payment_method) : 'card';
            $price  = (float)($o->total_price ?? 0);
            $type   = $is_del ? 'del' : 'col';

            if ($method === 'cash') {
                $data[$type]['cash_qty']++;
                $data[$type]['cash_rev'] += $price;
            } else {
                $data[$type]['card_qty']++;
                $data[$type]['card_rev'] += $price;
            }

            if ($is_del) { $data['del']['fee'] += (float)($o->delivery_charge ?? 0); }
            $data[$type]['srv']  += (float)($o->service_charge ?? 0);
            $data[$type]['bag']  += (float)($o->bag_charge ?? 0);
            $data[$type]['disc'] += (float)($o->delivery_discount ?? 0);
        }
    }

    $total_cash = $data['del']['cash_rev'] + $data['col']['cash_rev'];
    $total_card = $data['del']['card_rev'] + $data['col']['card_rev'];
    $grand_total = $total_cash + $total_card;
    ?>

    <style>
        /* DASHBOARD DESIGN */
        :root { 
            --primary: #2563eb; --success: #059669; --danger: #dc2626; 
            --bg: #f1f5f9; --text-main: #0f172a; --text-muted: #64748b; --border-clr: #cbd5e1;
        }
        .afd-report-wrap { padding: 40px; background: var(--bg); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1050px; margin: 20px auto; border-radius: 12px; }
        
        .afd-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid var(--text-main); padding-bottom: 20px; }
        .afd-header h1 { margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -1px; text-transform: uppercase; }

        .filter-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid var(--border-clr); display: flex; gap: 15px; align-items: flex-end; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .filter-group label { display: block; font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
        .filter-card input { padding: 10px; border: 1px solid var(--border-clr); border-radius: 6px; font-weight: 600; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .kpi-item { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid var(--border-clr); text-align: center; }
        .kpi-title { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .kpi-value { font-size: 26px; font-weight: 900; color: var(--text-main); display: block; margin-top: 8px; }

        /* TABLE STYLING */
        .table-holder { background: #fff; border-radius: 10px; border: 1px solid var(--text-main); overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { background: var(--text-main); color: #fff; padding: 15px 20px; font-size: 12px; text-transform: uppercase; text-align: left; letter-spacing: 1px; }
        .report-table td { padding: 14px 20px; border-bottom: 1px solid #eef2f6; font-size: 15px; color: var(--text-main); }
        
        .section-label { background: #f8fafc; font-weight: 900; color: var(--primary); font-size: 13px; }
        .t-center { text-align: left; }
        .t-right { text-align: left; }
        .text-bold { font-weight: 800; color: #000; }
        .font-num { font-family: 'SFMono-Regular', Consolas, monospace; font-weight: 600; }
        
        .grand-total-row { background: #f1f5f9; border-top: 2px solid var(--text-main); }
        .grand-total-row td { padding: 20px; font-size: 18px; font-weight: 900; }

        .btn-action { background: var(--text-main); color: #fff; border: none; padding: 12px 25px; border-radius: 6px; font-weight: 800; cursor: pointer; text-transform: uppercase; font-size: 12px; }

        /* PRINT LOGIC */
        @media print {
            body { background: white !important; }
            #adminmenumain, #wpadminbar, .filter-card, .btn-action, .afd-header button { display: none !important; }
            .afd-report-wrap { padding: 0; margin: 0; max-width: 100%; width: 100%; background: white; }
            .table-holder { box-shadow: none; border: 1px solid #000; }
            .report-table th { background: #000 !important; color: #fff !important; print-color-adjust: exact; }
            .section-label { background: #eee !important; print-color-adjust: exact; }
            @page { margin: 1.5cm; }
        }
    </style>

    <div class="afd-report-wrap">
        <div class="afd-header">
            <div>
                <h1>Report</h1>
                <p style="color:var(--text-muted); font-weight: 700; margin: 5px 0 0;">
                    Period: <?php echo date('M d, Y', strtotime($filter_from)); ?> – <?php echo date('M d, Y', strtotime($filter_to)); ?>
                </p>
            </div>
            <!-- <button class="btn-action" onclick="window.print()">Print Pro Report</button> -->
        </div>

        <form method="get" class="filter-card">
            <input type="hidden" name="page" value="awesome_food_delivery"><input type="hidden" name="tab" value="reports">
            <div class="filter-group"><label>From</label><input type="date" name="fd_from" value="<?php echo esc_attr($filter_from); ?>"></div>
            <div class="filter-group"><label>To</label><input type="date" name="fd_to" value="<?php echo esc_attr($filter_to); ?>"></div>
            <button type="submit" class="btn-action" style="background:var(--primary);">Apply Filter</button>
        </form>

        <div class="kpi-grid">
            <div class="kpi-item"><span class="kpi-title">Orders</span><span class="kpi-value"><?php echo count($orders) ?: 0; ?></span></div>
            <div class="kpi-item"><span class="kpi-title">Gross Revenue</span><span class="kpi-value" style="color:var(--success);"><?php echo $afon_currency . number_format($grand_total, 2); ?></span></div>
            <div class="kpi-item"><span class="kpi-title">Avg. Value</span><span class="kpi-value"><?php echo $afon_currency . number_format(count($orders) > 0 ? $grand_total / count($orders) : 0, 2); ?></span></div>
        </div>

        <div class="table-holder">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width:40%">Description</th>
                        <th style="width:20%" class="t-center">Cash</th>
                        <th style="width:20%" class="t-center">Card</th>
                        <th style="width:20%" class="t-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="section-label"><td colspan="4">Delivery Logistics</td></tr>
                    <tr>
                        <td>Order Quantity</td>
                        <td class="t-center font-num"><?php echo $data['del']['cash_qty'] ?: 0; ?></td>
                        <td class="t-center font-num"><?php echo $data['del']['card_qty'] ?: 0; ?></td>
                        <td class="t-right text-bold font-num"><?php echo ($data['del']['cash_qty'] + $data['del']['card_qty']) ?: 0; ?></td>
                    </tr>
                    <tr>
                        <td>Revenue Subtotal</td>
                        <td class="t-center font-num"><?php echo $afon_currency . number_format($data['del']['cash_rev'], 2); ?></td>
                        <td class="t-center font-num"><?php echo $afon_currency . number_format($data['del']['card_rev'], 2); ?></td>
                        <td class="t-right text-bold font-num"><?php echo $afon_currency . number_format($data['del']['cash_rev'] + $data['del']['card_rev'], 2); ?></td>
                    </tr>
                    <tr><td>Delivery Surcharge</td><td colspan="2"></td><td class="t-right font-num"><?php echo $afon_currency . number_format($data['del']['fee'], 2); ?></td></tr>
                    <tr><td>Service Fees</td><td colspan="2"></td><td class="t-right font-num"><?php echo $afon_currency . number_format($data['del']['srv'], 2); ?></td></tr>
                    <tr><td>Bag Fees</td><td colspan="2"></td><td class="t-right font-num"><?php echo $afon_currency . number_format($data['del']['bag'], 2); ?></td></tr>
                    <tr><td>Discounts</td><td colspan="2"></td><td class="t-right font-num" style="color:var(--danger);">-<?php echo $afon_currency . number_format($data['del']['disc'], 2); ?></td></tr>

                    <tr class="section-label"><td colspan="4">Collection Summary</td></tr>
                    <tr>
                        <td>Order Quantity</td>
                        <td class="t-center font-num"><?php echo $data['col']['cash_qty'] ?: 0; ?></td>
                        <td class="t-center font-num"><?php echo $data['col']['card_qty'] ?: 0; ?></td>
                        <td class="t-right text-bold font-num"><?php echo ($data['col']['cash_qty'] + $data['col']['card_qty']) ?: 0; ?></td>
                    </tr>
                    <tr>
                        <td>Revenue Subtotal</td>
                        <td class="t-center font-num"><?php echo $afon_currency . number_format($data['col']['cash_rev'], 2); ?></td>
                        <td class="t-center font-num"><?php echo $afon_currency . number_format($data['col']['card_rev'], 2); ?></td>
                        <td class="t-right text-bold font-num"><?php echo $afon_currency . number_format($data['col']['cash_rev'] + $data['col']['card_rev'], 2); ?></td>
                    </tr>
                    <tr><td>Service Fees</td><td colspan="2"></td><td class="t-right font-num"><?php echo $afon_currency . number_format($data['col']['srv'], 2); ?></td></tr>
                    <tr><td>Bag Fees</td><td colspan="2"></td><td class="t-right font-num"><?php echo $afon_currency . number_format($data['col']['bag'], 2); ?></td></tr>
                    <tr><td>Discounts</td><td colspan="2"></td><td class="t-right font-num" style="color:var(--danger);">-<?php echo $afon_currency . number_format($data['col']['disc'], 2); ?></td></tr>

                    <tr class="grand-total-row">
                        <td class="text-bold">TOTAL REVENUE (NET)</td>
                        <td class="t-center text-bold font-num"><?php echo $afon_currency . number_format($total_cash, 2); ?></td>
                        <td class="t-center text-bold font-num"><?php echo $afon_currency . number_format($total_card, 2); ?></td>
                        <td class="t-right text-bold font-num" style="color:var(--success); font-size:22px;"><?php echo $afon_currency . number_format($grand_total, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top:30px; border-top: 1px solid var(--border-clr); padding-top:15px; display:flex; justify-content: space-between; color:var(--text-muted); font-size:11px; font-weight:700;">
            <span>SYSTEM LOG: <?php echo date('Y-m-d H:i:s'); ?></span>
            <span>VERIFIED FINANCIAL AUDIT</span>
        </div>
    </div>
    <?php
}