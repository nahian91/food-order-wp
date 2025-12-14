<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('FD_Items_List_Table')){
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';

    class FD_Items_List_Table extends WP_List_Table {

        function __construct(){
            parent::__construct([
                'singular'=>'food_item',
                'plural'=>'food_items',
                'ajax'=>false
            ]);
        }

        // Columns
        function get_columns(){
            return [
                'cb'=>'<input type="checkbox"/>',
                'item_image'=>'Image',
                'title'=>'Item Name',
                'category'=>'Category',
                'extras'=>'Extras',
                'price'=>'Price',
                'actions'=>'Actions'
            ];
        }

        // Checkbox
        function column_cb($item){
            return sprintf('<input type="checkbox" name="food_item[]" value="%s"/>',$item->ID);
        }

        // Image
        function column_item_image($item){
            return get_the_post_thumbnail($item->ID,[60,60]) ?: '-';
        }

        // Title
        function column_title($item){
            return '<strong>'.esc_html($item->post_title).'</strong>';
        }

        // Category
        function column_category($item){
            $cats = wp_get_post_terms($item->ID,'food_category'); 
            if(!$cats) return '-';
            $out = '';
            foreach($cats as $c){
                $img_id = get_term_meta($c->term_id,'fd_category_image',true); 
                $img = $img_id ? '<img src="'.esc_url(wp_get_attachment_url($img_id)).'" style="max-width:40px;"> ':'';
                $out .= $img.esc_html($c->name).'<br>';
            }
            return $out;
        }

        // Extras
        function column_extras($item){
            $extras_ids = get_post_meta($item->ID,'fd_item_extras',true); 
            if(!$extras_ids) return '-';
            $all_extras = get_option('fd_extras',[]); 
            $out = '';
            foreach($extras_ids as $id){
                if(isset($all_extras[$id])){
                    $e = $all_extras[$id];
                    $img = !empty($e['file_id']) ? '<img src="'.esc_url(wp_get_attachment_url($e['file_id'])).'" style="max-width:40px;"> ' : '';
                    $out .= $img.esc_html($e['name']).'<br>';
                }
            }
            return $out;
        }

        // Price
        function column_price($item){
            $p = get_post_meta($item->ID,'price',true);
            return $p ? '$'.$p : '-';
        }

        // Actions
        function column_actions($item){
            $view_link = esc_url(add_query_arg([
                'page'=>'food_delivery',
                'tab'=>'items',
                'sub'=>'view',
                'item'=>$item->ID
            ], admin_url('admin.php')));

            $edit_link = esc_url(add_query_arg([
                'page'=>'food_delivery',
                'tab'=>'items',
                'sub'=>'edit',
                'item'=>$item->ID
            ], admin_url('admin.php')));

            $delete_link = esc_url(wp_nonce_url(
                add_query_arg([
                    'action'=>'fd_delete_item',
                    'item'=>$item->ID
                ], admin_url('admin-post.php')),
                'fd_delete_item_'.$item->ID
            ));

            return '<a class="button" href="'.$view_link.'">View</a> '.
                   '<a class="button" href="'.$edit_link.'">Edit</a> '.
                   '<a class="button" href="'.$delete_link.'" onclick="return confirm(\'Are you sure?\')">Delete</a>';
        }

        // Prepare items
        function prepare_items(){
            $columns = $this->get_columns();
            $hidden = [];
            $sortable = [];
            $this->_column_headers = [$columns,$hidden,$sortable];

            $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = wp_count_posts('food_item')->publish;

            $this->items = get_posts([
                'post_type'=>'food_item',
                'numberposts'=>$per_page,
                'offset'=>($current_page-1)*$per_page
            ]);

            $this->set_pagination_args([
                'total_items'=>$total_items,
                'per_page'=>$per_page
            ]);
        }
    }
}

