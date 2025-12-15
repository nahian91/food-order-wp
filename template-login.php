<?php
/*
Template Name: Login
*/

get_header();

// Handle login submission
$login_error = '';
if(isset($_POST['fd_login_submit'])){
    $creds = [
        'user_login'    => sanitize_text_field($_POST['fd_email']),
        'user_password' => sanitize_text_field($_POST['fd_password']),
        'remember'      => isset($_POST['fd_remember']) ? true : false,
    ];

    $user = wp_signon($creds, false);

    if(is_wp_error($user)){
        $login_error = $user->get_error_message();
    } else {
        // Redirect to /account/ page
        wp_safe_redirect(home_url('/account/'));
        exit;
    }
}
?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Login</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Login</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Start Login -->
<div class="login-area">
    <div class="container">
        <div class="login-items">
            <div class="row">
                <div class="col-lg-6">
                    <div class="login-thumb">
                        <img src="<?php echo get_template_directory_uri();?>/assets/img/banner/7.jpg" alt="Image Not Found">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-forms">
                        <h2>Welcome back</h2>
                        <p>Enter your email and password to continue</p>

                        <?php if($login_error): ?>
                            <div class="alert alert-danger"><?php echo esc_html($login_error); ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" placeholder="Email*" type="text" name="fd_email" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" placeholder="Password*" type="password" name="fd_password" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fd_remember" id="fd_remember">
                                        <label class="form-check-label" for="fd_remember">Remember me</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" name="fd_login_submit" class="btn btn-primary w-100">Login</button>
                                </div>
                            </div>
                        </form>

                        <div class="login-alternative mt-3">
                            <p>Don't have any account? <a href="<?php echo wp_registration_url(); ?>">Register Now</a></p>
                        </div>
                        <div class="login-alternative mt-2">
                            <p><a href="<?php echo wp_lostpassword_url(); ?>">Forgot your password?</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Login -->

<?php get_footer(); ?>
