<?php
if(!defined('ABSPATH')) exit;

function fd_items_list(){

    $per_page = 20;
    $paged = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($paged - 1) * $per_page;

    $total_items = wp_count_posts('food_item')->publish;

    $items = get_posts([
        'post_type'   => 'food_item',
        'numberposts' => $per_page,
        'offset'      => $offset
    ]);

    echo '<form method="post" action="'.admin_url('admin-post.php').'">';
    wp_nonce_field('fd_bulk_delete','fd_bulk_delete_nonce');
    echo '<input type="hidden" name="action" value="fd_bulk_delete">';

    echo '<table class="widefat striped">';
    echo '<thead>
            <tr>
                <th scope="col" class="check-column"><input type="checkbox" id="cb-select-all"></th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Extras</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
          </thead><tbody>';

    if(!$items){
        echo '<tr><td colspan="7">No items found</td></tr>';
    }

    foreach($items as $item){

        $price = get_post_meta($item->ID,'price',true);
        $cats  = wp_get_post_terms($item->ID,'food_category');
        $extras_ids = get_post_meta($item->ID,'fd_item_extras',true);
        $extras_all = get_option('fd_extras',[]);

        echo '<tr>';
        echo '<th class="check-column"><input type="checkbox" name="items[]" value="'.$item->ID.'"></th>';
        echo '<td>'.(has_post_thumbnail($item->ID) ? get_the_post_thumbnail($item->ID,[60,60]) : '-').'</td>';
        echo '<td><strong>'.$item->post_title.'</strong></td>';

        echo '<td>';
        if($cats){
            foreach($cats as $c){
                echo esc_html($c->name).'<br>';
            }
        } else echo '-';
        echo '</td>';

        echo '<td>';
        if($extras_ids){
            foreach($extras_ids as $id){
                if(isset($extras_all[$id])){
                    echo esc_html($extras_all[$id]['name']).'<br>';
                }
            }
        } else echo '-';
        echo '</td>';

        echo '<td>$'.$price.'</td>';

        $view = admin_url('admin.php?page=awesome_food_delivery&tab=items&sub=view&item='.$item->ID);
        $edit = admin_url('admin.php?page=awesome_food_delivery&tab=items&sub=edit&item='.$item->ID);
        $del  = wp_nonce_url(
            admin_url('admin-post.php?action=fd_delete_item&item='.$item->ID),
            'fd_delete_item_'.$item->ID
        );

        echo '<td>
                <a class="button afd-btn-view" href="'.$view.'">View</a>
                <a class="button afd-btn-edit" href="'.$edit.'">Edit</a>
                <a class="button afd-btn-delete" onclick="return confirm(\'Delete?\')" href="'.$del.'">Delete</a>
              </td>';

        echo '</tr>';
    }

    echo '</tbody></table>';

    // Bulk Delete Button
    echo '<p><input type="submit" class="button button-primary" value="Delete Selected"></p>';
    echo '</form>';

    // Pagination
    $total_pages = ceil($total_items / $per_page);
    if($total_pages > 1){
        $current = $paged;
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links([
            'base' => add_query_arg('paged','%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $total_pages,
            'current' => $current
        ]);
        echo '</div></div>';
    }

    // JS for Select All Checkbox
    ?>
    <script>
    jQuery(document).ready(function($){
        $('#cb-select-all').on('click', function(){
            $('input[name="items[]"]').prop('checked', this.checked);
        });
    });
    </script>
<?php
}

/*--------------------------------------------------------------
# Handle Bulk Delete
--------------------------------------------------------------*/
add_action('admin_post_fd_bulk_delete', function(){
    if(!isset($_POST['fd_bulk_delete_nonce']) || !wp_verify_nonce($_POST['fd_bulk_delete_nonce'],'fd_bulk_delete')){
        wp_die('Security check failed!');
    }

    $items = array_map('intval', $_POST['items'] ?? []);
    if($items){
        foreach($items as $id){
            wp_trash_post($id);
        }
    }

    wp_redirect(admin_url('admin.php?page=awesome_food_delivery&tab=items&sub=all'));
    exit;
});
