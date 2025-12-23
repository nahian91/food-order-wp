<?php
/*
Template Name: Account
*/

get_header();

// 1. SECURITY: Redirect guests to login
if(!is_user_logged_in()){
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$success_msg = '';
$error_msg   = '';
$currency    = '£';

// 2. HANDLE PROFILE UPDATE (Registration Fields Sync)
if(isset($_POST['update_profile'])){
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $full_name  = sanitize_text_field($_POST['full_name']);
    $phone      = sanitize_text_field($_POST['phone']);
    $address    = sanitize_textarea_field($_POST['address']);

    // Update WP User Table
    wp_update_user([
        'ID'           => $user_id,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => $full_name,
    ]);

    // Update Registration Meta Fields
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'address', $address);

    $success_msg = 'Profile details updated successfully!';
    $current_user = wp_get_current_user(); // Refresh local user object
}

// 3. HANDLE PASSWORD UPDATE
if(isset($_POST['update_password'])){
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if(!wp_check_password($current_pass, $current_user->user_pass, $user_id)){
        $error_msg = 'Current password is incorrect.';
    } elseif($new_pass !== $confirm_pass){
        $error_msg = 'New passwords do not match.';
    } elseif(strlen($new_pass) < 6){
        $error_msg = 'Password must be at least 6 characters.';
    } else {
        wp_set_password($new_pass, $user_id);
        $success_msg = 'Password updated successfully!';
        wp_set_auth_cookie($user_id); 
    }
}

// 4. FETCH META FOR DISPLAY
$user_phone   = get_user_meta($user_id, 'phone', true);
$user_address = get_user_meta($user_id, 'address', true);
?>

