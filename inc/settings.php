<?php
/*--------------------------------------------------------------
# Settings Tab - Extended WP Admin Layout
--------------------------------------------------------------*/
function fd_settings_tab(){
    $sub_tabs = [
        'general' => 'General',
        'checkout'=> 'Checkout',
        'notifications' => 'Notifications'
    ];
    $active_sub = $_GET['sub'] ?? 'general';

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Settings</h1>';

    // Subtabs
    echo '<h2 class="nav-tab-wrapper">';
    foreach($sub_tabs as $key => $label){
        $active_class = $active_sub === $key ? ' nav-tab-active' : '';
        echo '<a class="nav-tab'.$active_class.'" href="?page=food_delivery&tab=settings&sub='.$key.'">'.$label.'</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    // Handle form submission
    if($_POST && isset($_POST['fd_settings_nonce']) && wp_verify_nonce($_POST['fd_settings_nonce'],'fd_settings')){
        switch($active_sub){
            case 'general':
                update_option('fd_currency', sanitize_text_field($_POST['fd_currency']));
                update_option('fd_site_name', sanitize_text_field($_POST['fd_site_name']));
                update_option('fd_timezone', sanitize_text_field($_POST['fd_timezone']));
                update_option('fd_store_address', sanitize_textarea_field($_POST['fd_store_address']));
                update_option('fd_phone', sanitize_text_field($_POST['fd_phone']));
                echo '<div class="notice notice-success is-dismissible"><p>General settings saved!</p></div>';
                break;
            case 'checkout':
                update_option('fd_delivery_charge', floatval($_POST['fd_delivery_charge']));
                update_option('fd_min_order', floatval($_POST['fd_min_order']));
                update_option('fd_free_delivery_min', floatval($_POST['fd_free_delivery_min']));
                echo '<div class="notice notice-success is-dismissible"><p>Checkout settings saved!</p></div>';
                break;
            case 'notifications':
                update_option('fd_notify_email', sanitize_email($_POST['fd_notify_email']));
                update_option('fd_notify_sms', sanitize_text_field($_POST['fd_notify_sms']));
                update_option('fd_notify_admin', sanitize_text_field($_POST['fd_notify_admin']));
                echo '<div class="notice notice-success is-dismissible"><p>Notification settings saved!</p></div>';
                break;
        }
    }

    // Get current option values
    $currency = get_option('fd_currency','৳');
    $site_name = get_option('fd_site_name','My Food Delivery');
    $timezone = get_option('fd_timezone', get_option('timezone_string','Asia/Dhaka'));
    $store_address = get_option('fd_store_address','');
    $phone = get_option('fd_phone','');
    $delivery = get_option('fd_delivery_charge',0);
    $min_order = get_option('fd_min_order',0);
    $free_delivery = get_option('fd_free_delivery_min',0);
    $notify_email = get_option('fd_notify_email', get_option('admin_email'));
    $notify_sms = get_option('fd_notify_sms','');
    $notify_admin = get_option('fd_notify_admin','yes');

    echo '<div class="metabox-holder">';
    echo '<div class="postbox"><div class="inside">';
    echo '<form method="post">';
    wp_nonce_field('fd_settings','fd_settings_nonce');

    echo '<table class="form-table">';
    switch($active_sub){
        case 'general':
            echo '<tr><th>Site Name</th><td><input type="text" name="fd_site_name" class="regular-text" value="'.esc_attr($site_name).'"></td></tr>';
            echo '<tr><th>Currency Symbol</th><td><input type="text" name="fd_currency" class="regular-text" value="'.esc_attr($currency).'"></td></tr>';
            echo '<tr><th>Time Zone</th><td><input type="text" name="fd_timezone" class="regular-text" value="'.esc_attr($timezone).'"></td></tr>';
            echo '<tr><th>Store Address</th><td><textarea name="fd_store_address" class="large-text" rows="3">'.esc_textarea($store_address).'</textarea></td></tr>';
            echo '<tr><th>Phone Number</th><td><input type="text" name="fd_phone" class="regular-text" value="'.esc_attr($phone).'"></td></tr>';
            break;
        case 'checkout':
            echo '<tr><th>Delivery Charge</th><td><input type="number" step="0.01" name="fd_delivery_charge" class="small-text" value="'.esc_attr($delivery).'"></td></tr>';
            echo '<tr><th>Minimum Order Amount</th><td><input type="number" step="0.01" name="fd_min_order" class="small-text" value="'.esc_attr($min_order).'"></td></tr>';
            echo '<tr><th>Free Delivery Over</th><td><input type="number" step="0.01" name="fd_free_delivery_min" class="small-text" value="'.esc_attr($free_delivery).'"></td></tr>';
            break;
        case 'notifications':
            echo '<tr><th>Notification Email</th><td><input type="email" name="fd_notify_email" class="regular-text" value="'.esc_attr($notify_email).'"></td></tr>';
            echo '<tr><th>Notification SMS Number</th><td><input type="text" name="fd_notify_sms" class="regular-text" value="'.esc_attr($notify_sms).'"></td></tr>';
            echo '<tr><th>Notify Admin</th><td>
                <select name="fd_notify_admin">
                    <option value="yes" '.selected($notify_admin,'yes',false).'>Yes</option>
                    <option value="no" '.selected($notify_admin,'no',false).'>No</option>
                </select>
            </td></tr>';
            break;
    }
    echo '</table>';

    echo '<p class="submit"><input type="submit" class="button button-primary" value="Save Settings"></p>';
    echo '</form>';
    echo '</div></div>'; // postbox
    echo '</div>'; // metabox-holder
    echo '</div>'; // wrap
}
