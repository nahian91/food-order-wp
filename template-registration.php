<?php
/*
Template Name: Registration
*/

get_header();

// Handle form submission
if(isset($_POST['register_user'])){
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $email      = sanitize_email($_POST['email']);
    $phone      = sanitize_text_field($_POST['phone']);
    $password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    if(empty($first_name)) $errors[] = "First name is required.";
    if(empty($last_name)) $errors[] = "Last name is required.";
    if(empty($email) || !is_email($email)) $errors[] = "Valid email is required.";
    if(empty($password)) $errors[] = "Password is required.";
    if($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if(email_exists($email)) $errors[] = "Email already registered.";

    if(empty($errors)){
        // Create new user
        $user_id = wp_create_user($email, $password, $email);
        if(!is_wp_error($user_id)){
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);

            // Save phone in user meta
            update_user_meta($user_id, 'phone', $phone);

            // Auto login after registration
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to account page
            wp_redirect(home_url('/account/'));
            exit;
        } else {
            $errors[] = $user_id->get_error_message();
        }
    }
}
?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Registration</h1>
                <ul class="breadcrumb">
                    <li><a href="<?php echo home_url(); ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li>Registration</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Registration Form -->
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
                        <h2>Create an account</h2>
                        <p>Enter your details to create a new account</p>

                        <?php if(!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach($errors as $error) echo '<p>'.esc_html($error).'</p>'; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <input class="form-control" name="first_name" placeholder="First Name" type="text" value="<?php echo isset($first_name)?esc_attr($first_name):''; ?>">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <input class="form-control" name="last_name" placeholder="Last Name" type="text" value="<?php echo isset($last_name)?esc_attr($last_name):''; ?>">
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <input class="form-control" name="email" placeholder="Email*" type="email" value="<?php echo isset($email)?esc_attr($email):''; ?>">
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <input class="form-control" name="phone" placeholder="Telephone" type="text" value="<?php echo isset($phone)?esc_attr($phone):''; ?>">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <input class="form-control" name="password" placeholder="Password*" type="password">
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <input class="form-control" name="confirm_password" placeholder="Confirm Password*" type="password">
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <button type="submit" name="register_user" class="btn btn-primary w-100">Register</button>
                                </div>
                            </div>
                        </form>

                        <div class="login-alternative">
                            <p>
                                Already have an account? <a href="<?php echo home_url('/login/'); ?>">Login Now</a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
