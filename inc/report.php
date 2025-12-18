<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Reports Tab - Restaurant Red Analytics (Feature Complete & Secure)
 */
function fd_reports_tab() {
    // 1. SECURE INPUTS (Keeps your filter variables working)
    $afon_filter_status = isset($_GET['fd_status']) ? sanitize_text_field(wp_unslash($_GET['fd_status'])) : '';
    $afon_filter_from   = isset($_GET['fd_from']) ? sanitize_text_field(wp_unslash($_GET['fd_from'])) : '';
    $afon_filter_to     = isset($_GET['fd_to']) ? sanitize_text_field(wp_unslash($_GET['fd_to'])) : '';

    // 2. PERFORMANCE (Filters via DB instead of PHP array_filter)
    $args = [
        'post_type'   => 'food_order',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ];

    if ($afon_filter_status) {
        $args['meta_query'][] = ['key' => 'status', 'value' => $afon_filter_status];
    }
    if ($afon_filter_from || $afon_filter_to) {
        $args['date_query'] = [['inclusive' => true]];
        if ($afon_filter_from) $args['date_query'][0]['after'] = $afon_filter_from;
        if ($afon_filter_to)   $args['date_query'][0]['before'] = $afon_filter_to;
    }

    $afon_filtered_orders = get_posts($args);

    // 3. LOGIC (Calculating totals)
    $afon_total_revenue = 0;
    $afon_completed = 0;
    $afon_pending = 0;
    foreach ($afon_filtered_orders as $afon_o) {
        $afon_price = floatval(get_post_meta($afon_o->ID, 'total_price', true));
        $afon_status = strtolower(get_post_meta($afon_o->ID, 'status', true) ?: 'pending');
        $afon_total_revenue += $afon_price;
        ($afon_status === 'completed') ? $afon_completed++ : $afon_pending++;
    }

    $afon_currency = '€';
    ?>

    <div class="afon-reports-wrap">
        <div class="afon-flex-header">
            <h1 style="font-weight: 800; margin: 0;"><?php esc_html_e('Analytics Overview', 'text-domain'); ?></h1>
            <button class="afon-btn-export" onclick="window.print()">
                <span class="dashicons dashicons-download"></span> <?php esc_html_e('Export PDF', 'text-domain'); ?>
            </button>
        </div>

        <div class="afon-stats-grid">
            <div class="afon-stat-card">
                <span class="afon-stat-icon dashicons dashicons-cart"></span>
                <span class="afon-stat-label"><?php esc_html_e('Total Volume', 'text-domain'); ?></span>
                <span class="afon-stat-value"><?php echo count($afon_filtered_orders); ?></span>
            </div>
            <div class="afon-stat-card afon-stat-rev">
                <span class="afon-stat-icon dashicons dashicons-chart-area"></span>
                <span class="afon-stat-label"><?php esc_html_e('Gross Revenue', 'text-domain'); ?></span>
                <span class="afon-stat-value"><?php echo number_format($afon_total_revenue, 2, '.', '') . ' ' . $afon_currency; ?></span>
            </div>
            <div class="afon-stat-card afon-stat-done">
                <span class="afon-stat-icon dashicons dashicons-yes-alt"></span>
                <span class="afon-stat-label"><?php esc_html_e('Delivered', 'text-domain'); ?></span>
                <span class="afon-stat-value"><?php echo (int) $afon_completed; ?></span>
            </div>
            <div class="afon-stat-card afon-stat-wait">
                <span class="afon-stat-icon dashicons dashicons-clock"></span>
                <span class="afon-stat-label"><?php esc_html_e('In Progress', 'text-domain'); ?></span>
                <span class="afon-stat-value"><?php echo (int) $afon_pending; ?></span>
            </div>
        </div>

        <form method="get" class="afon-filter-bar">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'awesome_food_delivery'); ?>">
            <input type="hidden" name="tab" value="reports">
            
            <div class="afon-filter-group">
                <label><?php esc_html_e('Status', 'text-domain'); ?></label>
                <select name="fd_status">
                    <option value=""><?php esc_html_e('All Statuses', 'text-domain'); ?></option>
                    <option value="completed" <?php selected($afon_filter_status,'completed'); ?>><?php esc_html_e('Completed', 'text-domain'); ?></option>
                    <option value="pending" <?php selected($afon_filter_status,'pending'); ?>><?php esc_html_e('Pending', 'text-domain'); ?></option>
                </select>
            </div>

            <div class="afon-filter-group">
                <label><?php esc_html_e('From Date', 'text-domain'); ?></label>
                <input type="date" name="fd_from" value="<?php echo esc_attr($afon_filter_from); ?>">
            </div>

            <div class="afon-filter-group">
                <label><?php esc_html_e('To Date', 'text-domain'); ?></label>
                <input type="date" name="fd_to" value="<?php echo esc_attr($afon_filter_to); ?>">
            </div>

            <button type="submit" class="button button-primary" style="background:#d63638; border-color:#d63638; height:38px; padding: 0 25px; font-weight: 700;">
                <?php esc_html_e('Filter', 'text-domain'); ?>
            </button>
            <a href="?page=awesome_food_delivery&tab=reports" style="text-decoration:none; font-size:12px; color:#646970; margin-left: 10px;"><?php esc_html_e('Reset All', 'text-domain'); ?></a>
        </form>

        <div class="afon-table-card">
            <div class="afon-table-header">
                <h3><?php esc_html_e('Transaction History', 'text-domain'); ?></h3>
            </div>
            <div style="padding: 20px 0;">
                <table id="afon-reports-table" class="widefat">
                    <thead>
                        <tr>
                            <th width="100"><?php esc_html_e('Order ID', 'text-domain'); ?></th>
                            <th><?php esc_html_e('Customer Name', 'text-domain'); ?></th>
                            <th><?php esc_html_e('Revenue', 'text-domain'); ?></th>
                            <th><?php esc_html_e('Status', 'text-domain'); ?></th>
                            <th><?php esc_html_e('Date & Time', 'text-domain'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($afon_filtered_orders as $afon_o):
                            $afon_customer = get_post_meta($afon_o->ID, 'customer_name', true) ?: 'Guest User';
                            $afon_total = floatval(get_post_meta($afon_o->ID, 'total_price', true));
                            $afon_status = strtolower(get_post_meta($afon_o->ID, 'status', true) ?: 'pending');
                            $afon_date = get_the_date('M j, Y • H:i', $afon_o->ID);
                        ?>
                        <tr>
                            <td><code class="afon-text-muted">#<?php echo (int) $afon_o->ID; ?></code></td>
                            <td class="afon-user-name"><?php echo esc_html($afon_customer); ?></td>
                            <td><strong style="color:#d63638; font-size: 15px;"><?php echo number_format($afon_total, 2, '.', ''); ?> €</strong></td>
                            <td>
                                <div class="afon-badge afon-badge-<?php echo esc_attr($afon_status); ?>">
                                    <span class="afon-badge-dot"></span> <?php echo esc_html(ucfirst($afon_status)); ?>
                                </div>
                            </td>
                            <td class="afon-email-text"><?php echo esc_html($afon_date); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
}