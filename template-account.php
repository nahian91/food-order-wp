<?php
/*
Template Name: Account
*/

get_header();

// 1. ACCESS CONTROL
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

global $wpdb;
$table_name   = $wpdb->prefix . 'afd_food_orders';
$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$success_msg  = '';
$currency     = '£';

/**
 * 2. RE-ORDER LOGIC (Local Storage Bridge)
 */
if (isset($_GET['action']) && $_GET['action'] === 'reorder' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $db_order = $wpdb->get_row($wpdb->prepare(
        "SELECT items_json FROM $table_name WHERE id = %d AND customer_id = %d", 
        $order_id, $user_id
    ));
    if ($db_order) {
        echo "<script>
            localStorage.setItem('fd_cart_save', " . json_encode($db_order->items_json) . ");
            window.location.href = '" . home_url('/checkout/') . "';
        </script>";
        exit;
    }
}

/**
 * 3. PROFILE UPDATE HANDLER
 */
if (isset($_POST['update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'update_user_profile')) {
    
    // Normalize Postcode (Upper Case, No Spaces)
    $raw_pc = sanitize_text_field($_POST['postcode']);
    $clean_postcode = strtoupper(str_replace(' ', '', $raw_pc));
    $phone = sanitize_text_field($_POST['phone']);

    // Core WP User Update
    wp_update_user([
        'ID'           => $user_id,
        'first_name'   => sanitize_text_field($_POST['first_name']),
        'last_name'    => sanitize_text_field($_POST['last_name']),
        'display_name' => sanitize_text_field($_POST['first_name'] . ' ' . $_POST['last_name'])
    ]);

    // Metadata Sync
    $meta_fields = [
        'fd_user_phone'    => $phone,
        'fd_flat_no'       => sanitize_text_field($_POST['flat_no']),
        'fd_building'      => sanitize_text_field($_POST['building']),
        'fd_door_no'       => sanitize_text_field($_POST['door_no']),
        'fd_road_name'     => sanitize_text_field($_POST['road_name']),
        'fd_user_postcode' => $clean_postcode,
        'address'          => sanitize_textarea_field($_POST['address']),
        'billing_phone'    => $phone,
        'billing_postcode' => $clean_postcode
    ];

    foreach ($meta_fields as $key => $value) {
        update_user_meta($user_id, $key, $value);
    }

    $success_msg = 'Your profile and delivery details have been updated.';
}

/**
 * 4. DATA COMPILATION
 */
$all_orders = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name WHERE customer_id = %d ORDER BY order_date DESC", 
    $user_id
));

$live_order = null;
if ($all_orders) {
    foreach ($all_orders as $order) {
        $status = strtolower($order->order_status);
        if (in_array($status, ['pending', 'cooking', 'rider'])) {
            $live_order = $order;
            break;
        }
    }
}

// Meta Fetch
$u_phone = get_user_meta($user_id, 'fd_user_phone', true);
$u_flat  = get_user_meta($user_id, 'fd_flat_no', true);
$u_build = get_user_meta($user_id, 'fd_building', true);
$u_door  = get_user_meta($user_id, 'fd_door_no', true);
$u_road  = get_user_meta($user_id, 'fd_road_name', true);
$u_pc    = get_user_meta($user_id, 'fd_user_postcode', true);
$u_addr  = get_user_meta($user_id, 'address', true);
?>

