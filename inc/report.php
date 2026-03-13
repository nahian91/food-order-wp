<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AWESOME FOOD DELIVERY - ENTERPRISE REPORTS v3.1
 * Features: Live Analytics, Top Items & Categories, Order Management, Unified Alarms
 */
function fd_reports_tab() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';
    $afon_currency = '£';
    
    // --- 1. ACTION HANDLER: SAVE CHANGES ---
    if (isset($_POST['afd_save_report_order'])) {
        $order_id = intval($_POST['order_id']);
        $wpdb->update($table_name, [
            'full_name' => sanitize_text_field($_POST['full_name']),
            'phone'     => sanitize_text_field($_POST['phone']),
            'address'   => sanitize_textarea_field($_POST['address']),
            'notes'     => sanitize_textarea_field($_POST['notes']),
        ], ['id' => $order_id]);
        echo "<div class='notice notice-success is-dismissible'><p>Order #$order_id updated successfully.</p></div>";
    }

    // --- 2. VIEW HANDLER: EDIT ORDER ---
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['order_id'])) {
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['order_id'])));
        if ($order) { ?>
            <div class="wrap afd-reports-wrap">
                <h1 style="font-weight:900;">Edit Order #<?php echo $order->display_id; ?></h1>
                <a href="admin.php?page=awesome_food_delivery&tab=reports" class="button" style="margin-bottom:20px;">← Back to Analytics</a>
                <form method="post" action="" style="background:#fff; padding:30px; border-radius:15px; border:1px solid #ccd0d4; max-width:850px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
                    <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                    <table class="form-table">
                        <tr><th>Customer Name</th><td><input type="text" name="full_name" class="regular-text" value="<?php echo esc_attr($order->full_name); ?>"></td></tr>
                        <tr><th>Phone Number</th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($order->phone); ?>"></td></tr>
                        <tr><th>Delivery Address</th><td><textarea name="address" rows="3" class="large-text"><?php echo esc_textarea($order->address); ?></textarea></td></tr>
                        <tr><th>Kitchen Notes</th><td><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea($order->notes); ?></textarea></td></tr>
                    </table>
                    <div style="margin-top:20px;">
                        <button type="submit" name="afd_save_report_order" class="button button-primary" style="height:45px; padding:0 35px; font-weight:bold; font-size:14px;">UPDATE ORDER DETAILS</button>
                    </div>
                </form>
            </div>
        <?php return; }
    }

    // --- 3. CORE ANALYTICS LOGIC ---
    $filter_from = isset($_GET['fd_from']) ? sanitize_text_field($_GET['fd_from']) : current_time('Y-m-d');
    $filter_to   = isset($_GET['fd_to']) ? sanitize_text_field($_GET['fd_to']) : current_time('Y-m-d');
    
    // Notification Logic (Sync with Dashboard)
    $alarm_trigger_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE order_status = 'pending' AND scheduled_time = 'asap'");

    // Range Data
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE(order_date) BETWEEN %s AND %s ORDER BY id DESC",
        $filter_from, $filter_to
    ));

    $stats = [
        'total_rev' => 0,
        'count'     => 0,
        'items'     => [],
        'cats'      => [],
        'hours'     => array_fill(0, 24, 0)
    ];

    foreach ($orders as $o) {
        $stats['count']++;
        $stats['total_rev'] += (float)$o->total_price;
        $hour = (int)date('H', strtotime($o->order_date));
        $stats['hours'][$hour]++;

        $items = json_decode($o->items_json, true);
        if (is_array($items)) {
            foreach ($items as $it) {
                $name = $it['name'] ?? 'Unknown';
                $cat  = $it['category'] ?? 'General';
                $qty  = intval($it['qty'] ?? 1);
                
                $stats['items'][$name] = ($stats['items'][$name] ?? 0) + $qty;
                $stats['cats'][$cat]   = ($stats['cats'][$cat] ?? 0) + $qty;
            }
        }
    }
    arsort($stats['items']);
    $top_sellers = array_slice($stats['items'], 0, 5, true);
    
    arsort($stats['cats']);
    $top_categories = array_slice($stats['cats'], 0, 5, true);

    arsort($stats['hours']);
    $peak_hour = array_key_first($stats['hours']);
    ?>

    <style>
        :root { --r-blue: #6366f1; --r-green: #22c55e; --r-bg: #f8fafc; --r-border: #e2e8f0; --r-purple: #a855f7; }
        .afd-reports-wrap { padding: 25px; background: var(--r-bg); font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
        
        #afd-alarm-unlock { 
            background: #fffbeb; border: 1px solid #fef3c7; padding: 15px; margin-bottom: 25px; border-radius: 12px; 
            text-align: center; cursor: pointer; font-weight: bold; color: #92400e; display: flex; align-items: center; justify-content: center; gap: 10px; 
        }

        .afd-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; }
        .afd-card { background: #fff; border-radius: 16px; border: 1px solid var(--r-border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; height: fit-content; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .kpi-box { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid var(--r-border); border-top: 5px solid var(--r-blue); }
        .kpi-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-val { font-size: 32px; font-weight: 900; color: #0f172a; margin-top: 5px; display: block; }

        .filter-section { display: flex; gap: 15px; align-items: flex-end; margin-bottom: 30px; padding: 20px; background: #fff; border-radius: 16px; border: 1px solid var(--r-border); }
        .filter-section input { height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; }

        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { background: #f1f5f9; padding: 15px; text-align: left; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; }
        .report-table td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .afd-btn { text-decoration: none; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e2e8f0; background: #fff; color: #334155; }
        .afd-btn-edit { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 12px; }
        .badge-blue { background: #eef2ff; color: var(--r-blue); }
        .badge-purple { background: #f5f3ff; color: var(--r-purple); }
    </style>

    <div class="afd-reports-wrap">

        <div class="afd-header-flex">
            <div>
                <h1 style="font-weight:900; margin:0; font-size:28px;margin-bottom: 10px">Report</h1>
                <p style="color:#64748b; margin:5px 0 0;">Visualizing data from <?php echo date('M j', strtotime($filter_from)); ?> to <?php echo date('M j', strtotime($filter_to)); ?>.</p>
            </div>
        </div>

        <form method="get" class="filter-section">
            <input type="hidden" name="page" value="awesome_food_delivery"><input type="hidden" name="tab" value="reports">
            <div><label class="kpi-label">From</label><input type="date" name="fd_from" value="<?php echo $filter_from; ?>"></div>
            <div><label class="kpi-label">To</label><input type="date" name="fd_to" value="<?php echo $filter_to; ?>"></div>
            <button type="submit" class="button button-primary" style="height:40px; font-weight:bold; padding:0 25px;">Update Range</button>
        </form>

        <div class="kpi-grid">
            <div class="kpi-box"><span class="kpi-label">Order Volume</span><span class="kpi-val"><?php echo $stats['count']; ?></span></div>
            <div class="kpi-box"><span class="kpi-label">Gross Revenue</span><span class="kpi-val"><?php echo $afon_currency . number_format($stats['total_rev'], 2); ?></span></div>
            <div class="kpi-box" style="border-top-color:var(--r-green);"><span class="kpi-label">Peak Hour</span><span class="kpi-val"><?php echo date("g A", strtotime("$peak_hour:00")); ?></span></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 25px;">
            
            <div class="afd-card">
                <div style="padding:20px; border-bottom:1px solid #f1f5f9; font-weight:800; text-transform:uppercase; font-size:12px;">Detailed Order History</div>
                <table class="report-table">
                    <thead>
                        <tr><th>ID</th><th>Customer</th><th>Status</th><th>Total</th><th style="text-align:right;">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o) : ?>
                        <tr>
                            <td><strong>#<?php echo $o->display_id; ?></strong></td>
                            <td><div style="font-weight:700;"><?php echo esc_html($o->full_name); ?></div></td>
                            <td><span style="font-size:10px; font-weight:800;"><?php echo strtoupper($o->order_status); ?></span></td>
                            <td style="font-weight:800; color:var(--r-green);"><?php echo $afon_currency . number_format($o->total_price, 2); ?></td>
                            <td align="right">
                                <a class="afd-btn afd-btn-edit" href="?page=awesome_food_delivery&tab=orders&order_id=<?php echo $o->id; ?>&action=edit"><span class="dashicons dashicons-edit"></span></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display: flex; flex-direction: column; gap: 25px;">
                <div class="afd-card">
                    <div style="padding:15px; border-bottom:1px solid #f1f5f9; font-weight:800; text-transform:uppercase; font-size:11px; color:var(--r-blue);">Top 5 Selling Items</div>
                    <div style="padding:10px;">
                        <?php foreach ($top_sellers as $name => $qty) : ?>
                            <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #f8fafc;">
                                <span style="font-weight:600; font-size:13px;"><?php echo esc_html($name); ?></span>
                                <span class="badge badge-blue"><?php echo $qty; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="afd-card">
                    <div style="padding:15px; border-bottom:1px solid #f1f5f9; font-weight:800; text-transform:uppercase; font-size:11px; color:var(--r-purple);">Top Categories</div>
                    <div style="padding:10px;">
                        <?php foreach ($top_categories as $cat => $qty) : ?>
                            <div style="display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #f8fafc;">
                                <span style="font-weight:600; font-size:13px;"><?php echo esc_html($cat); ?></span>
                                <span class="badge badge-purple"><?php echo $qty; ?> Items</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        const audio = document.getElementById('afdOrderAlarm');
        const unlockBtn = document.getElementById('afd-alarm-unlock');
        const pendingNew = <?php echo (int)$alarm_trigger_count; ?>;
        
        if (sessionStorage.getItem('afd_audio_active') === 'true') {
            unlockBtn.style.display = 'none';
            if (pendingNew > 0) { audio.play().catch(e => { unlockBtn.style.display = 'flex'; }); }
        }
        
        unlockBtn.addEventListener('click', function() {
            sessionStorage.setItem('afd_audio_active', 'true');
            unlockBtn.style.display = 'none';
            if (pendingNew > 0) { audio.play(); }
        });

        var refresh = 60;
        setInterval(function(){ 
            refresh--; $('#timer-count').text(refresh); 
            if(refresh <= 0) location.reload(); 
        }, 1000);
    });
    </script>
    <?php
}