/*--------------------------------------------------------------
# Handle Delete
--------------------------------------------------------------*/
add_action('admin_post_fd_delete_item', function(){
    $item_id = intval($_GET['item'] ?? 0);
    $nonce   = $_GET['_wpnonce'] ?? '';

    if(!$item_id || !wp_verify_nonce($nonce, 'fd_delete_item_'.$item_id)){
        wp_die('Security check failed!');
    }

    wp_trash_post($item_id);

    wp_redirect(admin_url('admin.php?page=food_delivery&tab=items&sub=all'));
    exit;
});

/*--------------------------------------------------------------
# Display Items Tab
--------------------------------------------------------------*/
function fd_items_tab(){
    $sub_tabs = ['add'=>'Add Item','all'=>'All Items'];
    $active_sub = $_GET['sub'] ?? 'add';

    echo '<h2 class="nav-tab-wrapper">';
    foreach($sub_tabs as $k=>$label){
        echo '<a class="nav-tab'.($active_sub==$k?' nav-tab-active':'').'" href="?page=food_delivery&tab=items&sub='.$k.'">'.$label.'</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    switch($active_sub){
        case 'add':
            fd_add_edit_item_tab();
            break;

        case 'edit':
            fd_add_edit_item_tab(intval($_GET['item'] ?? 0));
            break;

        case 'view':
            fd_view_item_tab(intval($_GET['item'] ?? 0));
            break;

        case 'all':
            $table = new FD_Items_List_Table();
            $table->prepare_items();
            echo '<form method="post">';
            $table->display();
            echo '</form>';
            break;
    }

    echo '</div>';
}

/*--------------------------------------------------------------
# Add/Edit Item Sub-tab
--------------------------------------------------------------*/
function fd_add_edit_item_tab($edit_item_id = 0){
    $item = $edit_item_id ? get_post($edit_item_id) : null;

    if($_POST && isset($_POST['fd_add_item_nonce']) && wp_verify_nonce($_POST['fd_add_item_nonce'],'fd_add_item')){
        $title = sanitize_text_field($_POST['fd_item_name']);
        $desc = sanitize_textarea_field($_POST['fd_item_desc']);
        $price = floatval($_POST['fd_item_price']);
        $cat = intval($_POST['fd_item_cat']);
        $extras_selected = array_map('intval',$_POST['fd_item_extras'] ?? []);

        if($item){
            wp_update_post([
                'ID' => $edit_item_id,
                'post_title' => $title,
                'post_content' => $desc,
            ]);
        } else {
            $edit_item_id = wp_insert_post([
                'post_type'=>'food_item',
                'post_title'=>$title,
                'post_content'=>$desc,
                'post_status'=>'publish'
            ]);
        }

        if($edit_item_id){
            update_post_meta($edit_item_id,'price',$price);
            wp_set_post_terms($edit_item_id, [$cat], 'food_category');
            update_post_meta($edit_item_id,'fd_item_extras',$extras_selected);

            if(!empty($_FILES['fd_item_image']['name'])){
                require_once(ABSPATH.'wp-admin/includes/file.php');
                require_once(ABSPATH.'wp-admin/includes/media.php');
                require_once(ABSPATH.'wp-admin/includes/image.php');
                $att_id = media_handle_upload('fd_item_image',$edit_item_id);
                if(!is_wp_error($att_id)) set_post_thumbnail($edit_item_id,$att_id);
            }

            $msg = $item ? 'Item updated successfully!' : 'Item added successfully!';
            echo '<div class="notice notice-success is-dismissible"><p>'.$msg.'</p></div>';
        }
    }

    $title_val = $item ? $item->post_title : '';
    $desc_val = $item ? $item->post_content : '';
    $price_val = $item ? get_post_meta($edit_item_id,'price',true) : '';
    $selected_cat = $item ? wp_get_post_terms($edit_item_id,'food_category', ['fields'=>'ids'])[0] ?? 0 : 0;
    $selected_extras = $item ? get_post_meta($edit_item_id,'fd_item_extras',true) : [];

    $categories = get_terms(['taxonomy'=>'food_category','hide_empty'=>false]);
    $extras = get_option('fd_extras',[]);

    ?>
    <div class="metabox-holder columns-2">
        <div class="postbox"><h2 class="hndle"><span><?php echo $item ? 'Edit Item' : 'Add Item'; ?></span></h2><div class="inside">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('fd_add_item','fd_add_item_nonce'); ?>
            <table class="form-table">
                <tr><th>Name</th><td><input type="text" name="fd_item_name" class="regular-text" required value="<?php echo esc_attr($title_val); ?>"></td></tr>
                <tr><th>Description</th><td><textarea name="fd_item_desc" class="large-text" rows="4"><?php echo esc_textarea($desc_val); ?></textarea></td></tr>
                <tr><th>Price</th><td><input type="number" step="0.01" name="fd_item_price" class="small-text" required value="<?php echo esc_attr($price_val); ?>"></td></tr>
                <tr><th>Category</th>
                    <td>
                        <select name="fd_item_cat">
                            <?php foreach($categories as $c) echo "<option value='{$c->term_id}' ".selected($selected_cat,$c->term_id,false).">{$c->name}</option>"; ?>
                        </select>
                    </td>
                </tr>
                <tr><th>Extras</th>
                    <td>
                        <?php foreach($extras as $k=>$e){
                            $img = !empty($e['file_id']) ? '<img src="'.wp_get_attachment_url($e['file_id']).'" style="max-width:50px;"> ' : '';
                            $checked = in_array($k,$selected_extras) ? 'checked' : '';
                            echo '<label>'.$img.'<input type="checkbox" name="fd_item_extras[]" value="'.$k.'" '.$checked.'> '.esc_html($e['name']).'</label><br>';
                        } ?>
                    </td>
                </tr>
                <tr><th>Item Image</th>
                    <td>
                        <input type="file" name="fd_item_image" accept="image/*"><br>
                        <?php if($item && has_post_thumbnail($edit_item_id)) echo get_the_post_thumbnail($edit_item_id,[100,100]); ?>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="<?php echo $item ? 'Update Item' : 'Add Item'; ?>"></p>
        </form>
        </div></div>
    </div>
    <?php
}