<style>
    :root { --primary-red: #d63638; --navy: #0f172a; --emerald: #10b981; --slate-100: #f1f5f9; }
    body { background-color: var(--slate-100); font-family: 'Inter', sans-serif; }
    
    .account-main { padding: 60px 0; min-height: 90vh; }
    
    /* Premium Nav */
    .account-sidebar .nav-link { 
        background: #fff; color: #64748b; border: none; padding: 18px 24px; 
        border-radius: 20px; font-weight: 700; margin-bottom: 15px; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        display: flex; align-items: center; gap: 15px; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
    }
    .account-sidebar .nav-link.active { 
        background: var(--primary-red) !important; color: #fff !important; 
        box-shadow: 0 15px 25px -5px rgba(214, 54, 56, 0.3); transform: translateY(-2px);
    }
    .account-sidebar .nav-link .dashicons { font-size: 20px; width: 20px; height: 20px; }

    /* Content Cards */
    .glass-card { 
        border: none; border-radius: 30px; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.03); 
        padding: 45px; background: #fff; 
    }

    /* Live Tracker */
    .tracker-box { 
        background: #fff; border: 1px solid #e2e8f0; border-radius: 35px; 
        padding: 40px; position: relative; margin-bottom: 30px; 
    }
    .live-indicator { 
        width: 12px; height: 12px; background: var(--emerald); 
        border-radius: 50%; display: inline-block; margin-right: 10px; 
        animation: pulse 2s infinite; 
    }
    .arrival-timer { 
        background: var(--navy); color: #fff; padding: 12px 25px; 
        border-radius: 20px; font-family: 'JetBrains Mono', monospace; 
        font-size: 26px; font-weight: 800; 
    }

    /* Modern Stepper */
    .stepper-wrap { position: relative; display: flex; justify-content: space-between; margin-top: 40px; }
    .stepper-bg { position: absolute; top: 27px; left: 50px; right: 50px; height: 4px; background: #f1f5f9; z-index: 1; }
    .stepper-fill { position: absolute; top: 27px; left: 50px; height: 4px; background: var(--primary-red); z-index: 2; transition: width 1.5s ease; }
    .step-node { position: relative; z-index: 3; text-align: center; width: 100px; }
    .step-icon { 
        width: 58px; height: 58px; background: #fff; border: 3px solid #f1f5f9; 
        border-radius: 20px; display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 15px; transition: 0.5s; color: #cbd5e1; 
    }
    .step-label { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    
    .step-node.active .step-icon { border-color: var(--primary-red); color: var(--primary-red); background: #fff1f2; transform: scale(1.15); }
    .step-node.done .step-icon { background: var(--emerald); border-color: var(--emerald); color: #fff; }
    .step-node.active .step-label { color: var(--navy); }

    /* Stats Grid */
    .stat-pill { background: var(--slate-100); border-radius: 25px; padding: 25px; text-align: center; border: 1px solid #e2e8f0; }
    .stat-pill h2 { font-size: 32px; font-weight: 900; color: var(--primary-red); margin: 0; }
    
    @keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
    @media (max-width: 768px) { .stepper-bg, .stepper-fill { display: none; } .stepper-wrap { flex-direction: column; gap: 25px; align-items: center; } .step-node { width: 100%; display: flex; align-items: center; gap: 20px; text-align: left; } .step-icon { margin: 0; } }
</style>

<div class="breadcrumb-area text-center text-light" style="background: var(--navy); padding: 100px 0;">
    <div class="container"><h1 class="text-white m-0 fw-bold display-4">Account Dashboard</h1></div>
</div>

<div class="account-main">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="account-sidebar nav flex-column nav-pills" id="accountTabs" role="tablist">
                    <button class="nav-link active" id="btn-dash" data-bs-toggle="pill" data-bs-target="#tab-dash" type="button"><span class="dashicons dashicons-dashboard"></span> Dashboard</button>
                    <button class="nav-link" id="btn-orders" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button"><span class="dashicons dashicons-cart"></span> My Orders</button>
                    <button class="nav-link" id="btn-profile" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button"><span class="dashicons dashicons-admin-users"></span> Edit Profile</button>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-link text-danger mt-4"><span class="dashicons dashicons-exit"></span> Logout</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="tab-content glass-card">
                    
                    <div class="tab-pane fade show active" id="tab-dash">
                        <?php if ($live_order) : 
                            $status = strtolower($live_order->order_status);
                            $expiry = strtotime($live_order->order_date) + (intval($live_order->scheduled_time) * 60);
                            $progress = ($status == 'cooking') ? "50%" : (($status == 'rider') ? "80%" : "10%");
                        ?>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h4 class="fw-bold m-0"><span class="live-indicator"></span>Active Order #<?php echo $live_order->display_id; ?></h4>
                                    <p class="text-muted small m-0">In progress • Updating live</p>
                                </div>
                                <div class="text-end">
                                    <small class="fw-bold text-muted d-block">ESTIMATED DELIVERY</small>
                                    <div class="arrival-timer js-order-timer" data-expiry="<?php echo $expiry; ?>">00:00</div>
                                </div>
                            </div>

                            <div class="tracker-box">
                                <div class="stepper-wrap">
                                    <div class="stepper-bg"></div>
                                    <div class="stepper-fill" style="width: <?php echo $progress; ?>;"></div>

                                    <div class="step-node <?php echo in_array($status, ['pending','cooking','rider']) ? 'active' : ''; ?> <?php echo in_array($status, ['cooking','rider']) ? 'done' : ''; ?>">
                                        <div class="step-icon"><span class="dashicons <?php echo in_array($status, ['cooking','rider']) ? 'dashicons-yes' : 'dashicons-clipboard'; ?>"></span></div>
                                        <div class="step-label">Placed</div>
                                    </div>
                                    <div class="step-node <?php echo ($status == 'cooking') ? 'active' : ''; ?> <?php echo ($status == 'rider') ? 'done' : ''; ?>">
                                        <div class="step-icon"><span class="dashicons <?php echo ($status == 'rider') ? 'dashicons-yes' : 'dashicons-food'; ?>"></span></div>
                                        <div class="step-label">Cooking</div>
                                    </div>
                                    <div class="step-node <?php echo ($status == 'rider') ? 'active' : ''; ?>">
                                        <div class="step-icon"><span class="dashicons dashicons-location-alt"></span></div>
                                        <div class="step-label">On Way</div>
                                    </div>
                                    <div class="step-node">
                                        <div class="step-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                                        <div class="step-label">Arrived</div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/empty-cart.svg" alt="Empty" style="width:120px; opacity:0.2;" class="mb-4">
                                <h3 class="fw-bold">Welcome back, <?php echo $current_user->first_name; ?>!</h3>
                                <p class="text-muted mb-4">You don't have any active orders right now. Ready for lunch?</p>
                                <a href="<?php echo home_url('/menu/'); ?>" class="btn btn-danger px-5 py-3 rounded-pill fw-bold">Order Something New</a>
                            </div>
                        <?php endif; ?>

                        <div class="row mt-5 g-4">
                            <div class="col-md-4"><div class="stat-pill"><h2><?php echo count($all_orders); ?></h2><small class="fw-bold text-muted">TOTAL ORDERS</small></div></div>
                            <div class="col-md-4"><div class="stat-pill"><h2><?php echo $currency.number_format(array_sum(wp_list_pluck($all_orders, 'total_price')), 2); ?></h2><small class="fw-bold text-muted">LIFETIME SPEND</small></div></div>
                            <div class="col-md-4"><div class="stat-pill"><h2><?php echo date('M Y', strtotime($current_user->user_registered)); ?></h2><small class="fw-bold text-muted">MEMBER SINCE</small></div></div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-orders">
                        <h4 class="fw-bold mb-4">Recent Orders</h4>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light"><tr class="small fw-bold"><th>ORDER ID</th><th>ITEMS</th><th>TOTAL</th><th class="text-end">ACTION</th></tr></thead>
                                <tbody>
                                    <?php if($all_orders): foreach($all_orders as $ord): $items = json_decode($ord->items_json, true); ?>
                                    <tr>
                                        <td class="py-4"><strong>#<?php echo $ord->display_id; ?></strong><br><small class="text-muted"><?php echo date('d M, H:i', strtotime($ord->order_date)); ?></small></td>
                                        <td><?php if($items) foreach($items as $i) echo "<div class='small'>{$i['qty']}x {$i['name']}</div>"; ?></td>
                                        <td class="fw-bold"><?php echo $currency.number_format($ord->total_price, 2); ?></td>
                                        <td class="text-end"><a href="?action=reorder&order_id=<?php echo $ord->id; ?>" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold">Re-order</a></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center py-5">No order history found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-profile">
                        <h4 class="fw-bold mb-4">Delivery & Profile Details</h4>
                        <?php if($success_msg): ?>
                            <div id="profileSuccess" class="alert alert-success border-0 rounded-4 p-3 small fw-bold mb-4"><?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <form method="post" id="mainProfileForm" class="row g-4">
                            <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>
                            <div class="col-md-6"><label class="small fw-bold text-muted mb-2">First Name</label><input type="text" name="first_name" class="form-control form-control-lg rounded-4" value="<?php echo $current_user->first_name; ?>" required></div>
                            <div class="col-md-6"><label class="small fw-bold text-muted mb-2">Last Name</label><input type="text" name="last_name" class="form-control form-control-lg rounded-4" value="<?php echo $current_user->last_name; ?>" required></div>
                            <div class="col-12"><label class="small fw-bold text-muted mb-2">Phone Number</label><input type="text" name="phone" class="form-control form-control-lg rounded-4" value="<?php echo $u_phone; ?>" required></div>
                            
                            <div class="col-md-4"><label class="small fw-bold text-muted mb-2">Flat/Suite</label><input type="text" name="flat_no" class="form-control rounded-3" value="<?php echo $u_flat; ?>"></div>
                            <div class="col-md-4"><label class="small fw-bold text-muted mb-2">Building</label><input type="text" name="building" class="form-control rounded-3" value="<?php echo $u_build; ?>"></div>
                            <div class="col-md-4"><label class="small fw-bold text-muted mb-2">Door No.</label><input type="text" name="door_no" class="form-control rounded-3" value="<?php echo $u_door; ?>" required></div>
                            
                            <div class="col-md-8"><label class="small fw-bold text-muted mb-2">Road Name</label><input type="text" name="road_name" class="form-control rounded-3" value="<?php echo $u_road; ?>" required></div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted mb-2">Postcode</label>
                                <input type="text" id="pcInput" name="postcode" class="form-control rounded-3" style="text-transform:uppercase" value="<?php echo $u_pc; ?>" required>
                                <div id="pcAlert" class="text-danger fw-bold mt-2" style="font-size:11px; display:none;"></div>
                            </div>
                            
                            <div class="col-12"><label class="small fw-bold text-muted mb-2">Extra Instructions</label><textarea name="address" class="form-control rounded-4" rows="3"><?php echo $u_addr; ?></textarea></div>
                            <div class="col-12 mt-4 text-end"><button type="submit" name="update_profile" id="btnUpdate" class="btn btn-danger btn-lg px-5 rounded-pill fw-bold">Update Account</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var srvTime = <?php echo current_time('timestamp'); ?>;
    var timeDiff = srvTime - Math.floor(Date.now() / 1000);

    jQuery(document).ready(function($){
        
        // Tab Persistence
        if ($('#profileSuccess').length > 0) {
            $('#btn-profile').tab('show');
        }

        // Live Countdown
        function tick() {
            var now = Math.floor(Date.now() / 1000) + timeDiff;
            $('.js-order-timer').each(function() {
                var exp = parseInt($(this).data('expiry')), rem = exp - now;
                if (rem <= 0) { $(this).text("Ready soon").css('color', '#10b981'); } 
                else { 
                    var m = Math.floor(rem / 60), s = rem % 60; 
                    $(this).text((m < 10 ? "0"+m : m) + ":" + (s < 10 ? "0"+s : s)); 
                }
            });
        }
        setInterval(tick, 1000); tick();

        // Status Auto-Refresh
        if($('.stepper-wrap').length > 0) {
            setTimeout(function(){ location.reload(); }, 30000);
        }

        // Postcode Validation (Allowed: EN3, EN1, EN2, EN8, N9, EN7)
        $('#pcInput').on('input', function() {
            var $el = $(this), $err = $('#pcAlert'), val = $el.val().trim().toUpperCase().replace(/\s+/g, '');
            var allowed = ['EN3', 'EN1', 'EN2', 'EN8', 'N9', 'EN7'];
            
            if (val === "") { $el.css('border-color', ''); $err.hide(); return; }
            
            var match = allowed.some(function(p){ return val.startsWith(p); });
            
            if (!match) {
                $el.css('border-color', '#ef4444'); $err.text('Sorry, we do not deliver to this area.').show(); $('#btnUpdate').prop('disabled', true);
            } else if (val.length !== 6) {
                $el.css('border-color', '#ef4444'); $err.text('Must be exactly 6 characters.').show(); $('#btnUpdate').prop('disabled', true);
            } else {
                $el.css('border-color', '#10b981'); $err.hide(); $('#btnUpdate').prop('disabled', false);
            }
        });
    });
</script>

<?php get_footer(); ?>