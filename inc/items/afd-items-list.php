<?php
if (!defined('ABSPATH')) exit;

function fd_items_list() {
    $items = get_posts([
        'post_type'   => 'food_item',
        'numberposts' => -1, 
        'orderby'     => 'ID',
        'order'       => 'DESC',
    ]);
    ?>

    <style>
        :root { 
            --res-primary: #d63638; 
            --res-dark: #1d2327;    
            --res-border: #ccd0d4; 
        }

        /* Container & Table Style */
        .afd-dashboard { margin-top: 20px; }
        #fd-items-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.05); }
        #fd-items-table thead th { background: #fafafa; padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; }
        #fd-items-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
        
        /* Badges */
        .fd-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; margin: 2px; border: 1px solid transparent; }
        .fd-cat-badge { background: #fff9f9; color: var(--res-primary); border-color: #f5c2c2; }
        .fd-extra-badge { background: #f0f0f1; color: #3c434a; border-color: #dcdcde; }
        
        /* SaaS Management Buttons */
        .fd-btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #dcdcde; background: #fff; color: #2c3338; white-space: nowrap; }
        .fd-btn:hover { border-color: var(--res-primary); color: var(--res-primary); background: #fff9f9; }
        
        .fd-btn-danger:hover { color: #fff; border-color: var(--res-primary); background: var(--res-primary); }
        .fd-btn-danger .dashicons { transition: 0.2s; }
        .fd-btn-danger:hover .dashicons { color: #fff !important; }

        .fd-item-img { border-radius: 8px; border: 1px solid #eee; object-fit: cover; }
        .fd-no-img { width: 50px; height: 50px; border-radius: 8px; background: #f6f7f7; display: flex; align-items: center; justify-content: center; color: #c3c4c7; border: 1px solid #eee; }

        /* DataTable Search Style */
        .dataTables_wrapper .dataTables_filter { float: none; text-align: left; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #c3c4c7; border-radius: 4px; padding: 8px 12px; margin-bottom: 20px; width: 300px; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--res-primary); box-shadow: 0 0 0 1px var(--res-primary); outline: none; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--res-primary) !important; color: #fff !important; border: 1px solid var(--res-primary) !important; border-radius: 4px; }
    </style>

    <div class="wrap afd-dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 style="margin:0; font-weight: 700;"><?php esc_html_e('Food Menu Items', 'text-domain'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=awesome_food_delivery&tab=items&sub=add'); ?>" class="button button-primary" style="background:var(--res-primary); border-color:var(--res-primary); font-weight:600; padding: 0 20px;">+ Add New Menu Item</a>
        </div>

        <table id="fd-items-table" class="widefat">
            <thead>
                <tr>
                    <th width="75">Photo</th>
                    <th>Item Details</th>
                    <th>Category</th>
                    <th>Available Extras</th>
                    <th width="100">Price</th>
                    <th width="280" style="text-align: right;">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php if($items): 
                    $extras_all = get_option('fd_extras', []);
                    foreach($items as $item):
                        $price      = get_post_meta($item->ID, 'price', true);
                        $cats       = wp_get_post_terms($item->ID, 'food_category');
                        $extras_ids = get_post_meta($item->ID, 'fd_item_extras', true);
                        
                        // Internal Admin URLs
                        $base_url   = admin_url('admin.php?page=awesome_food_delivery&tab=items');
                        $view_url   = $base_url . '&sub=view&item=' . $item->ID;
                        $edit_url   = $base_url . '&sub=edit&item=' . $item->ID;
                        $del_url    = wp_nonce_url(admin_url('admin-post.php?action=fd_delete_item&item='.$item->ID), 'fd_delete_item_'.$item->ID);
                ?>
                    <tr>
                        <td>
                            <?php if (has_post_thumbnail($item->ID)): ?>
                                <?php echo get_the_post_thumbnail($item->ID, [50, 50], ['class' => 'fd-item-img']); ?>
                            <?php else: ?>
                                <div class="fd-no-img"><span class="dashicons dashicons-format-image"></span></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="font-size:15px; color: var(--res-dark);"><?php echo esc_html($item->post_title); ?></strong><br>
                            <code style="background:none; padding:0; color: #a7aaad; font-size: 11px;">#<?php echo $item->ID; ?></code>
                        </td>
                        <td>
                            <?php if($cats): foreach($cats as $c): ?>
                                <span class="fd-badge fd-cat-badge"><?php echo esc_html($c->name); ?></span>
                            <?php endforeach; else: echo '<span style="color:#a7aaad;">—</span>'; endif; ?>
                        </td>
                        <td>
                            <?php 
                            if($extras_ids && is_array($extras_ids)): 
                                foreach($extras_ids as $id): 
                                    if(isset($extras_all[$id])): ?>
                                        <span class="fd-badge fd-extra-badge"><?php echo esc_html($extras_all[$id]['name']); ?></span>
                            <?php   endif; 
                                endforeach; 
                            else: echo '<span style="color:#a7aaad; font-size: 12px;">No extras</span>'; endif; ?>
                        </td>
                        <td>
                            <strong style="font-size:16px; color: var(--res-primary);">
                                <?php echo number_format(floatval($price), 2, '.', ''); ?> €
                            </strong>
                        </td>
                        <td style="text-align: right;">
                            <a class="fd-btn" href="<?php echo $view_url; ?>" title="View Details">
                                <span class="dashicons dashicons-visibility" style="font-size:16px; margin-top:3px;"></span> View
                            </a>
                            <a class="fd-btn" href="<?php echo $edit_url; ?>">
                                <span class="dashicons dashicons-edit" style="font-size:16px; margin-top:3px;"></span> Edit
                            </a>
                            <a class="fd-btn fd-btn-danger" onclick="return confirm('Are you sure you want to delete this menu item?')" href="<?php echo $del_url; ?>">
                                <span class="dashicons dashicons-trash" style="font-size:16px; margin-top:3px; color: var(--res-primary);"></span> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="padding:40px; text-align:center;">No food items found. Start by adding one!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($){
        $('#fd-items-table').DataTable({
            "pageLength": 20,
            "language": {
                "search": "",
                "searchPlaceholder": "Search menu items (Name, ID)...",
                "paginate": { "next": "→", "previous": "←" }
            },
            "dom": '<"top"f>rt<"bottom"ip><"clear">',
            "columnDefs": [
                { "orderable": false, "targets": [0, 5] }
            ]
        });
    });
    </script>
<?php
}