/*--------------------------------------------------------------
# View Item Sub-tab
--------------------------------------------------------------*/
function fd_view_item_tab($item_id){
    $item = get_post($item_id);
    if(!$item){
        echo '<div class="notice notice-error"><p>Item not found.</p></div>';
        return;
    }

    $price = get_post_meta($item_id,'price',true);
    $categories = wp_get_post_terms($item_id,'food_category');
    $extras_ids = get_post_meta($item_id,'fd_item_extras',true);
    $all_extras = get_option('fd_extras',[]);

    echo '<h2>'.esc_html($item->post_title).'</h2>';
    echo '<div style="margin-top:20px;">';

    if(has_post_thumbnail($item_id)){
        echo get_the_post_thumbnail($item_id,[150,150]).'<br><br>';
    }

    echo '<strong>Description:</strong><br>'.nl2br(esc_html($item->post_content)).'<br><br>';
    echo '<strong>Price:</strong> $'.esc_html($price).'<br><br>';

    echo '<strong>Category:</strong> ';
    if($categories){
        foreach($categories as $c){
            $img_id = get_term_meta($c->term_id,'fd_category_image',true);
            $img = $img_id ? '<img src="'.esc_url(wp_get_attachment_url($img_id)).'" style="max-width:40px;"> ' : '';
            echo $img.esc_html($c->name).'<br>';
        }
    } else { echo '-'; }
    echo '<br>';

    echo '<strong>Extras:</strong><br>';
    if($extras_ids){
        foreach($extras_ids as $id){
            if(isset($all_extras[$id])){
                $e = $all_extras[$id];
                $img = !empty($e['file_id']) ? '<img src="'.esc_url(wp_get_attachment_url($e['file_id'])).'" style="max-width:40px;"> ' : '';
                echo $img.esc_html($e['name']).'<br>';
            }
        }
    } else { echo '-'; }

    echo '<br><a class="button" href="?page=food_delivery&tab=items&sub=all">Back to All Items</a>';
}
