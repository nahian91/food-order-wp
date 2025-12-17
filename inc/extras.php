<?php
if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Extras Tab - Full Separate HTML/PHP
--------------------------------------------------------------*/

/*--------------------------------------------------------------
# Enqueue Media Scripts (for Add/Edit Extra)
--------------------------------------------------------------*/
function fd_enqueue_extra_media_scripts($hook) {
    if($hook !== 'toplevel_page_awesome_food_delivery') return;
    if(isset($_GET['tab']) && $_GET['tab']==='extras'){
        wp_enqueue_media();
        add_action('admin_footer', function(){
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($){
                var file_frame;
                $('#fd_extra_file_button').on('click', function(e){
                    e.preventDefault();
                    if(file_frame){ file_frame.open(); return; }
                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: 'Select or Upload File',
                        button: { text: 'Use this file' },
                        multiple: false
                    });
                    file_frame.on('select', function(){
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        $('#fd_extra_file').val(attachment.id);
                        var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                        $('#fd_extra_file_preview').html('<img src="'+thumb+'" style="max-width:80px;max-height:80px;" />');
                    });
                    file_frame.open();
                });
            });
            </script>
            <?php
        });
    }
}
add_action('admin_enqueue_scripts', 'fd_enqueue_extra_media_scripts');

/*--------------------------------------------------------------
# Extras Tab Navigation
--------------------------------------------------------------*/
function fd_extras_tab(){
    $sub_tabs = ['add'=>'Add Extra','all'=>'All Extras'];
    $active_sub = $_GET['sub'] ?? 'add';
    ?>
    <h2 class="nav-tab-wrapper">
        <?php foreach($sub_tabs as $k=>$label): ?>
            <a class="nav-tab <?php echo ($active_sub==$k?'nav-tab-active':''); ?>" href="?page=awesome_food_delivery&tab=extras&sub=<?php echo esc_attr($k); ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    <div style="margin-top:20px;">
    <?php
    switch($active_sub){
        case 'add': fd_add_extra_tab(); break;
        case 'all': fd_all_extras_tab(); break;
        case 'view': fd_view_extra_tab(intval($_GET['item'] ?? 0)); break;
    }
    ?>
    </div>
    <?php
}

