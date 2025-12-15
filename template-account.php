<?php
/*
Template Name: Account
*/

get_header();

// Redirect if not logged in
if(!is_user_logged_in()){
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
$success_msg = '';
$error_msg   = '';

// Handle profile update
if(isset($_POST['update_profile'])){
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $full_name  = sanitize_text_field($_POST['full_name']);

    wp_update_user([
        'ID'           => $current_user->ID,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => $full_name,
    ]);
    $success_msg = 'Profile updated successfully!';
    $current_user = wp_get_current_user(); // refresh user object
}

// Handle password update
if(isset($_POST['update_password'])){
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if(!wp_check_password($current_pass, $current_user->user_pass, $current_user->ID)){
        $error_msg = 'Current password is incorrect.';
    } elseif($new_pass !== $confirm_pass){
        $error_msg = 'New password and confirm password do not match.';
    } elseif(empty($new_pass)){
        $error_msg = 'New password cannot be empty.';
    } else {
        wp_set_password($new_pass, $current_user->ID);
        $success_msg = 'Password updated successfully!';
        wp_set_auth_cookie($current_user->ID); // keep user logged in
    }
}
?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>My Account</h1>
                <ul class="breadcrumb">
                    <li><a href="<?php echo home_url(); ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li>My Account</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Tabs Sidebar -->
        <div class="col-md-3 mb-3">
            <ul class="nav flex-column nav-pills" id="accountTabs" role="tablist" aria-orientation="vertical">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="pill" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">Dashboard</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">Orders</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Change Password</button>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="<?php echo wp_logout_url(home_url()); ?>">Logout</a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="col-md-9">
            <div class="tab-content" id="accountTabsContent">

                <!-- Dashboard -->
                <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                    <h3>Dashboard</h3>
                    <p>Welcome, <strong><?php echo esc_html($current_user->display_name); ?></strong>. Manage your profile, orders, and password here.</p>
                </div>

                <!-- Profile -->
                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <h3>Profile</h3>

                    <?php if($success_msg) echo '<div class="alert alert-success">'.$success_msg.'</div>'; ?>
                    <?php if($error_msg) echo '<div class="alert alert-danger">'.$error_msg.'</div>'; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" id="firstName" value="<?php echo esc_attr($current_user->first_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" id="lastName" value="<?php echo esc_attr($current_user->last_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" id="fullName" value="<?php echo esc_attr($current_user->display_name); ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Orders -->
                <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                    <h3>Orders</h3>
                    <?php
                    $orders = get_posts([
                        'post_type' => 'food_order',
                        'meta_key' => 'customer_id',
                        'meta_value' => $current_user->ID,
                        'posts_per_page' => -1,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ]);

                    if($orders):
                    ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orders as $order):
                                    $status = get_post_meta($order->ID,'status',true) ?: 'Pending';
                                    $total = get_post_meta($order->ID,'total_price',true) ?: 0;
                                    $items = get_post_meta($order->ID,'items',true) ?: [];
                                ?>
                                <tr>
                                    <td>#<?php echo $order->ID; ?></td>
                                    <td><?php echo get_the_date('d M, Y', $order->ID); ?></td>
                                    <td><?php echo esc_html(ucfirst($status)); ?></td>
                                    <td>$<?php echo number_format($total,2); ?></td>
                                    <td>
                                        <?php
                                        if($items && is_array($items)){
                                            foreach($items as $i){
                                                echo esc_html($i['name']) . ' x ' . esc_html($i['qty']) . '<br>';
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>You have not placed any orders yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <h3>Change Password</h3>

                    <?php if($success_msg) echo '<div class="alert alert-success">'.$success_msg.'</div>'; ?>
                    <?php if($error_msg) echo '<div class="alert alert-danger">'.$error_msg.'</div>'; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" id="currentPassword" placeholder="Current Password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" id="newPassword" placeholder="New Password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" id="confirmPassword" placeholder="Confirm New Password" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-warning">Update Password</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
