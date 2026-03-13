<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Customers Tab - CRM SaaS Design 
 * COMPLETE INTEGRATION: DataTables + Separate Email + Detailed Address Profile + Address Column
 */
function fd_customers_tab() {
    global $wpdb;
    $afon_currency = get_option( 'fd_currency', '£' );
    $afon_page_slug = 'awesome_food_delivery';
    $table_orders = $wpdb->prefix . 'afd_food_orders';

    // ----- 1. SINGLE CUSTOMER VIEW (Nice Detailed Format) -----
    if ( isset( $_GET['view'] ) ) :
        $afon_user_id = intval( $_GET['view'] );
        $afon_user    = get_userdata( $afon_user_id );
        
        if ( ! $afon_user ) {
            echo '<div class="notice notice-error"><p>User not found.</p></div>';
            return;
        }

        // Fetching individual address components
        $u_phone     = get_user_meta($afon_user_id, 'fd_user_phone', true);
        $u_flat      = get_user_meta($afon_user_id, 'fd_flat_no', true);
        $u_building  = get_user_meta($afon_user_id, 'fd_building', true);
        $u_door      = get_user_meta($afon_user_id, 'fd_door_no', true);
        $u_road      = get_user_meta($afon_user_id, 'fd_road_name', true);
        $u_postcode  = get_user_meta($afon_user_id, 'fd_user_postcode', true);
        $u_extra     = get_user_meta($afon_user_id, 'address', true);

        $afon_orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_orders WHERE customer_id = %d ORDER BY order_date DESC",
            $afon_user_id
        ));
        
        $afon_total_spent = 0;
        if($afon_orders) {
            foreach ( $afon_orders as $o ) { $afon_total_spent += floatval( $o->total_price ); }
        }
        ?>
        <div class="wrap afon-wrap">
            <div class="afon-profile-header" style="display: flex; align-items: center; gap: 20px; background: #0f172a; color:#fff; padding: 30px; border-radius: 12px; margin-bottom:20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                <?php echo get_avatar( $afon_user_id, 80, '', '', [ 'style' => 'border-radius:15px; border:3px solid rgba(255,255,255,0.1);' ] ); ?>
                <div style="flex-grow:1;">
                    <h1 style="margin:0; font-size:26px; font-weight:900; color:#fff;"><?php echo esc_html( $afon_user->display_name ); ?></h1>
                    <p style="margin:5px 0; color:#94a3b8; font-size:14px;"><?php echo esc_html( $afon_user->user_email ); ?></p>
                </div>
                <div style="display:flex; gap:15px;">
                    <div style="background:rgba(255,255,255,0.05); padding:12px 20px; border-radius:10px; text-align:center;">
                        <span style="font-size:10px; color:#94a3b8; text-transform:uppercase; display:block; letter-spacing:1px;">Orders</span>
                        <strong style="font-size:20px;"><?php echo count( $afon_orders ); ?></strong>
                    </div>
                    <div style="background:rgba(255,255,255,0.05); padding:12px 20px; border-radius:10px; text-align:center; border:1px solid rgba(74, 222, 128, 0.2);">
                        <span style="font-size:10px; color:#94a3b8; text-transform:uppercase; display:block; letter-spacing:1px;">Revenue</span>
                        <strong style="font-size:20px; color:#4ade80;"><?php echo esc_html( $afon_currency ) . number_format( $afon_total_spent, 2 ); ?></strong>
                    </div>
                </div>
            </div>

            <div class="afon-grid-layout" style="display: grid; grid-template-columns: 320px 1fr; gap: 20px;">
                <div style="background: #fff; border-radius: 12px; border: 1px solid #ccd0d4; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 20px 0; font-size:15px; font-weight:700; border-bottom:1px solid #f1f5f9; padding-bottom:10px; color:#1e293b;">Contact & Location</h3>
                    
                    <div style="margin-bottom:15px;">
                        <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block;">Phone Number</label>
                        <span style="font-weight:600; color:#1e293b;"><?php echo esc_html($u_phone ?: '-'); ?></span>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                        <div>
                            <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block;">Flat No.</label>
                            <span style="font-weight:600; color:#1e293b;"><?php echo esc_html($u_flat ?: '-'); ?></span>
                        </div>
                        <div>
                            <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block;">Door No.</label>
                            <span style="font-weight:600; color:#1e293b;"><?php echo esc_html($u_door ?: '-'); ?></span>
                        </div>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block;">Building / Estate</label>
                        <span style="font-weight:600; color:#1e293b;"><?php echo esc_html($u_building ?: '-'); ?></span>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block;">Road Name</label>
                        <span style="font-weight:600; color:#1e293b;"><?php echo esc_html($u_road ?: '-'); ?></span>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block;">Postcode</label>
                        <span style="font-weight:800; color:#d63638; font-size:18px;"><?php echo esc_html($u_postcode ?: '-'); ?></span>
                    </div>

                    <?php if($u_extra): ?>
                        <div style="margin-top:20px; background: #fff1f2; padding:12px; border-radius:8px; border:1px solid #fecdd3;">
                            <label style="font-size:9px; font-weight:900; color:#991b1b; text-transform:uppercase; display:block; margin-bottom:4px;">Delivery Notes</label>
                            <div style="font-size:12px; color:#991b1b; line-height:1.4;"><?php echo esc_html($u_extra); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background: #fff; border-radius: 12px; border: 1px solid #ccd0d4; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #eee;">
                        <h3 style="margin:0; font-size:14px; font-weight:700;">Recent Order History</h3>
                    </div>
                    <table class="wp-list-table widefat fixed striped" style="border:none;">
                        <thead>
                            <tr>
                                <th width="100">Order ID</th>
                                <th>Items</th>
                                <th width="100">Total</th>
                                <th width="110">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($afon_orders): foreach($afon_orders as $order): $items = json_decode($order->items_json, true); ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($order->display_id); ?></strong></td>
                                    <td>
                                        <div style="font-size:11px;">
                                            <?php if(is_array($items)): foreach($items as $it): ?>
                                                <div style="margin-bottom:2px;"><span style="color:#d63638; font-weight:700;"><?php echo $it['qty']; ?>x</span> <?php echo esc_html($it['name']); ?></div>
                                            <?php endforeach; endif; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo $afon_currency . number_format($order->total_price, 2); ?></strong></td>
                                    <td>
                                        <?php 
                                            $st = strtolower($order->order_status); 
                                            $color = ($st == 'completed' || $st == 'delivered') ? '#22c55e' : (($st == 'cooking') ? '#f59e0b' : '#d63638'); 
                                        ?>
                                        <span style="background:<?php echo $color; ?>; color:#fff; padding:3px 10px; border-radius:20px; font-size:10px; font-weight:800; text-transform:uppercase;"><?php echo $st; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" style="text-align:center; padding:30px;">No orders found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p style="margin-top:20px;"><a href="?page=<?php echo $afon_page_slug; ?>&tab=customers" class="button">← Back to Directory</a></p>
        </div>
        <?php return; endif;

    // ----- 2. DIRECTORY VIEW (RESTORED DATATABLES) -----
    $afon_all_users = get_users( [ 'number' => -1 ] ); 
    ?>

    <div class="wrap afon-wrap">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1 style="font-weight:900; font-size:24px;">Customer Directory</h1>
        </div>

        <div style="background:#fff; border-radius:12px; border:1px solid #ccd0d4; padding:15px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <table id="afon-users-directory-table" class="wp-list-table widefat fixed striped" style="border:none;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding-left:10px;">Customer Name</th>
                        <th>Email Address</th>
                        <th width="140">Phone Number</th>
                        <th>Address</th>
                        <th width="80">Orders</th>
                        <th width="110" style="text-align:right; padding-right:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $afon_all_users as $afon_u ) :
                        $u_id = $afon_u->ID;
                        $phone    = get_user_meta($u_id, 'fd_user_phone', true) ?: '-';
                        $door     = get_user_meta($u_id, 'fd_door_no', true);
                        $road     = get_user_meta($u_id, 'fd_road_name', true);
                        $postcode = get_user_meta($u_id, 'fd_user_postcode', true);
                        
                        // Construct display address
                        $display_addr = trim(($door ? $door . ', ' : '') . ($road ? $road : ''));
                        if (empty($display_addr)) $display_addr = '-';

                        $order_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_orders WHERE customer_id = %d", $u_id));
                    ?>
                    <tr>
                        <td style="padding-left:10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php echo get_avatar($u_id, 32, '', '', ['style'=>'border-radius:8px;']); ?>
                                <strong style="color:#1e293b;"><?php echo esc_html($afon_u->display_name); ?></strong>
                            </div>
                        </td>
                        <td style="color:#2271b1; font-weight:500;"><?php echo esc_html($afon_u->user_email); ?></td>
                        <td><?php echo esc_html($phone); ?></td>
                        <td style="font-size:12px; color:#64748b;">
                            <?php echo esc_html($display_addr); ?> <span style="color:#d63638; font-weight:700; margin-left:5px;"><?php echo esc_html($postcode); ?></span>
                        </td>
                        <td>
                            <span style="background:#f1f5f9; padding:4px 10px; border-radius:10px; font-weight:800; font-size:11px;">
                                <?php echo (int)$order_count; ?>
                            </span>
                        </td>
                        <td style="text-align:right; padding-right:10px;">
                            <a href="?page=<?php echo $afon_page_slug; ?>&tab=customers&view=<?php echo $u_id; ?>" class="button button-primary" style="border-radius:6px; font-weight:600;">View Profile</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>