<?php

/**
 * Global function to check restaurant status
 * Included here to ensure the Settings Tab can access it for the Live Badge
 */
if ( ! function_exists( 'get_afd_restaurant_status' ) ) {
    function get_afd_restaurant_status() {
        $open_time    = get_option('afd_open_time', '09:00');
        $close_time   = get_option('afd_close_time', '22:00');
        $work_days    = get_option('afd_work_days', []);
        $closed_msg   = get_option('afd_status_message', 'Sorry, we are currently closed!');

        $now          = current_datetime(); 
        $current_day  = $now->format('D'); 
        $current_time = $now->format('H:i');

        // Robust comparison using timestamps
        $current_ts = strtotime($current_time);
        $open_ts    = strtotime($open_time);
        $close_ts   = strtotime($close_time);

        $is_open_day  = in_array($current_day, $work_days);
        $is_open_time = ($current_ts >= $open_ts && $current_ts <= $close_ts);

        if (!$is_open_day || !$is_open_time) {
            return ['is_open' => false, 'message' => $closed_msg];
        }
        return ['is_open' => true, 'message' => ''];
    }
}

function fd_settings_tab() {
    // 1. Process Data Saving
    if (isset($_POST['afd_save_settings'])) {
        update_option('afd_open_time', sanitize_text_field($_POST['afd_open_time']));
        update_option('afd_close_time', sanitize_text_field($_POST['afd_close_time']));
        update_option('afd_status_message', sanitize_textarea_field($_POST['afd_status_message']));
        
        $selected_days = isset($_POST['afd_work_days']) ? $_POST['afd_work_days'] : [];
        update_option('afd_work_days', $selected_days);
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings updated successfully.</p></div>';
    }

    // 2. Fetch Values
    $open_time  = get_option('afd_open_time', '09:00');
    $close_time = get_option('afd_close_time', '22:00');
    $message    = get_option('afd_status_message', 'Sorry, we are currently closed!');
    $work_days  = get_option('afd_work_days', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']); 
    
    $days_of_week = [
        'Mon' => 'Mon', 'Tue' => 'Tue', 'Wed' => 'Wed', 
        'Thu' => 'Thu', 'Fri' => 'Fri', 'Sat' => 'Sat', 'Sun' => 'Sun'
    ];

    $status = get_afd_restaurant_status();
    $wp_timezone = wp_timezone_string();
    $current_wp_time = current_datetime()->format('H:i');
    ?>

    <div class="afd-tab-content">
        <div class="afd-card">
            <div class="afd-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3><span class="dashicons dashicons-clock"></span> Restaurant Schedule</h3>
                    <p>System Time: <strong><?php echo $current_wp_time; ?></strong> | Timezone: <code><?php echo $wp_timezone; ?></code></p>
                </div>
                <div class="afd-status-badge <?php echo $status['is_open'] ? 'is-open' : 'is-closed'; ?>">
                    <?php echo $status['is_open'] ? '● LIVE: OPEN' : '● LIVE: CLOSED'; ?>
                </div>
            </div>

            <form method="post" action="">
                <div class="afd-settings-grid">
                    
                    <div class="afd-settings-section">
                        <h4>Operating Days</h4>
                        <p class="afd-hint-text">Green = Open | Red = Closed</p>
                        
                        <div class="afd-days-selector">
                            <?php foreach ($days_of_week as $key => $label) : 
                                $is_checked = in_array($key, $work_days);
                            ?>
                                <label class="day-pill">
                                    <input type="checkbox" name="afd_work_days[]" value="<?php echo $key; ?>" <?php checked($is_checked); ?>>
                                    <span class="day-name"><?php echo $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <h4 style="margin-top:30px;">Shift Hours</h4>
                        <div class="afd-time-picker-row">
                            <div class="afd-time-field">
                                <label><span class="dashicons dashicons-external"></span> Opens At</label>
                                <input type="time" name="afd_open_time" value="<?php echo esc_attr($open_time); ?>">
                            </div>
                            <div class="afd-time-field">
                                <label><span class="dashicons dashicons-download"></span> Closes At</label>
                                <input type="time" name="afd_close_time" value="<?php echo esc_attr($close_time); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="afd-settings-section">
                        <h4>Closure Notification</h4>
                        <div class="afd-input-group">
                            <label>Frontend Message</label>
                            <textarea name="afd_status_message" rows="7" placeholder="e.g. We are closed for the day..."><?php echo esc_textarea($message); ?></textarea>
                        </div>
                    </div>

                </div>

                <div class="afd-card-footer">
                    <button type="submit" name="afd_save_settings" class="afd-save-btn">
                        <span class="dashicons dashicons-saved"></span> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .afd-status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 800; font-size: 12px; letter-spacing: 1px; }
        .is-open { background: #f0f9eb; color: #67c23a; border: 1px solid #c2e7b0; }
        .is-closed { background: #ffeded; color: #f56c6c; border: 1px solid #fbc4c4; }

        .afd-tab-content { padding: 10px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; }
        .afd-card { background: #fff; border-radius: 12px; border: 1px solid #dcdfe6; box-shadow: 0 2px 12px 0 rgba(0,0,0,.1); overflow: hidden; }
        .afd-card-header { padding: 20px 25px; border-bottom: 1px solid #f0f2f5; background: #fafafa; }
        .afd-card-header h3 { margin: 0; color: #1f2f3d; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        
        .afd-settings-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; padding: 30px 25px; }
        .afd-settings-section h4 { margin: 0 0 15px 0; font-size: 14px; color: #606266; text-transform: uppercase; letter-spacing: 1px; }
        .afd-hint-text { font-size: 12px; color: #909399; margin-bottom: 15px; }

        .afd-days-selector { display: flex; gap: 10px; flex-wrap: wrap; }
        .day-pill input { display: none; }
        .day-name { 
            display: block; padding: 10px 15px; border-radius: 6px; cursor: pointer;
            font-weight: 600; font-size: 13px; text-align: center; min-width: 45px;
            transition: all 0.2s ease;
            background: #ffeded; color: #f56c6c; border: 1px solid #fbc4c4; 
        }
        .day-pill input:checked + .day-name { background: #f0f9eb; color: #67c23a; border-color: #c2e7b0; }
        
        .afd-time-picker-row { display: flex; gap: 20px; }
        .afd-time-field { flex: 1; }
        .afd-time-field label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; color: #475569; }
        .afd-time-field input[type="time"] { width: 100%; padding: 10px; border: 1px solid #dcdfe6; border-radius: 6px; font-size: 15px; }
        
        .afd-input-group textarea { width: 100%; border: 1px solid #dcdfe6; border-radius: 6px; padding: 12px; font-size: 14px; resize: none; }
        .afd-card-footer { padding: 20px 25px; background: #fafafa; border-top: 1px solid #f0f2f5; text-align: right; }
        .afd-save-btn { background: #2271b1; color: #fff; border: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        
        @media (max-width: 900px) { .afd-settings-grid { grid-template-columns: 1fr; } }
    </style>
    <?php
}