<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('FD_Categories_List_Table')){
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

    class FD_Categories_List_Table extends WP_List_Table {

        function __construct(){
            parent::__construct([
                'singular'=>'food_category',
                'plural'=>'food_categories',
                'ajax'=>false
            ]);
        }

        // Columns
        function get_columns(){
            return [
                'cb'    => '<input type="checkbox"/>',
                'image' => 'Image',
                'name'  => 'Category Name',
                'actions' => 'Actions',
                'id'    => 'ID'
            ];
        }

        // Checkbox
        function column_cb($item){
            return sprintf('<input type="checkbox" name="category[]" value="%s"/>', $item->term_id);
        }

        // Image
        function column_image($item){
            $img_id = get_term_meta($item->term_id,'fd_category_image',true);
            return $img_id ? '<img src="'.esc_url(wp_get_attachment_url($img_id)).'" style="max-width:60px;max-height:60px;">' : '-';
        }

        // Name + row actions
        function column_name($item){
            $view_url = add_query_arg([
                'tab'=>'categories',
                'sub'=>'view',
                'item'=>$item->term_id
            ], admin_url('admin.php?page=food_delivery'));

            $edit_url = add_query_arg([
                'tab'=>'categories',
                'sub'=>'add',
                'edit'=>$item->term_id
            ], admin_url('admin.php?page=food_delivery'));

            $delete_url = wp_nonce_url(
                add_query_arg([
                    'tab'=>'categories',
                    'sub'=>'all',
                    'delete'=>$item->term_id
                ], admin_url('admin.php?page=food_delivery')),
                'fd_delete_cat_'.$item->term_id
            );

            $actions = [
                'view' => '<a href="'.esc_url($view_url).'">View</a>',
                'edit' => '<a href="'.esc_url($edit_url).'">Edit</a>',
                'delete' => '<a href="'.esc_url($delete_url).'" onclick="return confirm(\'Delete this category?\');" style="color:#b32d2e;">Delete</a>'
            ];

            return sprintf('%1$s %2$s', esc_html($item->name), $this->row_actions($actions));
        }

        // Actions column with buttons
        function column_actions($item){
            $view_url = add_query_arg([
                'tab'=>'categories',
                'sub'=>'view',
                'item'=>$item->term_id
            ], admin_url('admin.php?page=food_delivery'));

            $edit_url = add_query_arg([
                'tab'=>'categories',
                'sub'=>'add',
                'edit'=>$item->term_id
            ], admin_url('admin.php?page=food_delivery'));

            $delete_url = wp_nonce_url(
                add_query_arg([
                    'tab'=>'categories',
                    'sub'=>'all',
                    'delete'=>$item->term_id
                ], admin_url('admin.php?page=food_delivery')),
                'fd_delete_cat_'.$item->term_id
            );

            return '<a class="button" href="'.esc_url($view_url).'">View</a> '.
                   '<a class="button" href="'.esc_url($edit_url).'">Edit</a> '.
                   '<a class="button" href="'.esc_url($delete_url).'" onclick="return confirm(\'Delete this category?\')">Delete</a>';
        }

        // ID column
        function column_id($item){
            return $item->term_id;
        }

        // Prepare items
        function prepare_items(){
            $per_page = 20;
            $current_page = $this->get_pagenum();

            $terms = get_terms([
                'taxonomy'   => 'food_category',
                'hide_empty' => false,
                'number'     => $per_page,
                'offset'     => ($current_page - 1) * $per_page
            ]);

            $total_items = wp_count_terms('food_category', ['hide_empty'=>false]);

            $this->_column_headers = [$this->get_columns(), [], []];
            $this->items = !is_wp_error($terms) ? $terms : [];

            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ]);
        }
    }
}

/*----------------------
# Categories Tab (Add + All + View)
----------------------*/
function fd_category_tab(){
    wp_enqueue_media(); // For media uploader

    // Sub-tabs
    $sub_tabs = ['add'=>'Add Category','all'=>'All Categories'];
    $active_sub = $_GET['sub'] ?? 'add';

    echo '<h2 class="nav-tab-wrapper">';
    foreach($sub_tabs as $k=>$label){
        echo '<a class="nav-tab'.($active_sub==$k?' nav-tab-active':'').'" href="?page=food_delivery&tab=categories&sub='.$k.'">'.$label.'</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    switch($active_sub){
        case 'add':
            fd_add_category_tab();
            break;

        case 'all':
            // Delete action
            if(isset($_GET['delete']) && current_user_can('manage_options')){
                $term_id = intval($_GET['delete']);
                $nonce = $_GET['_wpnonce'] ?? '';
                if(wp_verify_nonce($nonce,'fd_delete_cat_'.$term_id)){
                    wp_delete_term($term_id,'food_category');
                    echo '<div class="notice notice-success is-dismissible"><p>Category deleted.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Security check failed!</p></div>';
                }
            }

            $table = new FD_Categories_List_Table();
            $table->prepare_items();
            echo '<form method="post">';
            $table->display();
            echo '</form>';
            break;

        case 'view':
            fd_view_category_tab(intval($_GET['item'] ?? 0));
            break;
    }

    echo '</div>';
}

/*----------------------
# Add/Edit Category
----------------------*/
function fd_add_category_tab(){
    if(isset($_POST['fd_cat_nonce']) && wp_verify_nonce($_POST['fd_cat_nonce'],'fd_cat_action')){
        $name = sanitize_text_field($_POST['fd_cat_name']);
        $img_id = intval($_POST['fd_category_image']);
        $edit = intval($_POST['fd_cat_edit']);

        if($edit){
            wp_update_term($edit,'food_category',['name'=>$name]);
            update_term_meta($edit,'fd_category_image',$img_id);
            echo '<div class="notice notice-success is-dismissible"><p>Category updated.</p></div>';
        } else {
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
                    <?php if($edit_id): ?><a href="?page=food_delivery&tab=categories" class="button">Cancel</a><?php endif; ?>
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

/*----------------------
# View Category
----------------------*/
function fd_view_category_tab($item_id){
    $term = get_term($item_id,'food_category');
    if(!$term){
        echo '<div class="notice notice-error"><p>Category not found.</p></div>';
        return;
    }

    $img_id = get_term_meta($item_id,'fd_category_image',true);
    $foods = get_posts([
        'post_type'=>'food_item',
        'tax_query'=>[
            ['taxonomy'=>'food_category','field'=>'term_id','terms'=>$item_id]
        ],
        'numberposts'=>-1
    ]);

    echo '<h2>'.esc_html($term->name).'</h2>';
    echo '<div style="margin-top:20px;">';

    if($img_id){
        echo wp_get_attachment_image($img_id,[150,150]).'<br><br>';
    }

    echo '<strong>ID:</strong> '.esc_html($term->term_id).'<br><br>';
    echo '<strong>Assigned Foods:</strong><br>';

    if($foods){
        echo '<ul>';
        foreach($foods as $f){
            echo '<li>'.esc_html($f->post_title).'</li>';
        }
        echo '</ul>';
    } else {
        echo 'No foods assigned.';
    }

    echo '<br><a class="button" href="?page=food_delivery&tab=categories&sub=all">Back to All Categories</a>';
}
