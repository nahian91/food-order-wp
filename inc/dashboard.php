<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# 1. DASHBOARD UI (New Orders Only with Blink Effect)
--------------------------------------------------------------*/
function fd_dashboard_tab() {
    global $wpdb;
    
    // Fetch only orders where meta_key 'status' is 'pending'
    $new_orders = get_posts([
        'post_type'   => 'food_order', 
        'numberposts' => -1, // Show all new orders
        'post_status' => 'publish',
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_query'  => [
            [
                'key'     => 'status',
                'value'   => 'pending',
                'compare' => '='
            ]
        ]
    ]);
    ?>

    <style>
        :root { --res-primary: #d63638; --res-dark: #1d2327; --res-border: #ccd0d4; }
        
        .afd-dashboard-wrap { margin-top: 20px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; }
        
        /* Table Styles */
        #fd-orders-table { width: 100%; border-spacing: 0; background: #fff; border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,.05); }
        #fd-orders-table thead th { background: #fafafa; padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; text-align: left; }
        #fd-orders-table td { padding: 15px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }

        /* Status Badges */
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid transparent; }
        
        /* Blink Animation for New/Pending Orders */
        .status-badge.pending { 
            background: #fff9e6; 
            color: #856404; 
            border-color: #ffeeba; 
            animation: afd-blink-status 1.5s infinite;
        }

        @keyframes afd-blink-status {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); background: #fdf2cd; }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Item Rows */
        .afd-qty-badge { background: #f0f0f1; color: var(--res-dark); font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-right: 8px; border: 1px solid #dcdcde; }
        .afd-item-row { display: flex; align-items: center; padding: 4px 0; border-bottom: 1px dashed #eee; }
        .afd-item-row:last-child { border-bottom: none; }

        /* Buttons */
        .fd-btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #dcdcde; background: #fff; color: #2c3338; }
        .fd-btn-print { background: var(--res-primary); color: #fff; border-color: var(--res-primary); margin-left: 5px; }
        .fd-btn .dashicons { font-size: 16px; width: 16px; height: 16px; margin-top: 2px; }
    </style>

    <div class="wrap afd-dashboard-wrap">
        <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <h1 style="font-weight: 800; font-size: 24px; color: var(--res-dark); margin: 0;">
                <?php _e('Incoming Orders', 'text-domain'); ?>
            </h1>
            <span style="background: var(--res-primary); color: #fff; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                <?php echo count($new_orders); ?> NEW
            </span>
        </div>

        <table id="fd-orders-table">
            <thead>
                <tr>
                    <th width="80">Order</th>
                    <th>Customer</th>
                    <th width="30%">Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="text-align: right;">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($new_orders) : ?>
                    <?php foreach ($new_orders as $post) : 
                        $order_id = $post->ID;
                        $customer = get_post_meta($order_id, 'customer_name', true) ?: 'Guest';
                        $items    = get_post_meta($order_id, 'items', true);
                        $total    = get_post_meta($order_id, 'total_price', true) ?: '0.00';
                        $status   = strtolower(get_post_meta($order_id, 'status', true) ?: 'pending');
                        
                        $edit_url  = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id . '&action=edit');
                        $print_url = admin_url('admin.php?page=awesome_food_delivery&tab=orders&order_id=' . $order_id . '&action=print');
                    ?>
                        <tr>
                            <td><strong>#<?php echo $order_id; ?></strong></td>
                            <td>
                                <div style="font-weight: 700; color: #1d2327;"><?php echo esc_html($customer); ?></div>
                                <code style="background:none; padding:0; color:#a7aaad; font-size: 11px;">
                                    <?php echo get_the_date('M j, g:i a', $order_id); ?>
                                </code>
                            </td>
                            <td>
                                <?php if (is_array($items)) : 
                                    foreach (array_slice($items, 0, 3) as $item) : ?>
                                        <div class="afd-item-row">
                                            <span class="afd-qty-badge"><?php echo intval($item['qty']); ?></span>
                                            <span class="afd-item-name"><?php echo esc_html($item['name']); ?></span>
                                        </div>
                                    <?php endforeach; 
                                    if(count($items) > 3) echo '<small style="color:var(--res-primary); font-weight:600;">+ ' . (count($items)-3) . ' more...</small>';
                                endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--res-primary); font-size: 16px;">
                                    <?php echo number_format((float)$total, 2); ?> €
                                </strong>
                            </td>
                            <td>
                                <span class="status-badge <?php echo esc_attr($status); ?>">
                                    ● <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a class="fd-btn" href="<?php echo esc_url($edit_url); ?>">
                                    <span class="dashicons dashicons-edit"></span> Accept / Edit
                                </a>
                                <a class="fd-btn fd-btn-print" href="<?php echo esc_url($print_url); ?>" target="_blank">
                                    <span class="dashicons dashicons-printer"></span> Print
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6" style="text-align:center; padding:60px; color:#a7aaad;">
                        <span class="dashicons dashicons-smiley" style="font-size: 40px; width: 40px; height: 40px; display: block; margin: 0 auto 10px;"></span>
                        No new orders at the moment.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}