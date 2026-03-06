<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enterprise Reports - Restaurant Red Enterprise Analytics
 * VERSION: 3.0
 * * FEATURES:
 * - Real-time "Today" Pulse & Selected Period Analytics
 * - Global Date Range filtering via GET
 * - Peak Order Time (Hourly Intelligence)
 * - 5 Best Selling Items (Current vs Last 7 Days)
 * - 5 Best Categories (Current vs Last 7 Days)
 * - Print-Optimized Layout
 */
function fd_reports_tab() {
    // 1. INPUT FILTERS & CORE SETTINGS
    $afon_currency = '£';
    $filter_from   = isset($_GET['fd_from']) ? sanitize_text_field(wp_unslash($_GET['fd_from'])) : current_time('Y-m-d');
    $filter_to     = isset($_GET['fd_to']) ? sanitize_text_field(wp_unslash($_GET['fd_to'])) : current_time('Y-m-d');
    
    $today_str     = current_time('Y-m-d');
    $seven_days_ag = date('Y-m-d', strtotime('-7 days'));

    // 2. DATA QUERIES
    // Query A: Orders within the selected filter range
    $range_args = [
        'post_type'   => 'food_order',
        'numberposts' => -1,
        'date_query'  => [
            [
                'after'     => $filter_from,
                'before'    => $filter_to,
                'inclusive' => true,
            ],
        ],
    ];
    $filtered_orders = get_posts($range_args);
    
    // Query B: Orders from the last 7 days (for comparison)
    $week_orders = get_posts([
        'post_type'   => 'food_order', 
        'numberposts' => -1, 
        'date_query'  => [['after' => $seven_days_ag, 'inclusive' => true]]
    ]);

    // 3. ANALYTICS ENGINE INITIALIZATION
    $stats = [
        'range' => [
            'count' => 0, 
            'rev'   => 0, 
            'items' => [], 
            'cats'  => [], 
            'hours' => array_fill(0, 24, 0)
        ],
        'week' => [
            'items' => [], 
            'cats'  => []
        ]
    ];

    // 4. PROCESS FILTERED RANGE DATA
    foreach ( $filtered_orders as $o ) {
        $meta  = get_post_custom($o->ID);
        $price = floatval($meta['total_price'][0] ?? 0);
        
        $stats['range']['count']++;
        $stats['range']['rev'] += $price;

        // Peak Time (Hour) Logic
        $hour = (int) get_the_date('H', $o->ID);
        $stats['range']['hours'][$hour]++;

        // Item & Category Breakdown
        $items_json = $meta['order_items'][0] ?? '[]';
        $items_data = json_decode($items_json, true);
        if ( is_array($items_data) ) {
            foreach ( $items_data as $it ) {
                $name = $it['name'] ?? 'Unknown Item';
                $cat  = $it['category'] ?? 'General';
                $qty  = intval($it['qty'] ?? 1);
                
                $stats['range']['items'][$name] = ( $stats['range']['items'][$name] ?? 0 ) + $qty;
                $stats['range']['cats'][$cat]   = ( $stats['range']['cats'][$cat] ?? 0 ) + $qty;
            }
        }
    }

    // 5. PROCESS 7-DAY COMPARISON DATA
    foreach ( $week_orders as $wo ) {
        $items = json_decode(get_post_meta($wo->ID, 'order_items', true) ?: '[]', true);
        if ( is_array($items) ) {
            foreach ( $items as $it ) {
                $name = $it['name'] ?? 'Unknown Item';
                $cat  = $it['category'] ?? 'General';
                $qty  = intval($it['qty'] ?? 1);
                
                $stats['week']['items'][$name] = ( $stats['week']['items'][$name] ?? 0 ) + $qty;
                $stats['week']['cats'][$cat]   = ( $stats['week']['cats'][$cat] ?? 0 ) + $qty;
            }
        }
    }

    // Calculate Peak Hour
    arsort($stats['range']['hours']);
    $peak_hour = array_key_first($stats['range']['hours']);
    $peak_time_formatted = date("g:00 A", strtotime("$peak_hour:00"));

    // Sorting Helper for Top 5
    $get_top_5 = function($arr) { 
        arsort($arr); 
        return array_slice($arr, 0, 5, true); 
    };
    ?>

    <style>
        :root { --r-red: #d63638; --r-dark: #1e293b; --r-gray: #64748b; --r-bg: #f8fafc; --r-blue: #2563eb; }
        .r-container { padding: 30px; background: var(--r-bg); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif; }
        
        /* Header & Print */
        .r-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .r-header h1 { margin: 0; font-size: 28px; font-weight: 900; color: var(--r-dark); }
        .r-print-btn { background: #fff; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .r-print-btn:hover { background: #f1f5f9; }

        /* Filter Section */
        .r-filter-wrap { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; display: flex; align-items: flex-end; gap: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .r-filter-group { flex: 1; }
        .r-filter-group label { display: block; font-size: 12px; font-weight: 800; color: var(--r-dark); margin-bottom: 8px; text-transform: uppercase; }
        .r-filter-group input { width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 14px; }
        .r-submit-btn { background: var(--r-red); color: #fff; border: none; padding: 0 30px; height: 42px; border-radius: 8px; font-weight: 700; cursor: pointer; }

        /* KPI Grid */
        .r-kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        .r-card { background: #fff; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-top: 5px solid var(--r-red); }
        .r-card label { display: block; font-size: 11px; font-weight: 700; color: var(--r-gray); text-transform: uppercase; letter-spacing: 1px; }
        .r-card .big-val { font-size: 34px; font-weight: 900; color: var(--r-dark); display: block; margin-top: 10px; }
        .r-card.peak { border-top-color: var(--r-blue); }
        .r-card.peak .big-val { color: var(--r-blue); }

        /* Module Grid */
        .r-mod-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .r-module { background: #fff; border-radius: 15px; border: 1px solid #e2e8f0; padding: 25px; }
        .r-module h3 { margin: 0 0 20px 0; font-size: 18px; font-weight: 900; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; }
        .r-module h3 span { font-size: 10px; background: var(--r-dark); color: #fff; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; }

        .r-table { width: 100%; border-collapse: collapse; }
        .r-table th { text-align: left; font-size: 11px; color: var(--r-gray); text-transform: uppercase; padding-bottom: 12px; }
        .r-table td { padding: 12px 0; border-bottom: 1px solid #f8fafc; font-size: 14px; color: var(--r-dark); }
        .r-qty-tag { background: #fee2e2; color: var(--r-red); padding: 3px 10px; border-radius: 12px; font-weight: 800; font-size: 11px; }

        @media print {
            .r-filter-wrap, .r-print-btn, #adminmenumain, #wpadminbar { display: none !important; }
            .r-container { padding: 0; background: #fff; }
            .r-card, .r-module { box-shadow: none; border: 1px solid #eee; }
        }
    </style>

    <div class="r-container">
        <header class="r-header">
            <div>
                <h1>Report</h1>
                <p style="color: var(--r-gray); margin: 5px 0 0;">
                    Report Period: <strong><?php echo date('M j, Y', strtotime($filter_from)); ?></strong> — <strong><?php echo date('M j, Y', strtotime($filter_to)); ?></strong>
                </p>
            </div>
        </header>

        <form method="get" class="r-filter-wrap">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
            <input type="hidden" name="tab" value="reports">
            
            <div class="r-filter-group">
                <label>Date From</label>
                <input type="date" name="fd_from" value="<?php echo esc_attr($filter_from); ?>">
            </div>
            
            <div class="r-filter-group">
                <label>Date To</label>
                <input type="date" name="fd_to" value="<?php echo esc_attr($filter_to); ?>">
            </div>
            
            <button type="submit" class="r-submit-btn">Run Report</button>
            <a href="admin.php?page=awesome_food_delivery&tab=reports" style="font-size: 12px; color: var(--r-gray); text-decoration: none;">Reset Today</a>
        </form>

        <div class="r-kpi-grid">
            <div class="r-card">
                <label>Total Orders</label>
                <span class="big-val"><?php echo $stats['range']['count']; ?></span>
            </div>
            <div class="r-card">
                <label>Gross Revenue</label>
                <span class="big-val"><?php echo $afon_currency . number_format($stats['range']['rev'], 2); ?></span>
            </div>
            <div class="r-card peak">
                <label>Peak Order Time</label>
                <span class="big-val"><?php echo $peak_time_formatted; ?></span>
            </div>
        </div>

        <div class="r-mod-grid">
            <div class="r-module">
                <h3>Menu <span>Top 5 Sellers</span></h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <table class="r-table">
                            <thead><tr><th colspan="2">Selected Period</th></tr></thead>
                            <?php foreach($get_top_5($stats['range']['items']) as $name => $q): ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td align="right"><span class="r-qty-tag"><?php echo $q; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div>
                        <table class="r-table">
                            <thead><tr><th colspan="2">Last 7 Days</th></tr></thead>
                            <?php foreach($get_top_5($stats['week']['items']) as $name => $q): ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td align="right"><span class="r-qty-tag"><?php echo $q; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

            <div class="r-module">
                <h3>Category</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <table class="r-table">
                            <thead><tr><th colspan="2">Selected Period</th></tr></thead>
                            <?php foreach($get_top_5($stats['range']['cats']) as $name => $q): ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td align="right"><span class="r-qty-tag"><?php echo $q; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div>
                        <table class="r-table">
                            <thead><tr><th colspan="2">Last 7 Days</th></tr></thead>
                            <?php foreach($get_top_5($stats['week']['cats']) as $name => $q): ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td align="right"><span class="r-qty-tag"><?php echo $q; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}