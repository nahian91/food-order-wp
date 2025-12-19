<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# 1. Enqueue Scripts & Media Library (Crucial Fix)
--------------------------------------------------------------*/
add_action('admin_enqueue_scripts', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'awesome_food_delivery') return;

    // This loads the WordPress Media Library (required for the image button)
    wp_enqueue_media();

    wp_enqueue_style('fd-datatable-css', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css');
    wp_enqueue_script('fd-datatable-js', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['jquery'], null, true);
});

/*--------------------------------------------------------------
# 2. Main Add/Edit Function
--------------------------------------------------------------*/
function fd_add_edit_item_tab($edit_item_id = 0) {

    $item = $edit_item_id ? get_post($edit_item_id) : null;

    // --- FORM SUBMISSION LOGIC ---
    if (!empty($_POST) && isset($_POST['fd_add_item_nonce']) && wp_verify_nonce($_POST['fd_add_item_nonce'], 'fd_add_item')) {
        $title  = sanitize_text_field($_POST['fd_item_name']);
        $desc   = wp_kses_post($_POST['fd_item_desc']);
        $price  = floatval($_POST['fd_item_price']);
        $cat    = intval($_POST['fd_item_cat']);
        $extras = isset($_POST['fd_item_extras']) ? array_map('intval', $_POST['fd_item_extras']) : [];

        $post_args = [
            'post_type'    => 'food_item',
            'post_title'   => $title,
            'post_content' => $desc,
            'post_status'  => 'publish'
        ];

        if ($item) {
            $post_args['ID'] = $edit_item_id;
            wp_update_post($post_args);
        } else {
            $edit_item_id = wp_insert_post($post_args);
        }

        if ($edit_item_id) {
            update_post_meta($edit_item_id, 'price', $price);
            wp_set_post_terms($edit_item_id, [$cat], 'food_category');
            update_post_meta($edit_item_id, 'fd_item_extras', $extras);
            
            // Image Saving Logic
            if (isset($_POST['fd_item_image_id'])) {
                $img_id = intval($_POST['fd_item_image_id']);
                ($img_id > 0) ? set_post_thumbnail($edit_item_id, $img_id) : delete_post_thumbnail($edit_item_id);
            }
            
            echo '<div class="updated notice is-dismissible" style="border-left-color: #d63638;"><p>Menu updated successfully!</p></div>';
            $item = get_post($edit_item_id); // Refresh data
        }
    }

    // --- DATA PREPARATION ---
    $title_val  = $item ? $item->post_title : '';
    $desc_val   = $item ? $item->post_content : '';
    $price_val  = $item ? get_post_meta($edit_item_id, 'price', true) : '';
    $cat_id     = $item ? (wp_get_post_terms($edit_item_id, 'food_category', ['fields' => 'ids'])[0] ?? 0) : 0;
    $extras_sel = $item ? (get_post_meta($edit_item_id, 'fd_item_extras', true) ?: []) : [];
    $img_id     = $item ? get_post_thumbnail_id($edit_item_id) : 0;
    $img_url    = $img_id ? wp_get_attachment_url($img_id) : '';

    $categories = get_terms(['taxonomy' => 'food_category', 'hide_empty' => false]);
    $extras_all = get_option('fd_extras', []);
    ?>

    <style>
        :root { 
            --res-primary: #d63638; 
            --res-dark: #1d2327;    
            --res-border: #ccd0d4; 
            --res-bg-soft: #fff9f9;
        }
        
        .afd-item-editor { margin-top: 20px; max-width: 1200px; color: var(--res-dark); }
        
        /* Layout Structure */
        .fd-main-label { display: block; margin-bottom: 8px; font-weight: 600; color: #50575e; font-size: 13px; }
        .fd-main-input { 
            width: 100%; border: 1px solid var(--res-border); border-radius: 6px;
            font-size: 18px; font-weight: 600; padding: 12px 15px; outline: none; 
            transition: 0.2s; margin-bottom: 25px; background: #fff; 
        }
        .fd-main-input:focus { border-color: var(--res-primary); box-shadow: 0 0 0 1px var(--res-primary); }

        .fd-grid-wrapper { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
        
        /* Cards */
        .fd-card { background: #fff; border: 1px solid var(--res-border); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
        .fd-card-head { padding: 12px 18px; border-bottom: 1px solid #f0f0f1; background: #fafafa; }
        .fd-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #646970; }
        .fd-card-body { padding: 18px; }

        /* Price & Image UI */
        .fd-price-box { display: flex; align-items: center; background: var(--res-bg-soft); border: 1px solid #f5c2c2; padding: 10px 15px; border-radius: 6px; }
        .fd-price-box span { font-weight: 700; color: var(--res-primary); margin-right: 10px; }
        .fd-price-box input { border: none; background: transparent; font-size: 18px; font-weight: 700; width: 100%; text-align: right; outline: none; }

        .fd-image-drop { text-align: center; border: 2px dashed var(--res-border); padding: 25px; border-radius: 8px; cursor: pointer; background: #fafafa; transition: 0.3s; }
        .fd-image-drop:hover { border-color: var(--res-primary); background: var(--res-bg-soft); }
        .fd-image-drop img { border-radius: 4px; max-width: 100%; height: auto; display: block; margin: 0 auto; }

        .fd-extras-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .fd-extra-label { display: flex; align-items: center; padding: 10px; border: 1px solid var(--res-border); border-radius: 6px; cursor: pointer; font-size: 13px; }
        
        .fd-bottom-bar { 
            position: sticky; bottom: 20px; z-index: 100; background: #fff; 
            padding: 15px 25px; border-radius: 8px; display: flex; justify-content: space-between; 
            align-items: center; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border: 1px solid var(--res-border); margin-top: 30px; 
        }
        .btn-main { background: var(--res-primary) !important; border-color: var(--res-primary) !important; color: #fff !important; font-weight: 600 !important; padding: 0 25px !important; height: 40px !important; border-radius: 4px !important; cursor: pointer; }
    </style>

    <div class="wrap afd-item-editor">
        <form method="post" id="fd-edit-form">
            <?php wp_nonce_field('fd_add_item', 'fd_add_item_nonce'); ?>
            
            <label class="fd-main-label">Food Item Name</label>
            <input type="text" name="fd_item_name" class="fd-main-input" placeholder="e.g. Classic Beef Burger" value="<?php echo esc_attr($title_val); ?>" required autofocus>

            <div class="fd-grid-wrapper">
                <div class="fd-primary-col">
                    <div class="fd-card">
                        <div class="fd-card-head"><h3>Description</h3></div>
                        <div class="fd-card-body">
                            <?php wp_editor($desc_val, 'fd_item_desc', ['textarea_rows' => 12, 'media_buttons' => false]); ?>
                        </div>
                    </div>

                    <div class="fd-card">
                        <div class="fd-card-head"><h3>Available Extras / Add-ons</h3></div>
                        <div class="fd-card-body">
                            <div class="fd-extras-grid">
                                <?php if (!empty($extras_all)): foreach ($extras_all as $k => $e): 
                                    $checked = in_array($k, $extras_sel) ? 'checked' : '';
                                ?>
                                    <label class="fd-extra-label">
                                        <input type="checkbox" name="fd_item_extras[]" value="<?php echo $k; ?>" <?php echo $checked; ?>> 
                                        <span><?php echo esc_html($e['name']); ?> (+£<?php echo number_format($e['price'], 2); ?>)</span>
                                    </label>
                                <?php endforeach; else: ?>
                                    <p style="color:#646970; font-size:13px;">No extras configured in settings.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fd-sidebar-col">
                    <div class="fd-card">
                        <div class="fd-card-head"><h3>Food Photo</h3></div>
                        <div class="fd-card-body">
                            <input type="hidden" name="fd_item_image_id" id="fd_item_image_id" value="<?php echo esc_attr($img_id); ?>">
                            <div id="fd_image_container" class="fd-image-drop">
                                <div id="fd_image_preview">
                                    <?php if ($img_url): ?>
                                        <img src="<?php echo $img_url; ?>">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-camera" style="font-size: 32px; width:32px; height:32px; color: #ccd0d4;"></span>
                                        <p style="margin: 8px 0 0; color: #646970; font-size:12px;">Add Food Image</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="#" id="fd_remove_img" style="color:#d63638; display:<?php echo $img_url ? 'block' : 'none'; ?>; text-align:center; margin-top:12px; font-size:12px; text-decoration:none;">Remove Photo</a>
                        </div>
                    </div>

                    <div class="fd-card">
                        <div class="fd-card-head"><h3>Pricing & Taxonomy</h3></div>
                        <div class="fd-card-body">
                            <label class="fd-main-label">Base Price</label>
                            <div class="fd-price-box" style="margin-bottom:20px;">
                                <span>£</span>
                                <input type="number" step="0.01" name="fd_item_price" value="<?php echo esc_attr($price_val); ?>" placeholder="0.00" required>
                            </div>

                            <label class="fd-main-label">Category</label>
                            <select name="fd_item_cat" style="width:100%; height:35px; border-radius:4px;">
                                <?php foreach ($categories as $c) {
                                    echo '<option value="' . $c->term_id . '" ' . selected($cat_id, $c->term_id, false) . '>' . $c->name . '</option>';
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fd-bottom-bar">
                <div style="font-size:13px; color:#646970;">
                    <span class="dashicons dashicons-admin-tools" style="color:var(--res-primary); margin-top:2px; font-size:17px;"></span> 
                    Live Restaurant Menu Editor
                </div>
                <div>
                    <a href="?page=awesome_food_delivery&tab=items" style="text-decoration:none; margin-right:20px; color:#646970;">Discard Changes</a>
                    <input type="submit" class="button btn-main" value="Save to Menu">
                </div>
            </div>
        </form>
    </div>

    

    <script>
    jQuery(document).ready(function($){
        var frame;
        
        // OPEN MEDIA LIBRARY
        $('#fd_image_container').on('click', function(e){
            e.preventDefault();

            if (frame) { frame.open(); return; }

            frame = wp.media({
                title: 'Select Food Image',
                button: { text: 'Set as Food Photo' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#fd_item_image_id').val(attachment.id);
                $('#fd_image_preview').html('<img src="'+attachment.url+'">');
                $('#fd_remove_img').show();
            });

            frame.open();
        });

        // REMOVE IMAGE
        $('#fd_remove_img').on('click', function(e){
            e.preventDefault();
            $('#fd_item_image_id').val('0');
            $('#fd_image_preview').html('<span class="dashicons dashicons-camera" style="font-size:32px; width:32px; height:32px; color:#ccd0d4;"></span><p style="margin:8px 0 0; color:#646970; font-size:12px;">Add Food Image</p>');
            $(this).hide();
        });
    });
    </script>
<?php
}