<?php

/*--------------------------------------------------------------
# Add / Edit Category - SaaS Modern UI with Ordering
--------------------------------------------------------------*/
function fd_category_add_edit(){
    // Handle form submit logic
    if(isset($_POST['fd_cat_nonce']) && wp_verify_nonce($_POST['fd_cat_nonce'],'fd_cat_action')){
        $name = sanitize_text_field($_POST['fd_cat_name']);
        $img_id = intval($_POST['fd_category_image']);
        $order = intval($_POST['fd_cat_order']); // New Ordering Field
        $edit = intval($_POST['fd_cat_edit']);

        if($edit){ 
            wp_update_term($edit, 'food_category', ['name' => $name]);
            update_term_meta($edit, 'fd_category_image', $img_id);
            update_term_meta($edit, 'fd_category_order', $order); // Save Order
            echo '<div class="updated notice is-dismissible"><p>Category updated successfully.</p></div>';
        } else { 
            $term = wp_insert_term($name, 'food_category');
            if(!is_wp_error($term)){
                if($img_id) add_term_meta($term['term_id'], 'fd_category_image', $img_id);
                add_term_meta($term['term_id'], 'fd_category_order', $order); // Save Order
                echo '<div class="updated notice is-dismissible"><p>Category created successfully.</p></div>';
            }
        }
    }

    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $edit_term = $edit_id ? get_term($edit_id, 'food_category') : null;
    $edit_img_id = $edit_id ? get_term_meta($edit_id, 'fd_category_image', true) : '';
    $edit_img_url = $edit_img_id ? wp_get_attachment_url($edit_img_id) : '';
    $edit_order = $edit_id ? get_term_meta($edit_id, 'fd_category_order', true) : 0; // Get existing order
    ?>

    <style>
        /* ... keeping your existing styles ... */
        .fd-cat-container { max-width: 600px; margin: 40px auto; }
        .fd-card { background: #fff; border-radius: 12px; border: 1px solid #dcdcde; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .fd-card-header { padding: 25px; border-bottom: 1px solid #f0f0f1; background: #fff; text-align: center; }
        .fd-card-header h2 { margin: 0; font-size: 20px; font-weight: 700; color: #1d2327; }
        .fd-card-body { padding: 30px; }
        .fd-input-group { margin-bottom: 25px; }
        .fd-input-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327; font-size: 14px; }
        .fd-styled-input { width: 100%; padding: 12px 15px; border: 1px solid #dcdcde; border-radius: 8px; font-size: 16px; transition: 0.2s; }
        .fd-styled-input:focus { border-color: #2271b1; outline: none; box-shadow: 0 0 0 2px rgba(34,113,177,0.1); }
        .fd-cat-upload-zone { border: 2px dashed #dcdcde; border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: 0.3s; background: #f9f9f9; position: relative; }
        .fd-cat-upload-zone:hover { border-color: #2271b1; background: #f0f7ff; }
        .fd-cat-upload-zone img { max-width: 120px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .fd-card-footer { padding: 20px 30px; background: #f8f9fa; border-top: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center; }
        .fd-btn-save { background: #2271b1; color: #fff; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px; }
        .fd-btn-save:hover { background: #135e96; }
        .fd-btn-cancel { color: #646970; text-decoration: none; font-size: 14px; }
    </style>

    <div class="fd-cat-container">
        <div class="fd-card">
            <div class="fd-card-header">
                <h2><?php echo $edit_id ? 'Update Category' : 'Create New Category'; ?></h2>
            </div>
            
            <form method="post">
                <?php wp_nonce_field('fd_cat_action','fd_cat_nonce'); ?>
                <input type="hidden" name="fd_cat_edit" value="<?php echo esc_attr($edit_id); ?>">
                
                <div class="fd-card-body">
                    <div class="fd-input-group">
                        <label>Category Name</label>
                        <input type="text" name="fd_cat_name" class="fd-styled-input" 
                               placeholder="e.g. Italian Pizzas" 
                               value="<?php echo esc_attr($edit_term->name ?? ''); ?>" required>
                    </div>

                    <div class="fd-input-group">
                        <label>Display Order</label>
                        <input type="number" name="fd_cat_order" class="fd-styled-input" 
                               placeholder="0" 
                               value="<?php echo esc_attr($edit_order); ?>">
                        <small style="color: #646970;">Higher numbers appear last.</small>
                    </div>

                    <div class="fd-input-group">
                        <label>Display Image</label>
                        <input type="hidden" name="fd_category_image" id="fd_category_image" value="<?php echo esc_attr($edit_img_id); ?>">
                        
                        <div id="fd_cat_dropzone" class="fd-cat-upload-zone">
                            <div id="fd_cat_preview_inner">
                                <?php if($edit_img_url): ?>
                                    <img src="<?php echo $edit_img_url; ?>">
                                    <p style="margin-top:10px; font-size:12px; color:#646970;">Click to change image</p>
                                <?php else: ?>
                                    <span class="dashicons dashicons-camera" style="font-size: 40px; width: 40px; height: 40px; color: #adb5bd;"></span>
                                    <p style="margin: 10px 0 0; color: #646970; font-weight:500;">Select Category Image</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fd-card-footer">
                    <a href="?page=awesome_food_delivery&tab=categories" class="fd-btn-cancel">Cancel & Exit</a>
                    <button type="submit" class="fd-btn-save">
                        <?php echo $edit_id ? 'Save Changes' : 'Create Category'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    jQuery(function($){
        var frame;
        $('#fd_cat_dropzone').on('click', function(e){
            e.preventDefault();
            if(frame){ frame.open(); return; }
            frame = wp.media({
                title: 'Category Image',
                button: { text: 'Apply Image' },
                multiple: false
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#fd_category_image').val(attachment.id);
                $('#fd_cat_preview_inner').html('<img src="'+attachment.url+'"><p style="margin-top:10px; font-size:12px; color:#646970;">Click to change image</p>');
            });
            frame.open();
        });
    });
    </script>
<?php
}