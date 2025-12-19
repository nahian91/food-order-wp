<?php
/*
Template Name: Login
*/

get_header();

// 1. If already logged in, send to account
if (is_user_logged_in()) {
    wp_redirect(home_url('/account/'));
    exit;
}

// 2. Handle login submission
$login_error = '';
if(isset($_POST['fd_login_submit'])){
    
    // Capture the redirect URL if they came from the Cart/Checkout
    $redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/account/');

    $creds = [
        'user_login'    => sanitize_text_field($_POST['fd_email']),
        'user_password' => $_POST['fd_password'], 
        'remember'      => isset($_POST['fd_remember']) ? true : false,
    ];

    $user = wp_signon($creds, false);

    if(is_wp_error($user)){
        $login_error = "Invalid email or password. Please try again.";
    } else {
        wp_safe_redirect($redirect_to);
        exit;
    }
}
?>

<style>
    .auth-wrapper { background: #f4f7f6; padding: 60px 0; min-height: 80vh; }
    .auth-card { background: #fff; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; }
    .auth-info-side { background: #d63638; color: #fff; padding: 40px; display: flex; flex-direction: column; justify-content: center; }
    .auth-form-side { padding: 50px; }
    
    .form-label { font-weight: 600; color: #444; font-size: 14px; margin-bottom: 8px; display: block; }
    .form-control { height: 50px; border-radius: 10px; border: 1px solid #ddd; padding: 10px 15px; width: 100%; transition: 0.3s; }
    .form-control:focus { border-color: #d63638; box-shadow: 0 0 0 3px rgba(214, 54, 56, 0.1); outline: none; }
    
    .auth-btn { background: #d63638; color: #fff; border: none; width: 100%; padding: 15px; border-radius: 10px; font-weight: 700; font-size: 17px; margin-top: 10px; transition: 0.3s; cursor: pointer; }
    .auth-btn:hover { background: #b52a2c; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(214, 54, 56, 0.3); }
    
    .alert-custom { border-left: 4px solid #d63638; background: #fff5f5; color: #d63638; padding: 15px; border-radius: 8px; font-size: 14px; }
</style>

<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="auth-card">
                    <div class="row g-0">
                        
                        <div class="col-md-5 auth-info-side d-none d-md-flex">
                            <div class="text-center w-100">
                                <i class="fas fa-utensils mb-4" style="font-size: 60px; opacity: 0.8;"></i>
                            </div>
                            <h3>Welcome Back!</h3>
                            <p>Log in to access your saved addresses, track live orders, and enjoy your favorite meals.</p>
                            <ul class="mt-4 list-unstyled">
                                <li class="mb-2"><i class="fas fa-history me-2"></i> View Order History</li>
                                <li class="mb-2"><i class="fas fa-heart me-2"></i> Fast Checkout</li>
                                <li class="mb-2"><i class="fas fa-percentage me-2"></i> Member-only Deals</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-7 auth-form-side">
                            <h2 class="mb-2" style="font-weight: 800; color: #333;">Login</h2>
                            <p class="text-muted mb-4">Good to see you again! Please enter your details.</p>
                            
                            <?php if ($login_error): ?>
                                <div class="alert-custom mb-4">
                                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo esc_html($login_error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Username or Email</label>
                                    <input type="text" name="fd_email" class="form-control" placeholder="Enter your email" required>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <label class="form-label">Password</label>
                                        <a href="<?php echo wp_lostpassword_url(); ?>" style="font-size: 13px; color: #d63638; text-decoration: none;">Forgot?</a>
                                    </div>
                                    <input type="password" name="fd_password" class="form-control" placeholder="••••••••" required>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fd_remember" id="fd_remember">
                                        <label class="form-check-label text-muted" for="fd_remember" style="font-size: 14px; cursor: pointer;">
                                            Keep me logged in
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" name="fd_login_submit" class="auth-btn">Sign In</button>
                            </form>

                            <div class="text-center mt-5">
                                <p class="text-muted mb-0">Don't have an account yet?</p> 
                                <a href="<?php echo home_url('/registration'); ?>" style="color: #d63638; font-weight: 700; text-decoration: none; font-size: 16px;">Create Account Now</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>