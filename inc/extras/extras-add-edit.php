<?php
if(!defined('ABSPATH')) exit;

/**
 * Add / Edit Extra - SaaS Configuration UI (Refactored)
 */
function fd_add_extra_tab(){
    $afon_edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $afon_extras = get_option('fd_extras', []);
    $afon_edit_item = ($afon_edit_id !== null && isset($afon_extras[$afon_edit_id])) ? $afon_extras[$afon_edit_id] : null;

    // Form Processing
    if($_POST && isset($_POST['afon_extra_nonce']) && wp_verify_nonce($_POST['afon_extra_nonce'], 'afon_save_extra')){
        $afon_name     = sanitize_text_field($_POST['afon_extra_name']);
        $afon_file_id  = intval($_POST['afon_extra_file'] ?? 0);
        $afon_price    = floatval($_POST['afon_extra_price'] ?? 0);
        $afon_qty      = intval($_POST['afon_extra_qty'] ?? 0);

        $afon_new_entry = [
            'name'     => $afon_name,
            'file_id'  => ($afon_file_id > 0) ? $afon_file_id : ($afon_edit_item['file_id'] ?? 0),
            'price'    => $afon_price,
            'quantity' => $afon_qty
        ];

        if($afon_edit_id !== null){
            $afon_extras[$afon_edit_id] = $afon_new_entry;
            echo '<div class="updated notice is-dismissible"><p>Extra topping updated.</p></div>';
        } else {
            $afon_extras[] = $afon_new_entry;
            echo '<div class="updated notice is-dismissible"><p>New extra topping created.</p></div>';
        }

        update_option('fd_extras', $afon_extras);
        $afon_edit_item = $afon_new_entry; // Refresh for view
    }

    $afon_img_url = (!empty($afon_edit_item['file_id'])) ? wp_get_attachment_url($afon_edit_item['file_id']) : '';
    ?>

    <div class="wrap afon-wrap">
        <form method="post" id="afon-extra-config-form">
            <?php wp_nonce_field('afon_save_extra', 'afon_extra_nonce'); ?>
            
            <div class="afon-extra-wrapper">
                
                <div class="afon-conf-card">
                    <div class="afon-conf-header">
                        <h2><?php echo $afon_edit_id !== null ? 'Edit Topping Settings' : 'Create New Topping'; ?></h2>
                    </div>
                    
                    <div class="afon-conf-body">
                        <div class="afon-form-row">
                            <label>Topping Name</label>
                            <input type="text" name="afon_extra_name" class="afon-input-modern" placeholder="e.g. Extra Mozzarella" required value="<?php echo esc_attr($afon_edit_item['name'] ?? ''); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="afon-form-row">
                                <label>Price (€)</label>
                                <input type="number" step="0.01" min="0" name="afon_extra_price" class="afon-input-modern" required value="<?php echo esc_attr($afon_edit_item['price'] ?? 0); ?>">
                            </div>
                            <div class="afon-form-row">
                                <label>Inventory Level</label>
                                <input type="number" min="0" name="afon_extra_qty" class="afon-input-modern" required value="<?php echo esc_attr($afon_edit_item['quantity'] ?? 0); ?>">
                            </div>
                        </div>

                        <div class="afon-form-row">
                            <label>Topping Image / Icon</label>
                            <input type="hidden" name="afon_extra_file" id="afon-extra-file-id" value="<?php echo esc_attr($afon_edit_item['file_id'] ?? ''); ?>">
                            <div id="afon-extra-dropzone" class="afon-extra-upload">
                                <div id="afon-extra-preview">
                                    <?php if($afon_img_url): ?>
                                        <img src="<?php echo esc_url($afon_img_url); ?>">
                                        <p class="afon-upload-text">Click to change icon</p>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-cloud-upload" style="font-size: 40px; width: 40px; height: 40px; color: #ccd0d4;"></span>
                                        <p style="margin: 10px 0 0; color: #646970; font-weight: 500;">Click to upload topping image</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="afon-sidebar">
                    <div class="afon-summary-box">
                        <h3 style="margin-top:0; font-size:14px; text-transform:uppercase; letter-spacing:1px;">Summary</h3>
                        
                        <div class="afon-summary-item">
                            <span>Availability</span>
                            <strong id="afon-live-status"><?php echo (isset($afon_edit_item['quantity']) && $afon_edit_item['quantity'] > 0) ? 'Available' : 'No Stock'; ?></strong>
                        </div>
                        
                        <div class="afon-summary-item">
                            <span>Price Point</span>
                            <strong id="afon-live-price"><?php echo number_format($afon_edit_item['price'] ?? 0, 2); ?> €</strong>
                        </div>
                        
                        <hr style="border:none; border-top:1px solid #dcdcde; margin: 15px 0;">
                        
                        <button type="submit" class="afon-btn-save">
                            <?php echo $afon_edit_id !== null ? 'Update Resource' : 'Save Topping'; ?>
                        </button>
                        
                        <a href="?page=awesome_food_delivery&tab=extras" style="display:block; text-align:center; margin-top:15px; color:#646970; text-decoration:none; font-size:13px; font-weight: 500;">
                            &larr; Cancel and go back
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
<?php
}