<style>
    :root { --primary-red: #d63638; }
    
    /* Layout styling */
    .account-nav-column { display: flex; flex-direction: column; gap: 10px; }
    
    /* Button Style Sidebar */
    .account-nav-column .nav-link { 
        background: #fff; 
        color: #333; 
        border: 1px solid #eee; 
        padding: 15px 20px; 
        border-radius: 12px; 
        font-weight: 700; 
        text-align: left; 
        width: 100%; 
        transition: 0.3s;
    }
    .account-nav-column .nav-link i { width: 20px; }
    .account-nav-column .nav-link:hover { background: #fdfdfd; color: var(--primary-red); }
    
    .account-nav-column .nav-link.active { 
        background: var(--primary-red) !important; 
        color: #fff !important; 
        border-color: var(--primary-red); 
        box-shadow: 0 4px 15px rgba(214, 54, 56, 0.2);
    }
    
    /* Main Content Card */
    .account-content-card { 
        border: none; 
        border-radius: 20px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.05); 
        padding: 40px; 
        background: #fff; 
        min-height: 500px; 
    }

    /* Red Action Buttons */
    .btn-red-action { 
        background: var(--primary-red); 
        color: #fff; 
        border-radius: 10px; 
        padding: 12px 35px; 
        border: none; 
        font-weight: 700; 
        transition: 0.3s; 
    }
    .btn-red-action:hover { background: #b52a2c; color: #fff; transform: translateY(-2px); }

    /* Input Styling */
    .form-control-custom { 
        background: #f9f9f9; 
        border: 1px solid #f1f1f1; 
        padding: 12px 15px; 
        border-radius: 10px; 
        font-weight: 500; 
    }
    .form-control-custom:focus { 
        background: #fff; 
        border-color: var(--primary-red); 
        box-shadow: none; 
    }

    /* Order Badges */
    .status-badge { padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-publish { background: #d4edda; color: #155724; }
</style>

<div class="breadcrumb-area text-center text-light" style="background: #1a1a1a; padding: 60px 0;">
    <div class="container">
        <h1 class="text-white m-0">My Account</h1>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="account-nav-column nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="tab-dashboard" data-bs-toggle="pill" data-bs-target="#content-dashboard" type="button" role="tab">
                    <i class="fas fa-home me-2"></i> Dashboard
                </button>
                <button class="nav-link" id="tab-orders" data-bs-toggle="pill" data-bs-target="#content-orders" type="button" role="tab">
                    <i class="fas fa-shopping-bag me-2"></i> My Orders
                </button>
                <button class="nav-link" id="tab-profile" data-bs-toggle="pill" data-bs-target="#content-profile" type="button" role="tab">
                    <i class="fas fa-user-cog me-2"></i> Edit Profile
                </button>
                <button class="nav-link" id="tab-password" data-bs-toggle="pill" data-bs-target="#content-password" type="button" role="tab">
                    <i class="fas fa-shield-alt me-2"></i> Security
                </button>
                <a class="nav-link text-danger mt-3" href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="tab-content account-content-card" id="v-pills-tabContent">

                <div class="tab-pane fade show active" id="content-dashboard" role="tabpanel">
                    <h3 class="fw-bold mb-3">Hello, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>!</h3>
                    <p class="text-muted">Welcome to your dashboard. Here you can easily track your recent food orders and manage your account details.</p>
                    <div class="mt-4 p-4 rounded-3 border-start border-danger border-4 bg-light">
                        <p class="m-0 small"><strong>Need Help?</strong> Contact our support team if you have any issues with your current order.</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="content-orders" role="tabpanel">
                    <h3 class="fw-bold mb-4">Order History</h3>
                    <?php
                    $orders = get_posts([
                        'post_type'      => 'food_order',
                        'meta_key'       => 'customer_id',
                        'meta_value'     => $user_id,
                        'posts_per_page' => -1,
                        'post_status'    => 'any',
                        'orderby'        => 'date',
                        'order'          => 'DESC'
                    ]);

                    if($orders): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr class="small text-muted">
                                        <th>ORDER</th>
                                        <th>DATE</th>
                                        <th>STATUS</th>
                                        <th>TOTAL</th>
                                        <th>ITEMS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $order):
                                        $order_status = get_post_meta($order->ID, 'order_status', true) ?: 'pending';
                                        $total        = get_post_meta($order->ID, 'total_price', true) ?: 0;
                                        $items        = get_post_meta($order->ID, 'order_items', true) ?: [];
                                        // UPDATED: Get the custom Order ID title
                                        $display_id   = get_the_title($order->ID);
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo esc_html($display_id); ?></strong></td>
                                        <td class="small text-muted"><?php echo get_the_date('j M, Y', $order->ID); ?></td>
                                        <td><span class="status-badge status-<?php echo esc_attr($order_status); ?>"><?php echo esc_html($order_status); ?></span></td>
                                        <td class="fw-bold text-danger"><?php echo $currency . number_format($total, 2); ?></td>
                                        <td class="small text-muted">
                                            <?php if($items && is_array($items)): 
                                                foreach($items as $i) echo esc_html($i['qty']) . 'x ' . esc_html($i['name']) . '<br>';
                                            endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">You haven't placed any orders yet.</p>
                            <a href="<?php echo home_url('/menu'); ?>" class="btn-red-action">Browse Menu</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="content-profile" role="tabpanel">
                    <h3 class="fw-bold mb-4">Profile & Delivery Details</h3>
                    <?php if($success_msg && isset($_POST['update_profile'])) echo '<div class="alert alert-success border-0 small">'.$success_msg.'</div>'; ?>
                    
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">First Name</label>
                                <input type="text" name="first_name" class="form-control form-control-custom" value="<?php echo esc_attr($current_user->first_name); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Last Name</label>
                                <input type="text" name="last_name" class="form-control form-control-custom" value="<?php echo esc_attr($current_user->last_name); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Display Name (Publicly visible)</label>
                            <input type="text" name="full_name" class="form-control form-control-custom" value="<?php echo esc_attr($current_user->display_name); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-custom" value="<?php echo esc_attr($user_phone); ?>" placeholder="e.g. +44 1234 567890">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Primary Delivery Address</label>
                            <textarea name="address" rows="3" class="form-control form-control-custom" placeholder="House number, Street, Postcode"><?php echo esc_textarea($user_address); ?></textarea>
                        </div>

                        <button type="submit" name="update_profile" class="btn-red-action">Save Profile</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="content-password" role="tabpanel">
                    <h3 class="fw-bold mb-4">Password & Security</h3>
                    <?php if($success_msg && isset($_POST['update_password'])) echo '<div class="alert alert-success border-0 small">'.$success_msg.'</div>'; ?>
                    <?php if($error_msg) echo '<div class="alert alert-danger border-0 small">'.$error_msg.'</div>'; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-custom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-custom" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-custom" required>
                        </div>
                        <button type="submit" name="update_password" class="btn-red-action">Update Password</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>