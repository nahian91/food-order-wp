<?php
if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Add / Edit Item - Classic Layout + Media Library + Extras Name Only
--------------------------------------------------------------*/
function fd_add_edit_item_tab($edit_item_id = 0){

    $item = $edit_item_id ? get_post($edit_item_id) : null;

    // Handle form submission
    if(!empty($_POST) && isset($_POST['fd_add_item_nonce']) && wp_verify_nonce($_POST['fd_add_item_nonce'],'fd_add_item')){
        $title  = sanitize_text_field($_POST['fd_item_name']);
        $desc   = sanitize_textarea_field($_POST['fd_item_desc']);
        $price  = floatval($_POST['fd_item_price']);
        $cat    = intval($_POST['fd_item_cat']);
        $extras = array_map('intval', $_POST['fd_item_extras'] ?? []);

        if($item){
            wp_update_post([
                'ID'           => $edit_item_id,
                'post_title'   => $title,
                'post_content' => $desc,
            ]);
        } else {
            $edit_item_id = wp_insert_post([
                'post_type'    => 'food_item',
                'post_title'   => $title,
                'post_content' => $desc,
                'post_status'  => 'publish'
            ]);
        }

        if($edit_item_id){
            update_post_meta($edit_item_id,'price',$price);
            wp_set_post_terms($edit_item_id, [$cat], 'food_category');
            update_post_meta($edit_item_id,'fd_item_extras',$extras);

            // Save featured image from media library selection
            if(!empty($_POST['fd_item_image_id'])){
                set_post_thumbnail($edit_item_id,intval($_POST['fd_item_image_id']));
            }

            echo '<div class="notice notice-success is-dismissible"><p>';
            echo $item ? 'Item updated successfully!' : 'Item added successfully!';
            echo '</p></div>';
        }
    }

    // Default values
    $title_val   = $item ? $item->post_title : '';
    $desc_val    = $item ? $item->post_content : '';
    $price_val   = $item ? get_post_meta($edit_item_id,'price',true) : '';
    $cat_id      = $item ? (wp_get_post_terms($edit_item_id,'food_category',['fields'=>'ids'])[0] ?? 0) : 0;
    $extras_sel  = $item ? get_post_meta($edit_item_id,'fd_item_extras',true) : [];
    $img_id      = $item ? get_post_thumbnail_id($edit_item_id) : 0;

    $categories = get_terms(['taxonomy'=>'food_category','hide_empty'=>false]);
    $extras_all = get_option('fd_extras',[]); // only names
    ?>

    <div id="fd-metaboxes" class="metabox-holder columns-2">

        <!-- Main content column -->
        <div id="post-body">
            <div id="post-body-content">
                <div class="postbox">
                    <h2 class="hndle"><span><?php echo $item ? 'Edit Item' : 'Add Item'; ?></span></h2>
                    <div class="inside">

                        <form method="post">
                            <?php wp_nonce_field('fd_add_item','fd_add_item_nonce'); ?>

                            <table class="form-table">

                                <tr>
                                    <th>Name</th>
                                    <td><input type="text" name="fd_item_name" class="regular-text" required value="<?php echo esc_attr($title_val); ?>"></td>
                                </tr>

                                <tr>
                                    <th>Description</th>
                                    <td>
                                        <?php
                                        wp_editor($desc_val,'fd_item_desc',[
                                            'textarea_name'=>'fd_item_desc',
                                            'media_buttons'=>true,
                                            'textarea_rows'=>6
                                        ]);
                                        ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Price</th>
                                    <td><input type="number" step="0.01" name="fd_item_price" class="small-text" required value="<?php echo esc_attr($price_val); ?>"></td>
                                </tr>

                                <tr>
                                    <th>Category</th>
                                    <td>
                                        <select name="fd_item_cat">
                                            <?php foreach($categories as $c){
                                                echo '<option value="'.$c->term_id.'" '.selected($cat_id,$c->term_id,false).'>'.$c->name.'</option>';
                                            } ?>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Extras</th>
                                    <td>
                                        <?php
                                        foreach($extras_all as $k=>$e){
                                            $checked = in_array($k,$extras_sel) ? 'checked' : '';
                                            echo '<label><input type="checkbox" name="fd_item_extras[]" value="'.$k.'" '.$checked.'> '.esc_html($e['name']).'</label><br>';
                                        }
                                        ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Item Image</th>
                                    <td>
                                        <input type="hidden" name="fd_item_image_id" id="fd_item_image_id" value="<?php echo esc_attr($img_id); ?>">
                                        <div id="fd_item_image_preview">
                                            <?php if($img_id) echo wp_get_attachment_image($img_id,[100,100]); ?>
                                        </div>
                                        <button type="button" class="button" id="fd_select_image_btn">Select from Media Library</button>
                                        <button type="button" class="button" id="fd_remove_image_btn">Remove Image</button>
                                    </td>
                                </tr>

                            </table>

                            <p class="submit">
                                <input type="submit" class="button button-primary" value="<?php echo $item ? 'Update Item' : 'Add Item'; ?>">
                            </p>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        var frame;
        $('#fd_select_image_btn').click(function(e){
            e.preventDefault();
            if(frame){ frame.open(); return; }

            frame = wp.media({
                title: 'Select or Upload Item Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#fd_item_image_id').val(attachment.id);
                $('#fd_item_image_preview').html('<img src="'+attachment.url+'" style="max-width:100px;">');
            });

            frame.open();
        });

        $('#fd_remove_image_btn').click(function(){
            $('#fd_item_image_id').val('');
            $('#fd_item_image_preview').html('');
        });
    });
    </script>
<?php
}
