<?php
/*
Template Name: Advanced Registration
*/

if (is_user_logged_in()) {
    wp_redirect(home_url('/checkout'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fd_register_nonce'])) {
    if (wp_verify_nonce($_POST['fd_register_nonce'], 'fd_user_register')) {
        
        $username   = sanitize_user($_POST['username']);
        $email      = sanitize_email($_POST['email']);
        $password   = $_POST['password'];
        $full_name  = sanitize_text_field($_POST['full_name']);
        $phone      = sanitize_text_field($_POST['phone']);
        $address    = sanitize_textarea_field($_POST['address']);

        // Validation
        if (username_exists($username)) $errors[] = "Username already taken.";
        if (!is_email($email)) $errors[] = "Please enter a valid email.";
        if (email_exists($email)) $errors[] = "This email is already registered.";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        if (empty($phone)) $errors[] = "Phone number is required for delivery.";

        if (empty($errors)) {
            $user_id = wp_create_user($username, $password, $email);
            
            if (!is_wp_error($user_id)) {
                // Save Advanced Fields
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $full_name,
                    'first_name'   => $full_name
                ]);
                
                // Store Phone and Address in User Meta (Usable in Checkout)
                update_user_meta($user_id, 'billing_phone', $phone);
                update_user_meta($user_id, 'billing_address_1', $address);
                update_user_meta($user_id, 'fd_user_phone', $phone);
                update_user_meta($user_id, 'fd_user_address', $address);
                
                // Auto-login
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                
                wp_redirect(home_url('/checkout'));
                exit;
            } else {
                $errors[] = $user_id->get_error_message();
            }
        }
    }
}

get_header(); ?>

<style>
    .reg-wrapper { background: #f4f7f6; padding: 60px 0; min-height: 80vh; }
    .reg-card { background: #fff; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; }
    .reg-info-side { background: #d63638; color: #fff; padding: 40px; display: flex; flex-direction: column; justify-content: center; }
    .reg-form-side { padding: 40px; }
    .form-label { font-weight: 600; color: #444; font-size: 14px; margin-bottom: 8px; }
    .form-control { height: 48px; border-radius: 10px; border: 1px solid #ddd; padding: 10px 15px; width: 100%; transition: 0.3s; }
    .form-control:focus { border-color: #d63638; box-shadow: 0 0 0 3px rgba(214, 54, 56, 0.1); outline: none; }
    .reg-btn { background: #d63638; color: #fff; border: none; width: 100%; padding: 15px; border-radius: 10px; font-weight: 700; font-size: 16px; margin-top: 20px; transition: 0.3s; cursor: pointer; }
    .reg-btn:hover { background: #b52a2c; transform: translateY(-2px); }
    textarea.form-control { height: auto; }
</style>

<div class="reg-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="reg-card">
                    <div class="row g-0">
                        <div class="col-md-4 reg-info-side d-none d-md-flex">
                            <h3>Welcome!</h3>
                            <p>Create an account to track your delicious orders and get faster delivery.</p>
                            <ul class="mt-4 list-unstyled">
                                <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Save Multiple Addresses</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2"></i> One-click Ordering</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Exclusive Discounts</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-8 reg-form-side">
                            <h2 class="mb-4" style="font-weight: 800;">Register</h2>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger mb-4" style="border-left: 4px solid #d63638; background: #fff5f5;">
                                    <?php foreach ($errors as $error) echo '<p class="m-0 mb-1" style="color:#d63638; font-size:13px;">• '.$error.'</p>'; ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="registrationForm">
                                <?php wp_nonce_field('fd_user_register', 'fd_register_nonce'); ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" placeholder="+1 234 567 890" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" placeholder="johndoe123" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Delivery Address</label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="House No, Street Name, City..." required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Choose Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                </div>

                                <button type="submit" class="reg-btn">Create My Account</button>
                            </form>

                            <div class="text-center mt-4">
                                <span class="text-muted">Already have an account?</span> 
                                <a href="<?php echo home_url('/login'); ?>" style="color: #d63638; font-weight: 700; text-decoration: none;">Login here</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>