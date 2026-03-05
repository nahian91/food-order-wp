<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Reports Tab - Restaurant Red Enterprise Analytics
 * * FEATURES:
 * - Real-time "Today" Pulse (Orders & Revenue)
 * - Service Fee Analysis (Delivery vs Collection)
 * - Discount Loss Tracking
 * - Peak Order Time Intelligence (Hourly Map)
 * - Menu Engineering (Best Selling Items)
 * - Transaction Ledger with Type Attribution
 */
function fd_reports_tab() {
    // 1. SECURE INPUTS & GLOBAL SETTINGS
    $afon_filter_status = isset($_GET['fd_status']) ? sanitize_text_field(wp_unslash($_GET['fd_status'])) : '';
    $afon_filter_from   = isset($_GET['fd_from']) ? sanitize_text_field(wp_unslash($_GET['fd_from'])) : '';
    $afon_filter_to     = isset($_GET['fd_to']) ? sanitize_text_field(wp_unslash($_GET['fd_to'])) : '';
    $afon_currency      = '£';

    // 2. QUERY OPTIMIZATION
    $afon_args = [
        'post_type'   => 'food_order',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ];

    if ($afon_filter_status) {
        $afon_args['meta_query'][] = [
            'key'     => 'status',
            'value'   => $afon_filter_status,
            'compare' => '='
        ];
    }
    
    if ($afon_filter_from || $afon_filter_to) {
        $afon_args['date_query'] = [['inclusive' => true]];
        if ($afon_filter_from) $afon_args['date_query'][0]['after']  = $afon_filter_from;
        if ($afon_filter_to)   $afon_args['date_query'][0]['before'] = $afon_filter_to;
    }

    $afon_orders = get_posts($afon_args);

    // 3. ANALYTICS ENGINE INITIALIZATION
    $afon_today            = current_time('Y-m-d');
    $afon_total_rev        = 0;
    $afon_completed        = 0;
    $afon_pending          = 0;
    $afon_today_count      = 0;
    $afon_today_revenue    = 0;
    
    $afon_del_fees_total   = 0;
    $afon_col_fees_total   = 0;
    $afon_del_disc_total   = 0;
    $afon_col_disc_total   = 0;

    $afon_hourly_map       = array_fill(0, 24, 0);
    $afon_item_rank        = [];

    // 4. DATA PROCESSING LOOP
    foreach ( $afon_orders as $afon_o ) {
        $afon_id    = $afon_o->ID;
        $afon_meta  = get_post_custom($afon_id);
        
        // Basic Metrics
        $afon_price  = floatval($afon_meta['total_price'][0] ?? 0);
        $afon_status = strtolower($afon_meta['status'][0] ?? 'pending');
        $afon_type   = strtolower($afon_meta['order_type'][0] ?? 'delivery');
        
        $afon_total_rev += $afon_price;
        ($afon_status === 'completed') ? $afon_completed++ : $afon_pending++;

        // Service Fee & Discount Split
        $afon_d_fee  = floatval($afon_meta['delivery_charge'][0] ?? 0);
        $afon_p_fee  = floatval($afon_meta['pickup_charge'][0] ?? 0);
        $afon_d_disc = floatval($afon_meta['delivery_discount_amount'][0] ?? 0);
        $afon_p_disc = floatval($afon_meta['pickup_discount_amount'][0] ?? 0);

        $afon_del_fees_total += $afon_d_fee;
        $afon_col_fees_total += $afon_p_fee;
        $afon_del_disc_total += $afon_d_disc;
        $afon_col_disc_total += $afon_p_disc;

        // Today Pulse Logic
        if ( get_the_date('Y-m-d', $afon_id) === $afon_today ) {
            $afon_today_count++;
            $afon_today_revenue += $afon_price;
        }

        // Hourly Heatmap Logic
        $afon_hour = (int) get_the_date('H', $afon_id);
        $afon_hourly_map[$afon_hour]++;

        // Menu Engineering (Item Popularity)
        $afon_items_json = $afon_meta['order_items'][0] ?? '[]';
        $afon_items_data = json_decode($afon_items_json, true);
        if ( is_array($afon_items_data) ) {
            foreach ( $afon_items_data as $afon_item ) {
                $afon_name = $afon_item['name'] ?? 'Unknown Item';
                $afon_qty  = intval($afon_item['qty'] ?? 1);
                $afon_item_rank[$afon_name] = ($afon_item_rank[$afon_name] ?? 0) + $afon_qty;
            }
        }
    }

    // 5. POST-PROCESSING CALCS
    arsort($afon_item_rank);
    $afon_top_sellers = array_slice($afon_item_rank, 0, 5);
    
    arsort($afon_hourly_map);
    $afon_peak_hour      = array_key_first($afon_hourly_map);
    $afon_peak_formatted = date("g:00 A", strtotime("$afon_peak_hour:00"));
    
    $afon_net_service_gain = ($afon_del_fees_total + $afon_col_fees_total) - ($afon_del_disc_total + $afon_col_disc_total);
    ?>

    <style>
        :root { --afon-red: #d63638; --afon-dark: #1e293b; --afon-gray: #64748b; --afon-bg: #f8fafc; }
        .afon-admin-container { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background: var(--afon-bg); color: var(--afon-dark); }
        
        /* Header & Print Styles */
        .afon-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        .afon-header-flex h1 { margin: 0; font-size: 28px; font-weight: 800; color: var(--afon-dark); }
        .afon-btn-print { background: #fff; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .afon-btn-print:hover { background: #f1f5f9; border-color: var(--afon-gray); }

        /* Stat Grid System */
        .afon-main-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .afon-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .afon-card.primary { background: var(--afon-dark); color: #fff; border: none; }
        .afon-card label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--afon-gray); margin-bottom: 10px; }
        .afon-card.primary label { color: #94a3b8; }
        .afon-card .afon-big-val { font-size: 26px; font-weight: 800; display: block; }
        .afon-card .afon-trend { font-size: 11px; margin-top: 8px; display: block; color: var(--afon-gray); }

        /* Info Modules */
        .afon-module-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 30px; }
        .afon-module { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; }
        .afon-module h3 { margin: 0 0 20px 0; font-size: 18px; font-weight: 800; border-left: 4px solid var(--afon-red); padding-left: 15px; }
        
        .afon-item-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .afon-item-row:last-child { border: none; }
        .afon-qty-pill { background: #fee2e2; color: var(--afon-red); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        
        /* Financial Table */
        .afon-fin-table { width: 100%; border-collapse: collapse; }
        .afon-fin-table td { padding: 10px 0; font-size: 14px; }
        .afon-fin-table .afon-label { color: var(--afon-gray); font-weight: 500; }
        .afon-fin-table .afon-amt { text-align: right; font-weight: 700; font-family: monospace; }
        .afon-neg { color: var(--afon-red); }

        /* Filter Section */
        .afon-filter-wrap { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; display: flex; align-items: flex-end; gap: 20px; }
        .afon-filter-group { flex: 1; }
        .afon-filter-group label { display: block; font-size: 12px; font-weight: 700; color: var(--afon-dark); margin-bottom: 8px; }
        .afon-filter-group input, .afon-filter-group select { width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; }
        .afon-btn-submit { background: var(--afon-red); color: #fff; border: none; padding: 0 30px; height: 42px; border-radius: 8px; font-weight: 700; cursor: pointer; }

        /* Transaction Table */
        .afon-table-container { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .afon-table { width: 100%; border-collapse: collapse; }
        .afon-table th { background: #f8fafc; padding: 15px; text-align: left; font-size: 13px; font-weight: 700; color: var(--afon-gray); border-bottom: 1px solid #e2e8f0; }
        .afon-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .afon-id-tag { color: var(--afon-gray); font-family: monospace; }
        .afon-badge { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .afon-badge-completed { background: #dcfce7; color: #166534; }
        .afon-badge-pending { background: #fef9c3; color: #854d0e; }

        @media print { .afon-filter-wrap, .afon-btn-print, #adminmenumain { display: none !important; } .afon-admin-container { background: #fff; padding: 0; } }
        @media (max-width: 1100px) { .afon-main-grid { grid-template-columns: repeat(2, 1fr); } .afon-module-grid { grid-template-columns: 1fr; } .afon-filter-wrap { flex-wrap: wrap; } }
    </style>

    <div class="afon-admin-container">
        <header class="afon-header-flex">
            <div>
                <h1><?php esc_html_e('Enterprise Insights', 'text-domain'); ?></h1>
                <p style="color: var(--afon-gray); margin: 5px 0 0;">
                    <?php echo date('l, F j, Y'); ?> • 
                    <strong><?php echo count($afon_orders); ?></strong> Orders Analyzed
                </p>
            </div>
            <button class="afon-btn-print" onclick="window.print()">
                <span class="dashicons dashicons-download"></span> <?php esc_html_e('Export CSV/PDF', 'text-domain'); ?>
            </button>
        </header>

        <div class="afon-main-grid">
            <div class="afon-card primary">
                <label><?php esc_html_e('Today Volume', 'text-domain'); ?></label>
                <span class="afon-big-val"><?php echo $afon_today_count; ?></span>
                <span class="afon-trend"><?php esc_html_e('Real-time order pulse', 'text-domain'); ?></span>
            </div>
            <div class="afon-card primary">
                <label><?php esc_html_e('Today Revenue', 'text-domain'); ?></label>
                <span class="afon-big-val"><?php echo $afon_currency . number_format($afon_today_revenue, 2); ?></span>
                <span class="afon-trend"><?php esc_html_e('Daily gross earnings', 'text-domain'); ?></span>
            </div>
            <div class="afon-card">
                <label><?php esc_html_e('Peak Order Hour', 'text-domain'); ?></label>
                <span class="afon-big-val" style="color: var(--afon-red);"><?php echo $afon_peak_formatted; ?></span>
                <span class="afon-trend"><?php esc_html_e('Based on order timestamps', 'text-domain'); ?></span>
            </div>
            <div class="afon-card">
                <label><?php esc_html_e('Total Revenue', 'text-domain'); ?></label>
                <span class="afon-big-val"><?php echo $afon_currency . number_format($afon_total_rev, 2); ?></span>
                <span class="afon-trend"><?php echo $afon_completed; ?> <?php esc_html_e('Completed orders', 'text-domain'); ?></span>
            </div>
        </div>

        <div class="afon-module-grid">
            <div class="afon-module">
                <h3><?php esc_html_e('Menu Performance (Top 5)', 'text-domain'); ?></h3>
                <?php if ( !empty($afon_top_sellers) ) : ?>
                    <?php foreach ( $afon_top_sellers as $afon_name => $afon_qty ) : ?>
                        <div class="afon-item-row">
                            <span style="font-weight: 600;"><?php echo esc_html($afon_name); ?></span>
                            <span class="afon-qty-pill"><?php echo $afon_qty; ?> <?php esc_html_e('sold', 'text-domain'); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p style="color: var(--afon-gray);"><?php esc_html_e('No item data available for this period.', 'text-domain'); ?></p>
                <?php endif; ?>
            </div>

            <div class="afon-module">
                <h3><?php esc_html_e('Fee Reconciliation', 'text-domain'); ?></h3>
                <table class="afon-fin-table">
                    <tr>
                        <td class="afon-label"><?php esc_html_e('Delivery Fees', 'text-domain'); ?></td>
                        <td class="afon-amt"><?php echo $afon_currency . number_format($afon_del_fees_total, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="afon-label"><?php esc_html_e('Collection Fees', 'text-domain'); ?></td>
                        <td class="afon-amt"><?php echo $afon_currency . number_format($afon_col_fees_total, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="afon-label"><?php esc_html_e('Total Discounts', 'text-domain'); ?></td>
                        <td class="afon-amt afon-neg">-<?php echo $afon_currency . number_format($afon_del_disc_total + $afon_col_disc_total, 2); ?></td>
                    </tr>
                    <tr style="border-top: 2px solid #f1f5f9;">
                        <td class="afon-label" style="padding-top:15px; font-weight: 800; color: var(--afon-dark);"><?php esc_html_e('Net Service Profit', 'text-domain'); ?></td>
                        <td class="afon-amt" style="padding-top:15px; font-size: 18px; color: #10b981;">
                            <?php echo $afon_currency . number_format($afon_net_service_gain, 2); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <form method="get" class="afon-filter-wrap">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'awesome_food_delivery'); ?>">
            <input type="hidden" name="tab" value="reports">
            
            <div class="afon-filter-group">
                <label><?php esc_html_e('Filter by Status', 'text-domain'); ?></label>
                <select name="fd_status">
                    <option value=""><?php esc_html_e('All Orders', 'text-domain'); ?></option>
                    <option value="completed" <?php selected($afon_filter_status,'completed'); ?>><?php esc_html_e('Completed', 'text-domain'); ?></option>
                    <option value="pending" <?php selected($afon_filter_status,'pending'); ?>><?php esc_html_e('Pending', 'text-domain'); ?></option>
                </select>
            </div>

            <div class="afon-filter-group">
                <label><?php esc_html_e('Start Date', 'text-domain'); ?></label>
                <input type="date" name="fd_from" value="<?php echo esc_attr($afon_filter_from); ?>">
            </div>

            <div class="afon-filter-group">
                <label><?php esc_html_e('End Date', 'text-domain'); ?></label>
                <input type="date" name="fd_to" value="<?php echo esc_attr($afon_filter_to); ?>">
            </div>

            <button type="submit" class="afon-btn-submit">
                <?php esc_html_e('Run Report', 'text-domain'); ?>
            </button>
            <a href="?page=awesome_food_delivery&tab=reports" style="font-size: 12px; color: var(--afon-gray); margin-bottom: 12px; text-decoration: none;">Reset</a>
        </form>

        <div class="afon-table-container">
            <table class="afon-table">
                <thead>
                    <tr>
                        <th width="80"><?php esc_html_e('Order', 'text-domain'); ?></th>
                        <th><?php esc_html_e('Customer & Method', 'text-domain'); ?></th>
                        <th><?php esc_html_e('Revenue', 'text-domain'); ?></th>
                        <th><?php esc_html_e('Status', 'text-domain'); ?></th>
                        <th><?php esc_html_e('Order Time', 'text-domain'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $afon_orders ) : ?>
                        <?php foreach ( $afon_orders as $afon_o ) : 
                            $afon_name   = get_post_meta($afon_o->ID, 'customer_name', true) ?: 'Guest';
                            $afon_method = get_post_meta($afon_o->ID, 'order_type', true) ?: 'Delivery';
                            $afon_total  = floatval(get_post_meta($afon_o->ID, 'total_price', true));
                            $afon_stat   = strtolower(get_post_meta($afon_o->ID, 'status', true) ?: 'pending');
                        ?>
                            <tr>
                                <td><span class="afon-id-tag">#<?php echo $afon_o->ID; ?></span></td>
                                <td>
                                    <div style="font-weight: 700;"><?php echo esc_html($afon_name); ?></div>
                                    <div style="font-size: 10px; color: var(--afon-gray); text-transform: uppercase;"><?php echo esc_html($afon_method); ?></div>
                                </td>
                                <td><strong style="color: #10b981;"><?php echo $afon_currency . number_format($afon_total, 2); ?></strong></td>
                                <td>
                                    <span class="afon-badge afon-badge-<?php echo esc_attr($afon_stat); ?>">
                                        <?php echo ucfirst($afon_stat); ?>
                                    </span>
                                </td>
                                <td style="color: var(--afon-gray); font-size: 12px;">
                                    <?php echo get_the_date('M j, Y', $afon_o->ID); ?> <br>
                                    <span style="font-weight: 600;"><?php echo get_the_date('H:i', $afon_o->ID); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--afon-gray);">
                                <?php esc_html_e('No transactions found matching your criteria.', 'text-domain'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}