/*--------------------------------------------------------------
# Add/Edit Extra Layout
--------------------------------------------------------------*/
function fd_add_extra_tab(){
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $extras = get_option('fd_extras', []);
    $edit_item = $edit_id!==null && isset($extras[$edit_id]) ? $extras[$edit_id] : null;

    if($_POST && isset($_POST['fd_add_extra_nonce']) && wp_verify_nonce($_POST['fd_add_extra_nonce'],'fd_add_extra')){
        $extra_name = sanitize_text_field($_POST['fd_extra_name']);
        $attachment_id = intval($_POST['fd_extra_file'] ?? 0);
        $price = floatval($_POST['fd_extra_price'] ?? 0);
        $quantity = intval($_POST['fd_extra_qty'] ?? 1);

        if($edit_item){
            $extras[$edit_id] = [
                'name' => $extra_name,
                'file_id' => $attachment_id ?: $edit_item['file_id'],
                'price' => $price,
                'quantity' => $quantity
            ];
            echo '<div class="notice notice-success is-dismissible"><p>Extra updated successfully!</p></div>';
        } else {
            $extras[] = [
                'name' => $extra_name,
                'file_id' => $attachment_id,
                'price' => $price,
                'quantity' => $quantity
            ];
            echo '<div class="notice notice-success is-dismissible"><p>Extra added successfully!</p></div>';
        }

        update_option('fd_extras', $extras);
        $edit_item = $extras[$edit_id] ?? null;
    }
    ?>
    <div class="metabox-holder columns-2">
        <div class="postbox">
            <h2 class="hndle"><span><?php echo $edit_item?'Edit Extra':'Add New Extra'; ?></span></h2>
            <div class="inside">
                <form method="post">
                    <?php wp_nonce_field('fd_add_extra','fd_add_extra_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Extra Name</th>
                            <td><input type="text" name="fd_extra_name" class="regular-text" required value="<?php echo esc_attr($edit_item['name'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th>Price</th>
                            <td><input type="number" step="0.01" min="0" name="fd_extra_price" class="regular-text" required value="<?php echo esc_attr($edit_item['price'] ?? '0'); ?>"></td>
                        </tr>
                        <tr>
                            <th>Quantity</th>
                            <td><input type="number" min="1" name="fd_extra_qty" class="regular-text" required value="<?php echo esc_attr($edit_item['quantity'] ?? '1'); ?>"></td>
                        </tr>
                        <tr>
                            <th>Image/File</th>
                            <td>
                                <input type="hidden" name="fd_extra_file" id="fd_extra_file" value="<?php echo esc_attr($edit_item['file_id'] ?? ''); ?>">
                                <button type="button" class="button" id="fd_extra_file_button"><?php echo $edit_item && !empty($edit_item['file_id']) ? 'Change File' : 'Select File'; ?></button>
                                <div id="fd_extra_file_preview" style="margin-top:10px;">
                                    <?php if(!empty($edit_item['file_id'])) echo wp_get_attachment_image($edit_item['file_id'], [80,80]); ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php echo $edit_item?'Update Extra':'Add Extra'; ?>">
                        <?php if($edit_item): ?><a href="?page=awesome_food_delivery&tab=extras" class="button">Cancel</a><?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/*--------------------------------------------------------------
# All Extras Layout
--------------------------------------------------------------*/
function fd_all_extras_tab(){
    $extras = get_option('fd_extras', []);

    // Handle deletion
    if(isset($_GET['delete'])){
        $delete_id = intval($_GET['delete']);
        $nonce = $_GET['_wpnonce'] ?? '';
        if(isset($extras[$delete_id]) && wp_verify_nonce($nonce,'fd_delete_extra_'.$delete_id)){
            unset($extras[$delete_id]);
            update_option('fd_extras', $extras);
            echo '<div class="notice notice-success is-dismissible"><p>Extra deleted successfully!</p></div>';
        }
    }
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Image/File</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if($extras): ?>
            <?php foreach($extras as $id=>$item): ?>
                <tr>
                    <td><?php echo esc_html($id); ?></td>
                    <td><?php echo esc_html($item['name']); ?></td>
                    <td><?php echo isset($item['price']) ? esc_html(number_format($item['price'],2)) : '-'; ?></td>
                    <td><?php echo isset($item['quantity']) ? esc_html($item['quantity']) : '-'; ?></td>
                    <td>
                        <?php if(!empty($item['file_id'])): ?>
                            <a href="<?php echo esc_url(wp_get_attachment_url($item['file_id'])); ?>" target="_blank">
                                <img src="<?php echo esc_url(wp_get_attachment_url($item['file_id'])); ?>" style="max-width:60px;max-height:60px;">
                            </a>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td>
                        <a class="button afd-btn-view" href="?page=awesome_food_delivery&tab=extras&sub=view&item=<?php echo esc_attr($id); ?>">View</a>
                        <a class="button afd-btn-edit" href="?page=awesome_food_delivery&tab=extras&sub=add&edit=<?php echo esc_attr($id); ?>">Edit</a>
                        <a class="button afd-btn-delete" href="<?php echo wp_nonce_url(add_query_arg(['tab'=>'extras','sub'=>'all','delete'=>$id], admin_url('admin.php?page=awesome_food_delivery')), 'fd_delete_extra_'.$id); ?>" onclick="return confirm('Delete this extra?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No extras found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/*--------------------------------------------------------------
# View Single Extra Layout
--------------------------------------------------------------*/
function fd_view_extra_tab($item_id){
    $extras = get_option('fd_extras', []);
    if(!isset($extras[$item_id])){
        echo '<div class="notice notice-error"><p>Extra not found.</p></div>';
        return;
    }

    $item = $extras[$item_id];
    ?>
    <h2><?php echo esc_html($item['name']); ?></h2>
    <?php if(!empty($item['file_id'])): ?>
        <?php echo wp_get_attachment_image($item['file_id'], [150,150]); ?><br><br>
        <a href="<?php echo esc_url(wp_get_attachment_url($item['file_id'])); ?>" target="_blank" class="button">Open File</a><br><br>
    <?php endif; ?>
    <strong>Price:</strong> <?php echo isset($item['price']) ? esc_html(number_format($item['price'],2)) : '0'; ?><br>
    <strong>Quantity:</strong> <?php echo isset($item['quantity']) ? esc_html($item['quantity']) : '1'; ?><br><br>
    <strong>ID:</strong> <?php echo esc_html($item_id); ?><br><br>
    <a class="button" href="?page=awesome_food_delivery&tab=extras&sub=all">Back to All Extras</a>
    <?php
}
