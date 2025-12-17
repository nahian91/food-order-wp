<?php
/*-----------------------------------------
# List All Categories (Classic WP Table Style + Bulk Actions + Delete Button)
-----------------------------------------*/
function fd_category_list(){

    $page_slug = 'awesome_food_delivery'; // Correct menu slug

    // Single Delete via URL
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

    // Bulk Delete
    if(isset($_POST['action']) && $_POST['action']=='delete' && !empty($_POST['category']) && current_user_can('manage_options')){
        $bulk_nonce = $_POST['fd_bulk_delete_nonce'] ?? '';
        if(wp_verify_nonce($bulk_nonce,'fd_bulk_delete')){
            foreach($_POST['category'] as $term_id){
                wp_delete_term(intval($term_id),'food_category');
            }
            echo '<div class="notice notice-success is-dismissible"><p>Selected categories deleted.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Security check failed!</p></div>';
        }
    }

    $per_page = 20;
    $current_page = max(1,intval($_GET['paged'] ?? 1));
    $offset = ($current_page-1)*$per_page;

    $terms = get_terms([
        'taxonomy'=>'food_category',
        'hide_empty'=>false,
        'number'=>$per_page,
        'offset'=>$offset
    ]);

    $total = wp_count_terms('food_category',['hide_empty'=>false]);
    $total_pages = ceil($total/$per_page);
    ?>

    <form method="post">
        <?php wp_nonce_field('fd_bulk_delete','fd_bulk_delete_nonce'); ?>

        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" class="button action" value="Apply">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column check-column"><input type="checkbox" /></th>
                    <th scope="col">Image</th>
                    <th scope="col">Name</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if($terms && !is_wp_error($terms)): ?>
                <?php foreach($terms as $term): 
                    $img = get_term_meta($term->term_id,'fd_category_image',true);

                    // Correct URLs for menu page
                    $edit_url   = add_query_arg(['tab'=>'categories','sub'=>'add','edit'=>$term->term_id], admin_url('admin.php?page='.$page_slug));
                    $view_url   = add_query_arg(['tab'=>'categories','sub'=>'view','item'=>$term->term_id], admin_url('admin.php?page='.$page_slug));
                    $delete_url = wp_nonce_url(add_query_arg(['tab'=>'categories','sub'=>'all','delete'=>$term->term_id], admin_url('admin.php?page='.$page_slug)), 'fd_delete_cat_'.$term->term_id);
                ?>
                <tr>
                    <th class="check-column"><input type="checkbox" name="category[]" value="<?php echo esc_attr($term->term_id); ?>"/></th>
                    <td><?php echo $img ? wp_get_attachment_image($img,[60,60]) : '-'; ?></td>
                    <td><?php echo esc_html($term->name); ?></td>
                    <td>
                        <a class="button afd-btn-view" href="<?php echo esc_url($view_url); ?>">View</a>
                        <a class="button afd-btn-edit" href="<?php echo esc_url($edit_url); ?>">Edit</a>
                        <a class="button afd-btn-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this category?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No categories found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                for($i=1;$i<=$total_pages;$i++){
                    $class = $i==$current_page ? 'page-numbers current' : 'page-numbers';
                    $page_url = add_query_arg(['paged'=>$i]);
                    echo '<a class="'.$class.'" href="'.esc_url($page_url).'">'.$i.'</a> ';
                }
                ?>
            </div>
        </div>
    </form>

    <script>
    // Toggle all checkboxes
    document.addEventListener('DOMContentLoaded', function(){
        const topCheck = document.querySelector('.check-column input[type="checkbox"]');
        if(topCheck){
            topCheck.addEventListener('change', function(){
                document.querySelectorAll('tbody .check-column input[type="checkbox"]').forEach(cb=>{
                    cb.checked = topCheck.checked;
                });
            });
        }
    });
    </script>

<?php
}
