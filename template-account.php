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

/**
 * 2. FEATURE: UNIFIED RE-ORDER LOGIC
 * Sets localStorage and redirects immediately to Checkout
 */
if (isset($_GET['action']) && $_GET['action'] === 'reorder' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $items_json = '';

    // Check custom table first
    $db_order = $wpdb->get_row($wpdb->prepare("SELECT items_json FROM $table_name WHERE id = %d AND customer_id = %d", $order_id, $user_id));
    
    if ($db_order) {
        $items_json = $db_order->items_json;
    } else {
        // Fallback: Check old CPT meta
        $owner = get_post_meta($order_id, 'customer_id', true);
        if (intval($owner) === $user_id) {
            $old_items = get_post_meta($order_id, 'order_items', true) ?: get_post_meta($order_id, 'items', true);
            $items_json = json_encode($old_items);
        }
    }

    if (!empty($items_json)) {
        ?>
        <!DOCTYPE html>
        <html>
        <body style="background:#f8f9fa;">
            <script>
                localStorage.setItem('fd_cart_save', <?php echo json_encode($items_json); ?>);
                window.location.href = "<?php echo home_url('/checkout/'); ?>";
            </script>
            <div style="text-align:center; margin-top:100px; font-family:sans-serif;">
                <h2 style="color:#d63638;">Processing your order...</h2>
                <p>Redirecting you to checkout.</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// 3. PROFILE & PASSWORD UPDATES
if (isset($_POST['update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'update_user_profile')) {
    wp_update_user([
        'ID' => $user_id, 
        'first_name' => sanitize_text_field($_POST['first_name']), 
        'last_name' => sanitize_text_field($_POST['last_name']), 
        'display_name' => sanitize_text_field($_POST['full_name'])
    ]);
    update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    update_user_meta($user_id, 'address', sanitize_textarea_field($_POST['address']));
    $success_msg = 'Profile updated successfully!';
    $current_user = wp_get_current_user();
}

if (isset($_POST['update_password']) && wp_verify_nonce($_POST['pass_nonce'], 'update_user_password')) {
    if (!wp_check_password($_POST['current_password'], $current_user->user_pass, $user_id)) {
        $error_msg = 'Current password incorrect.';
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $error_msg = 'Passwords do not match.';
    } else {
        wp_set_password($_POST['new_password'], $user_id);
        $success_msg = 'Password updated!';
        wp_set_auth_cookie($user_id);
    }
}

$user_phone   = get_user_meta($user_id, 'phone', true);
$user_address = get_user_meta($user_id, 'address', true);
?>

<style>
    :root { --primary-red: #d63638; --dark-bg: #111; --light-bg: #f8f9fa; }
    .account-wrapper { background: var(--light-bg); padding-bottom: 80px; min-height: 100vh; }
    .account-nav-column .nav-link { background: #fff; color: #333; border: 1px solid #eee; padding: 15px 20px; border-radius: 12px; font-weight: 700; margin-bottom: 10px; transition: 0.3s; width: 100%; text-align: left; }
    .account-nav-column .nav-link.active { background: var(--primary-red) !important; color: #fff !important; border-color: var(--primary-red); }
    .account-content-card { border: none; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); padding: 40px; background: #fff; }
    .order-item-list { list-style: none; padding: 0; margin: 0; }
    .item-qty { color: var(--primary-red); font-weight: 800; margin-right: 8px; }
    .btn-reorder { padding: 8px 15px; font-size: 12px; border-radius: 8px; border: 1px solid #ddd; text-decoration: none; color: #333; font-weight: 700; display: inline-block; }
    .btn-reorder:hover { background: var(--primary-red); color: #fff; border-color: var(--primary-red); }
    .form-control-custom { background: #f9f9f9; border: 1px solid #eee; padding: 12px; border-radius: 10px; width: 100%; }
    .table thead th { border-top: none; text-transform: uppercase; font-size: 12px; color: #888; }
</style>

<div class="breadcrumb-area text-center text-light" style="background: var(--dark-bg); padding: 60px 0;">
    <div class="container"><h1 class="text-white m-0">My Account</h1></div>
</div>

<div class="account-wrapper pt-5">
    <div class="container">
        <?php if($success_msg) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
        <?php if($error_msg) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

        <div class="row">
            <div class="col-lg-3">
                <div class="account-nav-column nav flex-column nav-pills" role="tablist">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-dash">Dashboard</button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-orders">Order History</button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-profile">Profile</button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-pass">Security</button>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-link text-danger">Logout</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="tab-content account-content-card">
                    
                    <div class="tab-pane fade show active" id="tab-dash">
                        <h3>Hello, <?php echo esc_html($current_user->display_name); ?></h3>
                        <p class="text-muted">From your account dashboard you can view your recent orders and edit your password and account details.</p>
                        <hr>
                        <div class="mt-4">
                            <button class="btn btn-outline-dark" onclick="document.querySelector('[data-bs-target=\'#tab-orders\']').click()">View Recent Orders</button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-orders">
                        <h4 class="fw-bold mb-4">All Orders</h4>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Items</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $all_orders = $wpdb->get_results($wpdb->prepare(
                                        "SELECT * FROM $table_name WHERE customer_id = %d ORDER BY order_date DESC",
                                        $user_id
                                    ));

                                    if($all_orders): foreach($all_orders as $order): 
                                        $items = json_decode($order->items_json, true);
                                        // UPDATED: Use the stored display_id column
                                        $display_id = !empty($order->display_id) ? $order->display_id : 'REC-' . $order->id;
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo esc_html($display_id); ?></strong></td>
                                        <td>
                                            <ul class="order-item-list small">
                                                <?php if(is_array($items)) : foreach($items as $i): ?>
                                                    <li><span class="item-qty"><?php echo $i['qty']; ?>x</span> <?php echo esc_html($i['name']); ?></li>
                                                <?php endforeach; endif; ?>
                                            </ul>
                                        </td>
                                        <td class="small text-nowrap"><?php echo date('M j, Y', strtotime($order->order_date)); ?></td>
                                        <td class="fw-bold text-danger"><?php echo $currency.number_format($order->total_price, 2); ?></td>
                                        <td><a href="?action=reorder&order_id=<?php echo $order->id; ?>" class="btn-reorder">Re-order</a></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="5" class="text-center py-4">No orders found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-profile">
                        <h4 class="fw-bold mb-4">Account Details</h4>
                        <form method="post">
                            <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>First Name</label><input type="text" name="first_name" class="form-control-custom" value="<?php echo esc_attr($current_user->first_name); ?>"></div>
                                <div class="col-md-6 mb-3"><label>Last Name</label><input type="text" name="last_name" class="form-control-custom" value="<?php echo esc_attr($current_user->last_name); ?>"></div>
                            </div>
                            <div class="mb-3"><label>Display Name</label><input type="text" name="full_name" class="form-control-custom" value="<?php echo esc_attr($current_user->display_name); ?>"></div>
                            <div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control-custom" value="<?php echo esc_attr($user_phone); ?>"></div>
                            <div class="mb-3"><label>Address</label><textarea name="address" class="form-control-custom" rows="3"><?php echo esc_textarea($user_address); ?></textarea></div>
                            <button type="submit" name="update_profile" class="btn btn-danger px-5">Save Changes</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tab-pass">
                        <h4 class="fw-bold mb-4">Update Password</h4>
                        <form method="post">
                            <?php wp_nonce_field('update_user_password', 'pass_nonce'); ?>
                            <div class="mb-3"><label>Current Password</label><input type="password" name="current_password" class="form-control-custom"></div>
                            <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control-custom"></div>
                            <div class="mb-3"><label>Confirm New Password</label><input type="password" name="confirm_password" class="form-control-custom"></div>
                            <button type="submit" name="update_password" class="btn btn-danger px-5">Update Password</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>