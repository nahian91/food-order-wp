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
        
        // UK Phone Validation Logic
        $phone_raw  = sanitize_text_field($_POST['phone']);
        $phone      = preg_replace('/[^0-9]/', '', $phone_raw); 
        
        // New Address Fields
        $flat_no    = sanitize_text_field($_POST['flat_no']);
        $building   = sanitize_text_field($_POST['building']);
        $door_no    = sanitize_text_field($_POST['door_no']);
        $road_name  = sanitize_text_field($_POST['road_name']);
        $address_gen = sanitize_textarea_field($_POST['address']);
        
        // Postcode Logic - Normalize (Upper case, no spaces)
        $postcode_raw = sanitize_text_field($_POST['postcode']);
        $postcode     = strtoupper(str_replace(' ', '', $postcode_raw));
        $allowed_zones = ['EN3', 'EN1', 'EN2', 'EN8', 'N9', 'EN7'];

        // Validation
        if (username_exists($username)) $errors[] = "Username already taken.";
        if (!is_email($email)) $errors[] = "Please enter a valid email.";
        if (email_exists($email)) $errors[] = "This email is already registered.";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        
        // Phone Validation
        if (empty($phone)) {
            $errors[] = "Phone number is required.";
        } elseif (strlen($phone) !== 11) {
            $errors[] = "UK Phone number must be exactly 11 digits.";
        } elseif (substr($phone, 0, 1) !== '0') {
            $errors[] = "UK Phone number must start with 0.";
        }

        // Postcode Validation Logic
        $is_allowed = false;
        foreach ($allowed_zones as $zone) {
            if (strpos($postcode, $zone) === 0) {
                $is_allowed = true;
                break;
            }
        }

        if (empty($postcode)) {
            $errors[] = "Postcode is required.";
        } elseif (!$is_allowed) {
            $errors[] = "Sorry, we do not deliver to this postcode area.";
        } elseif (strlen($postcode) > 6) {
            $errors[] = "Postcode cannot be more than 6 characters. (e.g., EN31AA).";
        }

        if (empty($errors)) {
            $user_id = wp_create_user($username, $password, $email);
            
            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $full_name,
                    'first_name'   => $full_name
                ]);
                
                update_user_meta($user_id, 'billing_phone', $phone);
                update_user_meta($user_id, 'billing_postcode', $postcode);
                
                $full_address_string = "Flat $flat_no, $building, Door $door_no, $road_name. $address_gen";
                update_user_meta($user_id, 'billing_address_1', $full_address_string);
                
                update_user_meta($user_id, 'fd_flat_no', $flat_no);
                update_user_meta($user_id, 'fd_building', $building);
                update_user_meta($user_id, 'fd_door_no', $door_no);
                update_user_meta($user_id, 'fd_road_name', $road_name);
                update_user_meta($user_id, 'fd_user_phone', $phone);
                update_user_meta($user_id, 'fd_user_postcode', $postcode);
                
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
    .form-label { font-weight: 600; color: #444; font-size: 13px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
    .form-control { height: 45px; border-radius: 8px; border: 1px solid #ddd; padding: 10px 15px; width: 100%; transition: 0.3s; font-size: 15px; }
    .form-control:focus { border-color: #d63638; box-shadow: 0 0 0 3px rgba(214, 54, 56, 0.1); outline: none; }
    .reg-btn { background: #d63638; color: #fff; border: none; width: 100%; padding: 15px; border-radius: 10px; font-weight: 700; font-size: 16px; margin-top: 20px; transition: 0.3s; cursor: pointer; }
    .reg-btn:hover { background: #b52a2c; transform: translateY(-2px); }
    .reg-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
    textarea.form-control { height: auto; }
    #pc-live-error { color: #d63638; font-size: 11px; font-weight: 700; margin-top: 5px; display: none; }
</style>

<div class="reg-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="reg-card">
                    <div class="row g-0">
                        <div class="col-md-4 reg-info-side d-none d-md-flex">
                            <h3 class="text-white">Join Us!</h3>
                            <p class="text-white">Get your UK food delivery faster by saving your details with us.</p>
                            <ul class="mt-4 list-unstyled">
                                <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Standard 11-digit UK Security</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Postcode Validated Delivery</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Door-to-Door Tracking</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-8 reg-form-side">
                            <h2 class="mb-4" style="font-weight: 800;">Register Account</h2>
                            
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
                                        <input type="text" name="full_name" class="form-control" placeholder="e.g. John Smith" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input 
                                            type="tel" 
                                            name="phone" 
                                            class="form-control" 
                                            placeholder="07123456789" 
                                            maxlength="11"
                                            pattern="^0[0-9]{10}$"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                            required
                                        >
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

                                <hr class="my-4">
                                <h6 class="mb-3">Delivery Address</h6>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Flat No.</label>
                                        <input type="text" name="flat_no" class="form-control" placeholder="4B" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Building Name</label>
                                        <input type="text" name="building" class="form-control" placeholder="Skyline Tower" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Door / House No.</label>
                                        <input type="text" name="door_no" class="form-control" placeholder="10" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Road Name</label>
                                        <input type="text" name="road_name" class="form-control" placeholder="High Street" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">UK Postcode</label>
                                        <input type="text" id="regPostcode" name="postcode" class="form-control" placeholder="EN3 1AA" style="text-transform:uppercase" required>
                                        <div id="pc-live-error"></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Delivery Instructions</label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="e.g. Leave by the red gate..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                </div>

                                <button type="submit" id="regSubmitBtn" class="reg-btn">Create My Account</button>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(document).ready(function($) {
    $('#regPostcode').on('input', function() {
        const $el = $(this);
        const $err = $('#pc-live-error');
        const $btn = $('#regSubmitBtn');
        
        // Normalize: Uppercase and remove all spaces
        const val = $el.val().trim().toUpperCase().replace(/\s+/g, '');
        const allowed = ['EN3', 'EN1', 'EN2', 'EN8', 'N9', 'EN7'];
        
        if (val === "") {
            $el.css('border-color', ''); 
            $err.hide(); 
            $btn.prop('disabled', false);
            return;
        }

        const startMatch = allowed.some(p => val.startsWith(p));
        
        if (!startMatch) {
            $el.css('border-color', '#d63638');
            $err.text('We do not deliver to this area.').show();
            $btn.prop('disabled', true);
        } else if (val.length !== 6) {
            $el.css('border-color', '#d63638');
            $err.text('Must be 6 characters (e.g. EN31AA).').show();
            $btn.prop('disabled', true);
        } else {
            $el.css('border-color', '#10b981'); 
            $err.hide();
            $btn.prop('disabled', false);
        }
    });
});
</script>

<?php get_footer(); ?>