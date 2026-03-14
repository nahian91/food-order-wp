<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AWESOME FOOD DELIVERY - PRO FINANCIAL AUDIT v5.0
 * Features: Summary Tiles, 3-Column Detailed Audit, Multi-Charge Support, Print-Ready
 */
function fd_reports_tab() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';
    $afon_currency = '£';
    
    // 1. DATE SELECTION LOGIC
    $filter_from = isset($_GET['fd_from']) ? sanitize_text_field($_GET['fd_from']) : current_time('Y-m-d');
    $filter_to   = isset($_GET['fd_to']) ? sanitize_text_field($_GET['fd_to']) : current_time('Y-m-d');
    
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE(order_date) BETWEEN %s AND %s ORDER BY order_date DESC",
        $filter_from, $filter_to
    ));

    // 2. ANALYTICS ENGINE
    $totals = ['orders' => 0, 'revenue' => 0];
    $stats = [
        'cash'       => ['amt' => 0, 'qty' => 0],
        'card'       => ['amt' => 0, 'qty' => 0],
        'delivery'   => ['amt' => 0, 'qty' => 0],
        'collection' => ['amt' => 0, 'qty' => 0],
        'service'    => ['amt' => 0, 'qty' => 0],
        'bag'        => ['amt' => 0, 'qty' => 0],
        'discount'   => ['amt' => 0, 'qty' => 0],
    ];

    if ( ! empty( $orders ) ) {
        foreach ( $orders as $o ) {
            $totals['orders']++;
            $price = (float)($o->total_price ?? 0);
            $totals['revenue'] += $price;
            
            // Payment Classification
            $method = isset($o->payment_method) ? strtolower($o->payment_method) : 'card';
            if ( $method === 'cash' ) {
                $stats['cash']['amt'] += $price;
                $stats['cash']['qty']++;
            } else {
                $stats['card']['amt'] += $price;
                $stats['card']['qty']++;
            }

            // Warning-Proof Surcharge Tracking
            $charges = [
                'delivery'   => isset($o->delivery_charge) ? (float)$o->delivery_charge : 0,
                'collection' => isset($o->collection_charge) ? (float)$o->collection_charge : 0,
                'service'    => isset($o->service_charge) ? (float)$o->service_charge : 0,
                'bag'        => isset($o->bag_charge) ? (float)$o->bag_charge : 0,
                'discount'   => isset($o->delivery_discount) ? (float)$o->delivery_discount : 0,
            ];

            foreach($charges as $key => $val) {
                if ($val > 0) {
                    $stats[$key]['amt'] += $val;
                    $stats[$key]['qty']++;
                }
            }
        }
    }
    ?>

    <style>
        :root { 
            --primary: #4f46e5; --success: #10b981; --danger: #ef4444; 
            --bg: #f3f4f6; --text-dark: #111827; --text-light: #6b7280; --border: #e5e7eb;
        }
        .afd-pro-wrap { 
            padding: 30px; background: var(--bg); font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; 
            max-width: 1100px; margin: 20px auto; border-radius: 12px;
        }
        
        /* Header & Filters */
        .afd-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .afd-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark); }
        
        .filter-card { 
            background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border); 
            display: flex; gap: 15px; align-items: flex-end; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; }
        .filter-card input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 600; }
        .btn-run { background: var(--primary); color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: 700; cursor: pointer; }

        /* KPI Tiles */
        .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .kpi-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border); position: relative; overflow: hidden; }
        .kpi-card::after { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--primary); }
        .kpi-card.success::after { background: var(--success); }
        .kpi-label { font-size: 12px; font-weight: 700; color: var(--text-light); text-transform: uppercase; }
        .kpi-val { font-size: 28px; font-weight: 900; color: var(--text-dark); display: block; margin-top: 8px; }

        /* Audit Table Layout */
        .table-card { background: #fff; border-radius: 12px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .table-header { padding: 15px 25px; background: #fafafa; border-bottom: 1px solid var(--border); font-weight: 800; font-size: 13px; color: var(--text-light); }
        
        .audit-table { width: 100%; border-collapse: collapse; }
        .audit-table th { text-align: left; padding: 12px 25px; font-size: 11px; text-transform: uppercase; color: var(--text-light); background: #f9fafb; border-bottom: 2px solid var(--border); }
        .audit-table td { padding: 18px 25px; border-bottom: 1px solid #f3f4f6; font-size: 15px; vertical-align: middle; }
        
        .row-label { font-weight: 600; color: var(--text-dark); }
        .row-qty { text-align: center; color: var(--text-light); font-weight: 500; }
        .row-amt { text-align: right; font-weight: 800; font-size: 18px; font-variant-numeric: tabular-nums; }
        
        .highlight-cash { background: #ecfdf5; }
        .highlight-card { background: #eff6ff; }
        .text-neg { color: var(--danger); }

        @media print { .filter-card, .btn-print { display: none !important; } .afd-pro-wrap { padding: 0; } }
    </style>

    <div class="afd-pro-wrap">
        
        <div class="afd-header">
            <div>
                <h1>Report</h1>
                <p style="color:var(--text-light); margin:5px 0 0;">Report Period: <b><?php echo date('d M Y', strtotime($filter_from)); ?></b> to <b><?php echo date('d M Y', strtotime($filter_to)); ?></b></p>
            </div>
        </div>

        <form method="get" class="filter-card">
            <input type="hidden" name="page" value="awesome_food_delivery"><input type="hidden" name="tab" value="reports">
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="fd_from" value="<?php echo esc_attr($filter_from); ?>">
            </div>
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="fd_to" value="<?php echo esc_attr($filter_to); ?>">
            </div>
            <button type="submit" class="btn-run">Generate Report</button>
        </form>

        <div class="kpi-row">
            <div class="kpi-card">
                <span class="kpi-label">Order Volume</span>
                <span class="kpi-val"><?php echo $totals['orders']; ?></span>
            </div>
            <div class="kpi-card success">
                <span class="kpi-label">Gross Revenue</span>
                <span class="kpi-val" style="color:var(--success);"><?php echo $afon_currency . number_format($totals['revenue'], 2); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">Avg. Revenue</span>
                <span class="kpi-val"><?php echo $afon_currency . number_format($totals['orders'] > 0 ? $totals['revenue'] / $totals['orders'] : 0, 2); ?></span>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">ITEMIZED TRANSACTION BREAKDOWN</div>
            <table class="audit-table">
                <thead>
                    <tr>
                        <th style="width:45%">Description</th>
                        <th style="width:25%; text-align:center;">Total</th>
                        <th style="width:30%; text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="highlight-cash">
                        <td class="row-label">Cash Payments</td>
                        <td class="row-qty"><?php echo $stats['cash']['qty']; ?> Orders</td>
                        <td class="row-amt" style="color:#065f46;"><?php echo $afon_currency . number_format($stats['cash']['amt'], 2); ?></td>
                    </tr>
                    <tr class="highlight-card">
                        <td class="row-label">Card Payments</td>
                        <td class="row-qty"><?php echo $stats['card']['qty']; ?> Orders</td>
                        <td class="row-amt"><?php echo $afon_currency . number_format($stats['card']['amt'], 2); ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">Delivery Charges</td>
                        <td class="row-qty"><?php echo $stats['delivery']['qty']; ?></td>
                        <td class="row-amt"><?php echo $afon_currency . number_format($stats['delivery']['amt'], 2); ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">Collection Charges</td>
                        <td class="row-qty"><?php echo $stats['collection']['qty']; ?></td>
                        <td class="row-amt"><?php echo $afon_currency . number_format($stats['collection']['amt'], 2); ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">Service Fees</td>
                        <td class="row-qty"><?php echo $stats['service']['qty']; ?></td>
                        <td class="row-amt"><?php echo $afon_currency . number_format($stats['service']['amt'], 2); ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">Bag Fees</td>
                        <td class="row-qty"><?php echo $stats['bag']['qty']; ?></td>
                        <td class="row-amt"><?php echo $afon_currency . number_format($stats['bag']['amt'], 2); ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">Discounts</td>
                        <td class="row-qty"><?php echo $stats['discount']['qty']; ?></td>
                        <td class="row-amt text-neg">-<?php echo $afon_currency . number_format($stats['discount']['amt'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px; text-align:right; color:var(--text-light); font-size:12px; font-style:italic;">
            System Timestamp: <?php echo date('Y-m-d H:i:s'); ?> | Secure Audit Enabled
        </div>
    </div>
    <?php
}