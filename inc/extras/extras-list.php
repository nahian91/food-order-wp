<?php
if(!defined('ABSPATH')) exit;

/**
 * List All Extras - Restaurant Red SaaS UI
 */
function fd_all_extras_tab(){
    $afon_extras = get_option('fd_extras', []);

    // Handle deletion logic
    if(isset($_GET['delete'])){
        $afon_delete_id = intval($_GET['delete']);
        $afon_nonce = $_GET['_wpnonce'] ?? '';
        if(isset($afon_extras[$afon_delete_id]) && wp_verify_nonce($afon_nonce, 'fd_delete_extra_' . $afon_delete_id)){
            unset($afon_extras[$afon_delete_id]);
            update_option('fd_extras', $afon_extras);
            echo '<div class="updated notice is-dismissible"><p>Extra topping removed successfully.</p></div>';
        }
    }
    ?>

    <div class="wrap afon-wrap">
        <table id="afon-extras-directory-table" class="widefat afon-extras-table">
            <thead>
                <tr>
                    <th width="70">Photo</th>
                    <th>Topping Name</th>
                    <th>Price</th>
                    <th>Stock Status</th>
                    <th width="280" class="afon-text-right">Management</th>
                </tr>
            </thead>
            <tbody>
            <?php if($afon_extras): ?>
                <?php foreach($afon_extras as $afon_id => $afon_item): 
                    $afon_img_url = !empty($afon_item['file_id']) ? wp_get_attachment_thumb_url($afon_item['file_id']) : '';
                    
                    // Route Links
                    $afon_view_url   = "?page=awesome_food_delivery&tab=extras&sub=view&item=$afon_id";
                    $afon_edit_url   = "?page=awesome_food_delivery&tab=extras&sub=add&edit=$afon_id";
                    $afon_delete_url = wp_nonce_url(add_query_arg(['tab'=>'extras','sub'=>'all','delete'=>$afon_id], admin_url('admin.php?page=awesome_food_delivery')), 'fd_delete_extra_'.$afon_id);
                ?>
                    <tr>
                        <td>
                            <?php if($afon_img_url): ?>
                                <img src="<?php echo esc_url($afon_img_url); ?>" class="afon-extra-img">
                            <?php else: ?>
                                <div class="afon-extra-no-img"><span class="dashicons dashicons-plus-alt2"></span></div>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <strong class="afon-user-name"><?php echo esc_html($afon_item['name']); ?></strong><br>
                            <code class="afon-id-code">#ID: <?php echo esc_html($afon_id); ?></code>
                        </td>

                        <td>
                            <span class="afon-price-text">
                                <?php echo isset($afon_item['price']) ? number_format($afon_item['price'], 2, '.', '') . ' £' : '0.00 £'; ?>
                            </span>
                        </td>

                        <td>
                            <?php if(isset($afon_item['quantity']) && $afon_item['quantity'] > 0): ?>
                                <span class="afon-badge-qty"><?php echo esc_html($afon_item['quantity']); ?> in stock</span>
                            <?php else: ?>
                                <span class="afon-badge-qty afon-badge-out">Out of stock</span>
                            <?php endif; ?>
                        </td>

                        <td class="afon-text-right">
                            <a class="afon-btn-action" href="<?php echo esc_url($afon_view_url); ?>">
                                <span class="dashicons dashicons-visibility"></span> View
                            </a>

                            <a class="afon-btn-action" href="<?php echo esc_url($afon_edit_url); ?>">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </a>

                            <a class="afon-btn-action afon-btn-danger" href="<?php echo esc_url($afon_delete_url); ?>" 
                               onclick="return confirm('Remove <?php echo esc_js($afon_item['name']); ?>?')">
                                <span class="dashicons dashicons-trash"></span> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="afon-empty-state">
                        No extras found. Click "Add New" to create your first topping.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}