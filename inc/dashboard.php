<?php
if (!defined('ABSPATH')) exit;

/**
 * Dashboard Tab - SaaS UI with Persistent Continuous Alarm
 * Features: Live Clock, Stats, Top Items, and Browser-Safe Audio Loop
 * Database: afd_food_orders
 */
function fd_dashboard_tab() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'afd_food_orders';

    // --- 1. DATA CALCULATIONS ---
    $today_date = date('Y-m-d');
    
    $todays_orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE(order_date) = %s", 
        $today_date
    ));

    $todays_count   = count($todays_orders);
    $todays_revenue = 0;
    $product_stats  = [];
    $category_stats = [];

    foreach($todays_orders as $o) {
        $todays_revenue += (float)$o->total_price;
        $items = json_decode($o->items_json, true);
        
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

    $pending_orders_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE order_status = 'pending'");
    $all_orders_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders');
    ?>

    <meta http-equiv="refresh" content="30">

    <style>
        :root {
            --afd-bg: #f8fafc;
            --afd-card: #ffffff;
            --afd-accent: #6366f1;
            --afd-text: #0f172a;
            --afd-muted: #64748b;
            --afd-border: #e2e8f0;
            --afd-success: #22c55e;
            --afd-danger: #ef4444;
            --afd-warning: #f59e0b;
        }

        .afd-admin-summary { padding: 30px; background: var(--afd-bg); font-family: 'Inter', -apple-system, sans-serif; color: var(--afd-text); }
        
        /* Audio Unlocker Bar */
        #afd-audio-unlock {
            background: #fffbeb; border: 1px solid #fef3c7; padding: 15px;
            margin-bottom: 25px; border-radius: 14px; display: flex; align-items: center; justify-content: center; 
            gap: 12px; font-weight: 700; color: #92400e; cursor: pointer; transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        #afd-audio-unlock:hover { background: #fef3c7; transform: translateY(-1px); }

        /* Header Area */
        .afd-summary-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .afd-summary-header h1 { font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -1px; }
        
        .afd-datetime-container {
            display: flex; align-items: center; background: #ffffff; padding: 10px 18px;
            border-radius: 14px; border: 1px solid var(--afd-border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); gap: 15px;
        }
        .afd-time-part { 
            display: flex; align-items: center; gap: 8px; padding-left: 15px; border-left: 2px solid #f1f5f9; 
            color: var(--afd-accent); font-weight: 800; font-size: 16px; font-family: monospace; 
        }

        /* Status Banner */
        .afd-status-banner {
            padding: 20px 25px; border-radius: 20px; margin-bottom: 35px; display: flex; align-items: center; justify-content: space-between;
            background: #fff; border: 1px solid var(--afd-border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }
        .afd-live-indicator {
            position: relative; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
            background: <?php echo ($pending_orders_count > 0) ? '#fff1f2' : '#f0fdf4'; ?>;
            color: <?php echo ($pending_orders_count > 0) ? '#ef4444' : '#22c55e'; ?>;
        }
        .afd-pulse {
            position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; 
            background: var(--afd-danger); border-radius: 50%; border: 2px solid #fff;
            animation: afd-pulse-red 2s infinite;
        }
        @keyframes afd-pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Stats Grid */
        .afd-stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 40px; }
        .afd-stat-card { background: var(--afd-card); padding: 25px; border-radius: 20px; border: 1px solid var(--afd-border); }
        .afd-stat-value { font-size: 28px; font-weight: 800; color: var(--afd-text); }
        .afd-view-btn { background: var(--afd-accent); color: #fff !important; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Split View Lists */
        .afd-split-view { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .afd-content-box { background: var(--afd-card); border-radius: 20px; padding: 30px; border: 1px solid var(--afd-border); }
        .afd-list-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .afd-qty-pill { background: #eef2ff; color: var(--afd-accent); font-weight: 700; padding: 4px 10px; border-radius: 10px; font-size: 12px; }
    </style>

    <div class="afd-admin-summary">
        <audio id="afdOrderAlarm" preload="auto" loop>
            <source src="https://assets.mixkit.co/active_storage/sfx/2041/2041-preview.mp3" type="audio/mpeg">
        </audio>

        <div id="afd-audio-unlock">
            <span class="dashicons dashicons-megaphone"></span> 
            CLICK HERE TO ENABLE LIVE AUDIO ALARMS
        </div>

        <div class="afd-summary-header">
            <div>
                <h1>Daily Dashboard</h1>
                <p>Live performance tracking from order database.</p>
            </div>
            <div class="afd-datetime-container">
                <div class="afd-date-part"><span class="dashicons dashicons-calendar-alt"></span> <?php echo date('l, jS F Y'); ?></div>
                <div class="afd-time-part">
                    <span class="dashicons dashicons-clock"></span>
                    <span id="afdLiveClock">00:00:00</span>
                </div>
            </div>
        </div>

        <div class="afd-status-banner">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="afd-live-indicator">
                    <span class="dashicons dashicons-bell"></span>
                    <?php if ($pending_orders_count > 0) : ?><span class="afd-pulse"></span><?php endif; ?>
                </div>
                <div>
                    <h2 style="margin:0; font-size:18px; font-weight:800;"><?php echo $pending_orders_count; ?> Pending Orders</h2>
                    <p style="margin:2px 0 0; color:var(--afd-muted);">Checks every 30s. Alarm loops until orders are managed.</p>
                </div>
            </div>
            <a href="<?php echo esc_url($all_orders_url); ?>" class="afd-view-btn">
                Manage Orders <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>

        <div class="afd-stat-grid">
            <div class="afd-stat-card">
                <div style="color:var(--afd-muted); font-size:12px; font-weight:700; text-transform:uppercase; margin-bottom:10px;">Today's Orders</div>
                <div class="afd-stat-value"><?php echo $todays_count; ?></div>
            </div>
            <div class="afd-stat-card">
                <div style="color:var(--afd-muted); font-size:12px; font-weight:700; text-transform:uppercase; margin-bottom:10px;">Today's Revenue</div>
                <div class="afd-stat-value">£<?php echo number_format($todays_revenue, 2); ?></div>
            </div>
        </div>

        <div class="afd-split-view">
            <div class="afd-content-box">
                <h3 style="margin-top:0; border-bottom:1px solid var(--afd-border); padding-bottom:15px;">Top Items (Today)</h3>
                <?php if (!empty($product_stats)) : 
                    foreach (array_slice($product_stats, 0, 5) as $name => $qty) : ?>
                    <div class="afd-list-row">
                        <span style="font-weight:600;"><?php echo esc_html($name); ?></span>
                        <span class="afd-qty-pill"><?php echo $qty; ?> Sold</span>
                    </div>
                <?php endforeach; else : ?>
                    <p style="color:var(--afd-muted);">No sales data today.</p>
                <?php endif; ?>
            </div>

            <div class="afd-content-box">
                <h3 style="margin-top:0; border-bottom:1px solid var(--afd-border); padding-bottom:15px;">Categories</h3>
                <?php if (!empty($category_stats)) : 
                    foreach (array_slice($category_stats, 0, 5) as $cat => $qty) : ?>
                    <div class="afd-list-row">
                        <span style="font-weight:600;"><?php echo esc_html($cat); ?></span>
                        <span class="afd-qty-pill"><?php echo $qty; ?> Items</span>
                    </div>
                <?php endforeach; else : ?>
                    <p style="color:var(--afd-muted);">No category data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. LIVE CLOCK ---
        function updateClock() {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                               now.getMinutes().toString().padStart(2, '0') + ':' + 
                               now.getSeconds().toString().padStart(2, '0');
            document.getElementById('afdLiveClock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- 2. PERSISTENT AUDIO LOGIC ---
        const audio = document.getElementById('afdOrderAlarm');
        const unlockBtn = document.getElementById('afd-audio-unlock');
        const pendingCount = <?php echo (int)$pending_orders_count; ?>;

        // Check if user previously enabled audio in this session
        if (sessionStorage.getItem('afd_audio_enabled') === 'true') {
            unlockBtn.style.display = 'none';
            if (pendingCount > 0) {
                audio.play().catch(err => {
                    // Browser might still block if session expired
                    unlockBtn.style.display = 'flex';
                });
            }
        }

        // Unlock click handler
        unlockBtn.addEventListener('click', function() {
            sessionStorage.setItem('afd_audio_enabled', 'true');
            unlockBtn.style.display = 'none';
            
            if (pendingCount > 0) {
                audio.play();
            } else {
                // Play and pause immediately to confirm permission to browser
                audio.play().then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                    alert("Audio notifications active! I will loop when a new order arrives.");
                });
            }
        });
    });
    </script>
    <?php
}