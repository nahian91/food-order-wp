<?php
/*
Template Name: Account
*/

get_header();

// 1. SECURITY & INITIALIZATION
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

global $wpdb;
$table_name   = $wpdb->prefix . 'afd_food_orders';
$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$success_msg  = '';
$error_msg    = '';
$currency     = '£';
$prep_time    = intval(get_option('afd_cooking_time', 20));

/**
 * 2. RE-ORDER LOGIC
 */
if (isset($_GET['action']) && $_GET['action'] === 'reorder' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $db_order = $wpdb->get_row($wpdb->prepare("SELECT items_json FROM $table_name WHERE id = %d AND customer_id = %d", $order_id, $user_id));
    if ($db_order) {
        ?>
        <script>
            localStorage.setItem('fd_cart_save', <?php echo json_encode($db_order->items_json); ?>);
            window.location.href = "<?php echo home_url('/checkout/'); ?>";
        </script>
        <?php
        exit;
    }
}

// 3. PROFILE UPDATES
if (isset($_POST['update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'update_user_profile')) {
    wp_update_user(['ID' => $user_id, 'first_name' => sanitize_text_field($_POST['first_name']), 'last_name' => sanitize_text_field($_POST['last_name']), 'display_name' => sanitize_text_field($_POST['full_name'])]);
    update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    update_user_meta($user_id, 'address', sanitize_textarea_field($_POST['address']));
    $success_msg = 'Profile updated successfully!';
}

// 4. DATA FETCHING
$all_orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE customer_id = %d ORDER BY order_date DESC", $user_id));

// FIND ACTIVE ORDER (Only show if NOT completed/delivered)
$live_order = null;
if($all_orders){
    foreach($all_orders as $order){
        $status = strtolower($order->order_status);
        if(in_array($status, ['pending', 'cooking', 'rider'])){
            $live_order = $order;
            break;
        }
    }
}

$user_phone   = get_user_meta($user_id, 'phone', true);
$user_address = get_user_meta($user_id, 'address', true);
?>

<style>
    :root { --primary-red: #d63638; --dark-bg: #0f172a; --light-gray: #f8fafc; --success-green: #22c55e; }
    body { background-color: #f1f5f9; }
    .account-wrapper { padding: 50px 0; min-height: 100vh; }
    
    /* Sidebar Navigation */
    .account-nav-column .nav-link { background: #fff; color: #64748b; border: none; padding: 16px 20px; border-radius: 16px; font-weight: 600; margin-bottom: 12px; transition: 0.3s; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); text-align: left; width: 100%; }
    .account-nav-column .nav-link.active { background: var(--primary-red) !important; color: #fff !important; box-shadow: 0 10px 15px -3px rgba(214, 54, 56, 0.3); }
    
    /* Main Content Card */
    .account-content-card { border: none; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); padding: 40px; background: #fff; }

    /* MODERN TRACKER UI */
    .live-tracker-card { background: linear-gradient(145deg, #ffffff, #fdfdfd); border: 1px solid #e2e8f0; border-radius: 30px; padding: 35px; position: relative; overflow: hidden; margin-bottom: 40px; }
    .live-tracker-card::before { content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: var(--primary-red); }
    
    .tracker-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
    .live-dot { width: 10px; height: 10px; background: var(--success-green); border-radius: 50%; display: inline-block; margin-right: 8px; animation: blink 1.5s infinite; }
    
    .timer-pill { background: #1e293b; color: #fff; padding: 10px 20px; border-radius: 20px; font-family: 'Courier New', monospace; font-size: 22px; font-weight: 700; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

    /* Stepper UX */
    .stepper-container { position: relative; display: flex; justify-content: space-between; align-items: flex-start; margin-top: 20px; }
    .stepper-line { position: absolute; top: 25px; left: 5%; width: 90%; height: 4px; background: #f1f5f9; z-index: 1; border-radius: 10px; }
    .stepper-progress { position: absolute; top: 25px; left: 5%; height: 4px; background: var(--primary-red); z-index: 2; transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 10px; }
    
    .step-item { position: relative; z-index: 3; text-align: center; flex: 1; }
    .step-circle { width: 54px; height: 54px; background: #fff; border: 3px solid #f1f5f9; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; transition: 0.4s; color: #cbd5e1; }
    .step-label { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.3s; }
    
    .step-item.active .step-circle { border-color: var(--primary-red); color: var(--primary-red); background: #fff1f2; transform: scale(1.1); box-shadow: 0 10px 15px -3px rgba(214, 54, 56, 0.2); }
    .step-item.active .step-label { color: #1e293b; }
    .step-item.completed .step-circle { background: var(--success-green); border-color: var(--success-green); color: #fff; }

    /* Welcome Banner */
    .welcome-banner { background: linear-gradient(90deg, #fff5f5 0%, #ffffff 100%); border-radius: 20px; padding: 35px; border-left: 5px solid var(--primary-red); }

    @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stepper-line, .stepper-progress { display: none; }
        .stepper-container { flex-direction: column; gap: 20px; }
        .step-item { display: flex; align-items: center; gap: 15px; text-align: left; width: 100%; }
        .step-circle { margin: 0; }
    }
</style>

<div class="breadcrumb-area text-center text-light" style="background: var(--dark-bg); padding: 80px 0;">
    <div class="container"><h1 class="text-white m-0 fw-bold">My Account</h1></div>
</div>

<div class="account-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="account-nav-column nav flex-column nav-pills" role="tablist">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-dash"><span class="dashicons dashicons-dashboard"></span> Dashboard</button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-orders"><span class="dashicons dashicons-cart"></span> Order History</button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-profile"><span class="dashicons dashicons-admin-users"></span> My Profile</button>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-link text-danger"><span class="dashicons dashicons-exit"></span> Logout</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="tab-content account-content-card">
                    
                    <div class="tab-pane fade show active" id="tab-dash">
                        <?php if ($live_order) : 
                            $st = strtolower($live_order->order_status);
                            $expiry = strtotime($live_order->order_date) + ($prep_time * 60);
                            
                            // Calculate Progress
                            $prog = "5%"; 
                            if($st == 'cooking') $prog = "40%";
                            if($st == 'rider') $prog = "75%";
                        ?>
                            <div class="tracker-top">
                                <div>
                                    <h4 class="fw-bold m-0"><span class="live-dot"></span>Order #<?php echo $live_order->display_id; ?></h4>
                                    <p class="text-muted small mt-1">Status: <span class="text-capitalize fw-bold text-dark"><?php echo $st; ?></span></p>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted fw-bold d-block mb-1">REMAINING</small>
                                    <div class="timer-pill live-js-timer" data-expiry="<?php echo $expiry; ?>">00:00</div>
                                </div>
                            </div>

                            <div class="live-tracker-card">
                                <div class="stepper-container">
                                    <div class="stepper-line"></div>
                                    <div class="stepper-progress" style="width: <?php echo $prog; ?>;"></div>

                                    <div class="step-item <?php echo in_array($st, ['pending','cooking','rider']) ? 'active' : ''; ?> <?php echo in_array($st, ['cooking','rider']) ? 'completed' : ''; ?>">
                                        <div class="step-circle"><span class="dashicons dashicons-clipboard"></span></div>
                                        <div class="step-label">Placed</div>
                                    </div>

                                    <div class="step-item <?php echo ($st == 'cooking') ? 'active' : ''; ?> <?php echo ($st == 'rider') ? 'completed' : ''; ?>">
                                        <div class="step-circle"><span class="dashicons dashicons-food"></span></div>
                                        <div class="step-label">Cooking</div>
                                    </div>

                                    <div class="step-item <?php echo ($st == 'rider') ? 'active' : ''; ?>">
                                        <div class="step-circle"><span class="dashicons dashicons-location-alt"></span></div>
                                        <div class="step-label">On Way</div>
                                    </div>

                                    <div class="step-item">
                                        <div class="step-circle"><span class="dashicons dashicons-yes-alt"></span></div>
                                        <div class="step-label">Delivered</div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="welcome-banner">
                                <h3 class="fw-bold m-0">Welcome back, <?php echo esc_html($current_user->first_name); ?>!</h3>
                                <p class="text-muted mt-2">Ready for your next meal? Your favorites are just a click away.</p>
                                <a href="<?php echo home_url('/menu/'); ?>" class="btn btn-danger mt-3 px-4 py-2 rounded-pill fw-bold">Browse Menu</a>
                            </div>
                        <?php endif; ?>

                        <hr class="my-5 opacity-25">
                        <h5 class="fw-bold mb-4">Account Overview</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="p-4 border rounded-4 text-center">
                                    <h2 class="fw-bold text-danger"><?php echo count($all_orders); ?></h2>
                                    <small class="text-muted fw-bold">TOTAL ORDERS</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <button onclick="document.querySelector('[data-bs-target=\'#tab-orders\']').click()" class="btn btn-light w-100 h-100 py-3 rounded-4 border text-start px-3 d-flex align-items-center">
                                    <span class="dashicons dashicons-list-view text-danger me-2"></span> <b>Order History</b>
                                </button>
                            </div>
                            <div class="col-md-4 mb-3">
                                <button onclick="document.querySelector('[data-bs-target=\'#tab-profile\']').click()" class="btn btn-light w-100 h-100 py-3 rounded-4 border text-start px-3 d-flex align-items-center">
                                    <span class="dashicons dashicons-admin-users text-danger me-2"></span> <b>Edit Profile</b>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-orders">
                        <h4 class="fw-bold mb-4">Past Orders</h4>
                        <div class="table-responsive">
                            <table class="table align-middle border-0">
                                <thead class="table-light">
                                    <tr class="small text-muted">
                                        <th class="border-0">ORDER</th>
                                        <th class="border-0">DETAILS</th>
                                        <th class="border-0 text-center">TOTAL</th>
                                        <th class="border-0 text-end">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($all_orders): foreach($all_orders as $order): 
                                        $items = json_decode($order->items_json, true);
                                    ?>
                                    <tr>
                                        <td class="py-4">
                                            <span class="fw-bold">#<?php echo $order->display_id; ?></span><br>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($order->order_date)); ?></small>
                                        </td>
                                        <td>
                                            <?php if(is_array($items)) : foreach($items as $i): ?>
                                                <div class="small"><span class="text-danger fw-bold"><?php echo $i['qty']; ?>x</span> <?php echo esc_html($i['name']); ?></div>
                                            <?php endforeach; endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?php echo $currency.number_format($order->total_price, 2); ?></td>
                                        <td class="text-end">
                                            <a href="?action=reorder&order_id=<?php echo $order->id; ?>" class="btn btn-sm btn-dark rounded-pill px-3">Re-order</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="4" class="text-center py-5">No orders found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-profile">
                        <h4 class="fw-bold mb-4">Account Details</h4>
                        <form method="post" class="row g-3">
                            <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>
                            <div class="col-md-6">
                                <label class="small fw-bold mb-1">First Name</label>
                                <input type="text" name="first_name" class="form-control rounded-3 py-2" value="<?php echo esc_attr($current_user->first_name); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold mb-1">Last Name</label>
                                <input type="text" name="last_name" class="form-control rounded-3 py-2" value="<?php echo esc_attr($current_user->last_name); ?>">
                            </div>
                            <div class="col-12">
                                <label class="small fw-bold mb-1">Phone Number</label>
                                <input type="text" name="phone" class="form-control rounded-3 py-2" value="<?php echo esc_attr($user_phone); ?>">
                            </div>
                            <div class="col-12">
                                <label class="small fw-bold mb-1">Delivery Address</label>
                                <textarea name="address" class="form-control rounded-3" rows="3"><?php echo esc_textarea($user_address); ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="update_profile" class="btn btn-danger px-5 rounded-pill py-2 fw-bold">Update Profile</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var server_now = <?php echo current_time('timestamp'); ?>;
    var browser_now = Math.floor(Date.now() / 1000);
    var offset = server_now - browser_now;

    jQuery(document).ready(function($){
        function updateTimer() {
            var now = Math.floor(Date.now() / 1000) + offset;
            $('.live-js-timer').each(function() {
                var expiry = parseInt($(this).data('expiry'));
                var diff = expiry - now;
                if (diff <= 0) { 
                    $(this).text("Ready soon").css('color', '#22c55e'); 
                } else {
                    var m = Math.floor(diff / 60), s = diff % 60;
                    $(this).text((m < 10 ? "0"+m : m) + ":" + (s < 10 ? "0"+s : s));
                }
            });
        }
        setInterval(updateTimer, 1000);
        updateTimer();
        
        // Refresh every 30s only if tracking an order
        if($('.stepper-container').length > 0) {
            setTimeout(function(){ location.reload(); }, 30000);
        }
    });
</script>

<?php get_footer(); ?>