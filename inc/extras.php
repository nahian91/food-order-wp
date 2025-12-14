<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('FD_Extras_List_Table')){
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';

    class FD_Extras_List_Table extends WP_List_Table {

        function __construct(){ 
            parent::__construct(['singular'=>'food_extra','plural'=>'food_extras','ajax'=>false]); 
        }

        function get_columns(){ 
            return [
                'cb'=>'<input type="checkbox"/>',
                'image'=>'Image/File',
                'name'=>'Extra Name',
                'actions'=>'Actions',
                'id'=>'ID'
            ]; 
        }

        function column_cb($item){ 
            return sprintf('<input type="checkbox" name="extra[]" value="%s"/>',$item['id']); 
        }

        function column_image($item){
            if(!empty($item['file_id'])){
                $url = wp_get_attachment_url($item['file_id']);
                return '<a href="'.esc_url($url).'" target="_blank"><img src="'.esc_url($url).'" style="max-width:60px;max-height:60px;"/></a>';
            }
            return '-';
        }

        function column_name($item){ 
            return esc_html($item['name']);
        }

        function column_actions($item){
            $view_url = add_query_arg(['tab'=>'extras','sub'=>'view','item'=>$item['id']], admin_url('admin.php?page=food_delivery'));
            $edit_url = add_query_arg(['tab'=>'extras','sub'=>'add','edit'=>$item['id']], admin_url('admin.php?page=food_delivery'));
            $delete_url = wp_nonce_url(add_query_arg(['tab'=>'extras','sub'=>'all','delete'=>$item['id']], admin_url('admin.php?page=food_delivery')), 'fd_delete_extra_'.$item['id']);

            return '<a class="button" href="'.esc_url($view_url).'">View</a> '.
                   '<a class="button" href="'.esc_url($edit_url).'">Edit</a> '.
                   '<a class="button" href="'.esc_url($delete_url).'" onclick="return confirm(\'Delete this extra?\')">Delete</a>';
        }

        function column_id($item){ 
            return $item['id']; 
        }

        function prepare_items(){
            $per_page = 20;
            $current_page = $this->get_pagenum();
            $extras = get_option('fd_extras', []);
            $total_items = count($extras);

            // Pagination
            $extras = array_slice($extras, ($current_page-1)*$per_page, $per_page, true);
            $this->items = array_map(function($e,$i){ $e['id']=$i; return $e; }, $extras, array_keys($extras));
            $this->_column_headers = [$this->get_columns(), [], []];
            $this->set_pagination_args([
                'total_items'=>$total_items,
                'per_page'=>$per_page,
                'total_pages'=>ceil($total_items/$per_page)
            ]);
        }
    }
}

/*----------------------
# Extras Tab
----------------------*/
function fd_extras_tab(){
    $sub_tabs = ['add'=>'Add Extra','all'=>'All Extras'];
    $active_sub = $_GET['sub'] ?? 'add';

    echo '<h2 class="nav-tab-wrapper">';
    foreach($sub_tabs as $k=>$label){
        echo '<a class="nav-tab'.($active_sub==$k?' nav-tab-active':'').'" href="?page=food_delivery&tab=extras&sub='.$k.'">'.$label.'</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    switch($active_sub){
        case 'add':
            fd_add_extra_tab();
            break;

        case 'all':
            $extras = get_option('fd_extras', []);

            // Delete
            if(isset($_GET['delete'])){
                $delete_id = intval($_GET['delete']);
                $nonce = $_GET['_wpnonce'] ?? '';
                if(isset($extras[$delete_id]) && wp_verify_nonce($nonce,'fd_delete_extra_'.$delete_id)){
                    unset($extras[$delete_id]);
                    update_option('fd_extras', $extras);
                    echo '<div class="notice notice-success is-dismissible"><p>Extra deleted successfully!</p></div>';
                }
            }

            $table = new FD_Extras_List_Table();
            $table->prepare_items();
            echo '<form method="post">';
            $table->display();
            echo '</form>';
            break;

        case 'view':
            fd_view_extra_tab(intval($_GET['item'] ?? 0));
            break;
    }

    echo '</div>';
}

/*----------------------
# Add/Edit Extra
----------------------*/
function fd_add_extra_tab(){
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $extras = get_option('fd_extras', []);
    $edit_item = $edit_id !== null && isset($extras[$edit_id]) ? $extras[$edit_id] : null;

    if($_POST && isset($_POST['fd_add_extra_nonce']) && wp_verify_nonce($_POST['fd_add_extra_nonce'],'fd_add_extra')){
        $extra_name = sanitize_text_field($_POST['fd_extra_name']);
        $attachment_id = 0;

        if(!empty($_FILES['fd_extra_file']['name'])){
            require_once(ABSPATH.'wp-admin/includes/file.php');
            require_once(ABSPATH.'wp-admin/includes/media.php');
            require_once(ABSPATH.'wp-admin/includes/image.php');
            $attachment_id = media_handle_upload('fd_extra_file', 0);
            if(is_wp_error($attachment_id)) $attachment_id = $edit_item['file_id'] ?? 0;
        }

        if($edit_item){
            $extras[$edit_id] = ['name'=>$extra_name,'file_id'=>$attachment_id ?: $edit_item['file_id']];
            echo '<div class="notice notice-success is-dismissible"><p>Extra updated successfully!</p></div>';
        } else {
            $extras[] = ['name'=>$extra_name,'file_id'=>$attachment_id];
            echo '<div class="notice notice-success is-dismissible"><p>Extra added successfully!</p></div>';
        }

        update_option('fd_extras', $extras);
    }

    ?>
    <div class="metabox-holder columns-2">
        <div class="postbox"><h2 class="hndle"><span><?php echo $edit_item?'Edit Extra':'Add New Extra'; ?></span></h2>
            <div class="inside">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('fd_add_extra','fd_add_extra_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Extra Name</th>
                            <td><input type="text" name="fd_extra_name" class="regular-text" required value="<?php echo esc_attr($edit_item['name'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th>Image/File</th>
                            <td><input type="file" name="fd_extra_file" accept="image/*,.pdf,.doc,.docx">
                                <?php if(!empty($edit_item['file_id'])) echo wp_get_attachment_image($edit_item['file_id'], [80,80]); ?>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php echo $edit_item?'Update Extra':'Add Extra'; ?>">
                        <?php if($edit_item): ?><a href="?page=food_delivery&tab=extras" class="button">Cancel</a><?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/*----------------------
# View Extra
----------------------*/
function fd_view_extra_tab($item_id){
    $extras = get_option('fd_extras', []);
    if(!isset($extras[$item_id])){
        echo '<div class="notice notice-error"><p>Extra not found.</p></div>';
        return;
    }

    $item = $extras[$item_id];
    echo '<h2>'.esc_html($item['name']).'</h2>';
    if(!empty($item['file_id'])){
        echo wp_get_attachment_image($item['file_id'], [150,150]).'<br><br>';
        $url = wp_get_attachment_url($item['file_id']);
        echo '<a href="'.esc_url($url).'" target="_blank" class="button">Open File</a><br><br>';
    }
    echo '<strong>ID:</strong> '.esc_html($item_id).'<br><br>';
    echo '<a class="button" href="?page=food_delivery&tab=extras&sub=all">Back to All Extras</a>';
}
