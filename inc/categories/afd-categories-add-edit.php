<?php

/*-----------------------------------------
# Add / Edit Category
-----------------------------------------*/
function fd_category_add_edit(){
    // Handle form submit
    if(isset($_POST['fd_cat_nonce']) && wp_verify_nonce($_POST['fd_cat_nonce'],'fd_cat_action')){
        $name = sanitize_text_field($_POST['fd_cat_name']);
        $img_id = intval($_POST['fd_category_image']);
        $edit = intval($_POST['fd_cat_edit']);

        if($edit){ // Update
            wp_update_term($edit,'food_category',['name'=>$name]);
            update_term_meta($edit,'fd_category_image',$img_id);
            echo '<div class="notice notice-success is-dismissible"><p>Category updated.</p></div>';
        } else { // Add
            $term = wp_insert_term($name,'food_category');
            if(!is_wp_error($term)){
                if($img_id) add_term_meta($term['term_id'],'fd_category_image',$img_id);
                echo '<div class="notice notice-success is-dismissible"><p>Category added.</p></div>';
            }
        }
    }

    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $edit_term = $edit_id ? get_term($edit_id,'food_category') : null;
    $edit_img = $edit_id ? get_term_meta($edit_id,'fd_category_image',true) : '';
    ?>

    <div class="metabox-holder columns-2">
        <div class="postbox"><h2 class="hndle"><span><?php echo $edit_id?'Edit Category':'Add New Category'; ?></span></h2>
        <div class="inside">
            <form method="post">
                <?php wp_nonce_field('fd_cat_action','fd_cat_nonce'); ?>
                <input type="hidden" name="fd_cat_edit" value="<?php echo esc_attr($edit_id); ?>">

                <table class="form-table">
                    <tr>
                        <th>Category Name</th>
                        <td><input type="text" name="fd_cat_name" class="regular-text" value="<?php echo esc_attr($edit_term->name ?? ''); ?>" required></td>
                    </tr>
                    <tr>
                        <th>Category Image</th>
                        <td>
                            <input type="hidden" name="fd_category_image" id="fd_category_image" value="<?php echo esc_attr($edit_img); ?>">
                            <button type="button" class="button" id="fd_cat_upload"><?php echo $edit_img?'Change Image':'Select Image'; ?></button>
                            <div id="fd_cat_preview" style="margin-top:10px;">
                                <?php if($edit_img) echo wp_get_attachment_image($edit_img,[80,80]); ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo $edit_id?'Update Category':'Add Category'; ?>">
                    <?php if($edit_id): ?><a href="?page=awesome_food_delivery&tab=categories" class="button">Cancel</a><?php endif; ?>
                </p>
            </form>
        </div></div>
    </div>

    <script>
    jQuery(function($){
        var frame;
        $('#fd_cat_upload').on('click',function(e){
            e.preventDefault();
            frame = wp.media({title:'Select Category Image',button:{text:'Use Image'},multiple:false});
            frame.on('select',function(){
                var img = frame.state().get('selection').first().toJSON();
                $('#fd_category_image').val(img.id);
                $('#fd_cat_preview').html('<img src="'+img.url+'" style="max-width:80px;">');
            });
            frame.open();
        });
    });
    </script>

<?php
}