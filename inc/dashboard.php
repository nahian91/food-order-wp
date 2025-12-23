<?php
if (!defined('ABSPATH')) exit;

function fd_dashboard_tab() {
    // 1. STATS DATA (TODAY ONLY)
    $today_args = [
        'post_type'   => 'food_order',
        'post_status' => 'publish',
        'numberposts' => -1,
        'date_query'  => [['year' => date('Y'), 'month' => date('m'), 'day' => date('d')]],
    ];

    $todays_orders   = get_posts($today_args);
    $todays_count    = count($todays_orders);
    $todays_revenue  = 0;
    $product_stats   = [];
    $category_stats  = [];

    foreach($todays_orders as $o) {
        $todays_revenue += (float)get_post_meta($o->ID, 'total_price', true);
        $items = get_post_meta($o->ID, 'items', true);
        if (is_array($items)) {
            foreach ($items as $item) {
                $name = $item['name'];
                $qty  = intval($item['qty']);
                $product_stats[$name] = ($product_stats[$name] ?? 0) + $qty;
                
                $product_obj = get_page_by_title($name, OBJECT, 'food_item');
                if ($product_obj) {
                    $terms = wp_get_post_terms($product_obj->ID, 'food_category');
                    if (!empty($terms)) {
                        $cat_name = $terms[0]->name;
                        $category_stats[$cat_name] = ($category_stats[$cat_name] ?? 0) + $qty;
                    }
                }
            }
        }
    }
    arsort($product_stats);
    arsort($category_stats);

    // 2. LIVE PENDING COUNT (STRICT CHECK)
    // This ensures COMPLETED orders are NOT counted here.
    $pending_orders_count = count(get_posts([
        'post_type'   => 'food_order',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query'  => [[
            'key'     => 'status',
            'value'   => 'pending',
            'compare' => '=' 
        ]]
    ]));

    $all_orders_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders');
    ?>

    <style>
        :root {
            --panel-bg: #f8fafc;
            --panel-card: #ffffff;
            --panel-accent: #6366f1;
            --panel-text: #0f172a;
            --panel-muted: #64748b;
            --panel-border: #e2e8f0;
        }

        .fd-admin-summary { padding: 30px; background: var(--panel-bg); font-family: 'Inter', -apple-system, sans-serif; color: var(--panel-text); }

        /* Header */
        .fd-summary-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .fd-summary-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; }

        /* Status Banner */
        .fd-status-banner {
            padding: 20px 25px; border-radius: 20px; margin-bottom: 35px; display: flex; align-items: center; justify-content: space-between;
            background: #fff; border: 1px solid var(--panel-border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }
        .fd-status-info { display: flex; align-items: center; gap: 20px; }
        
        .fd-live-indicator {
            position: relative; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
            background: <?php echo ($pending_orders_count > 0) ? '#fff1f2' : '#f0fdf4'; ?>;
            color: <?php echo ($pending_orders_count > 0) ? '#ef4444' : '#22c55e'; ?>;
        }
        .fd-pulse {
            position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; 
            background: #ef4444; border-radius: 50%; border: 2px solid #fff;
            animation: fd-pulse-red 2s infinite;
        }
        @keyframes fd-pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .fd-view-btn {
            background: var(--panel-accent); color: #fff !important; text-decoration: none;
            padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 13px;
            display: flex; align-items: center; gap: 8px; transition: 0.2s;
        }

        /* Stat Grid */
        .fd-stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px; }
        .fd-stat-card { background: var(--panel-card); padding: 25px; border-radius: 20px; border: 1px solid var(--panel-border); }
        .fd-stat-label { display: flex; align-items: center; gap: 8px; color: var(--panel-muted); font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 15px; }
        .fd-stat-value { font-size: 28px; font-weight: 800; color: var(--panel-text); }

        /* Content Boxes */
        .fd-split-view { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .fd-content-box { background: var(--panel-card); border-radius: 20px; padding: 30px; border: 1px solid var(--panel-border); }
        .fd-content-box h3 { margin-top: 0; font-size: 18px; font-weight: 700; border-bottom: 1px solid var(--panel-border); padding-bottom: 15px; margin-bottom: 20px; }
        .fd-list-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .fd-qty-pill { background: #eef2ff; color: var(--panel-accent); font-weight: 700; padding: 4px 10px; border-radius: 10px; font-size: 12px; }
    </style>

    <div class="fd-admin-summary">
        
        <div class="fd-summary-header">
            <div>
                <h1>Daily Dashboard</h1>
                <p>Tracking restaurant performance for today.</p>
            </div>
            <div style="font-size: 14px; font-weight: 700; background: white; padding: 10px 20px; border-radius: 10px; border: 1px solid var(--panel-border);">
                <?php echo date('l, jS F Y'); ?>
            </div>
        </div>

        <div class="fd-status-banner">
            <div class="fd-status-info">
                <div class="fd-live-indicator">
                    <span class="dashicons dashicons-bell"></span>
                    <?php if ($pending_orders_count > 0) : ?><span class="fd-pulse"></span><?php endif; ?>
                </div>
                <div>
                    <h2 style="margin:0; font-size:18px; font-weight:800;"><?php echo $pending_orders_count; ?> Pending Orders</h2>
                    <p style="margin:2px 0 0; color:var(--panel-muted);">Orders awaiting preparation.</p>
                </div>
            </div>
            <a href="<?php echo esc_url($all_orders_url); ?>" class="fd-view-btn">
                View All Orders <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>

        <div class="fd-stat-grid">
            <div class="fd-stat-card">
                <div class="fd-stat-label"><span class="dashicons dashicons-cart"></span> Today's Orders</div>
                <div class="fd-stat-value"><?php echo $todays_count; ?></div>
            </div>
            <div class="fd-stat-card">
                <div class="fd-stat-label"><span class="dashicons dashicons-chart-area"></span> Today's Revenue</div>
                <div class="fd-stat-value">£<?php echo number_format($todays_revenue, 2); ?></div>
            </div>
            <div class="fd-stat-card">
                <div class="fd-stat-label"><span class="dashicons dashicons-clock"></span> Current Time</div>
                <div class="fd-stat-value"><?php echo date('H:i'); ?></div>
            </div>
        </div>

        <div class="fd-split-view">
            <div class="fd-content-box">
                <h3>Top Selling Products</h3>
                <?php if (!empty($product_stats)) : 
                    foreach (array_slice($product_stats, 0, 5) as $name => $qty) : ?>
                    <div class="fd-list-row">
                        <span style="font-weight:600;"><?php echo esc_html($name); ?></span>
                        <span class="fd-qty-pill"><?php echo $qty; ?> Sold</span>
                    </div>
                <?php endforeach; else : ?>
                    <p style="color:var(--panel-muted);">No sales today.</p>
                <?php endif; ?>
            </div>

            <div class="fd-content-box">
                <h3>Top Categories</h3>
                <?php if (!empty($category_stats)) : 
                    foreach (array_slice($category_stats, 0, 5) as $cat => $qty) : ?>
                    <div class="fd-list-row">
                        <span style="font-weight:600;"><?php echo esc_html($cat); ?></span>
                        <span class="fd-qty-pill"><?php echo $qty; ?> Items</span>
                    </div>
                <?php endforeach; else : ?>
                    <p style="color:var(--panel-muted);">No category data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}