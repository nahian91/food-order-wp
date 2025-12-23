<?php
/*--------------------------------------------------------------
# View Category (Enhanced "SaaS Modern" UI)
--------------------------------------------------------------*/
function fd_category_view($id) {
    $id = intval($id);
    $taxonomy = 'food_category'; 
    $term = get_term($id, $taxonomy);

    if (!$term || is_wp_error($term)) {
        echo '<div class="notice notice-error"><p>Category not found.</p></div>';
        return;
    }

    $img_id = get_term_meta($id, 'fd_category_image', true);
    
    // Fetch items with price meta
    $foods = get_posts([
        'post_type'   => 'food_item',
        'tax_query'   => [['taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $id]],
        'numberposts' => -1
    ]);

    $back_url = admin_url('admin.php?page=awesome_food_delivery&tab=categories&sub=all');
    ?>

    <style>
        :root { --primary-red: #d63638; --bg-gray: #f6f7f7; }
        
        .fd-view-header { background: #fff; border: 1px solid #ccd0d4; border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 25px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .fd-view-header img { width: 100px; height: 100px; border-radius: 10px; object-fit: cover; border: 1px solid #eee; }
        .fd-view-header .no-img { width: 100px; height: 100px; background: var(--bg-gray); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ccd0d4; border: 1px solid #eee; }
        
        .fd-view-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #1d2327; }
        .fd-view-title p { margin: 5px 0 0; color: #646970; font-size: 13px; }

        .fd-table-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .fd-table-header { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; background: #fff; display: flex; justify-content: space-between; align-items: center; }
        
        .fd-grid-table { width: 100%; border-collapse: collapse; }
        .fd-grid-table th { background: #fafafa; padding: 12px 20px; text-align: left; font-size: 11px; text-transform: uppercase; color: #50575e; border-bottom: 2px solid #f0f0f1; }
        .fd-grid-table td { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
        
        .fd-item-cell { display: flex; align-items: center; gap: 12px; }
        .fd-item-cell img { width: 42px; height: 42px; border-radius: 6px; object-fit: cover; border: 1px solid #eee; }
        .fd-item-name { font-weight: 600; color: #1d2327; font-size: 14px; }
        
        /* Badges */
        .fd-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-publish { background: #e7f5ec; color: #008a20; }
        .badge-draft { background: #f0f0f1; color: #50575e; }
        .fd-price-tag { color: var(--primary-red); font-weight: 700; font-size: 14px; }
        
        .fd-btn-back { display: inline-flex; align-items: center; gap: 5px; text-decoration: none; color: #646970; font-weight: 600; margin-bottom: 15px; transition: 0.2s; }
        .fd-btn-back:hover { color: var(--primary-red); }
    </style>

    <div class="wrap" style="margin-top:20px;">
        <a href="<?php echo esc_url($back_url); ?>" class="fd-btn-back">
            <span class="dashicons dashicons-arrow-left-alt2" style="font-size:16px; width:16px; height:16px;"></span> Back to All Categories
        </a>

        <div class="fd-view-header">
            <?php if($img_id): ?>
                <?php echo wp_get_attachment_image($img_id, 'thumbnail'); ?>
            <?php else: ?>
                <div class="no-img"><span class="dashicons dashicons-format-image" style="font-size:40px; width:40px; height:40px;"></span></div>
            <?php endif; ?>
            
            <div class="fd-view-title">
                <h1><?php echo esc_html($term->name); ?></h1>
                <p>
                    <span class="dashicons dashicons-tag" style="font-size:14px; vertical-align:text-bottom;"></span> 
                    Slug: <strong><?php echo esc_html($term->slug); ?></strong> 
                    &nbsp; | &nbsp; 
                    <span class="dashicons dashicons-food" style="font-size:14px; vertical-align:text-bottom;"></span> 
                    Total Items: <strong><?php echo count($foods); ?></strong>
                </p>
            </div>
        </div>

        <div class="fd-table-card">
            <div class="fd-table-header">
                <h3 style="margin:0; font-weight:700;">Assigned Food Items</h3>
                <span class="fd-badge" style="background: #f0f6fb; color: #2271b1;"><?php echo count($foods); ?> Products</span>
            </div>

            <table class="fd-grid-table">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($foods): ?>
                        <?php foreach($foods as $f): 
                            $price = get_post_meta($f->ID, 'food_price', true); 
                            $status_class = ($f->post_status === 'publish') ? 'badge-publish' : 'badge-draft';
                        ?>
                            <tr>
                                <td>
                                    <div class="fd-item-cell">
                                        <?php if(has_post_thumbnail($f->ID)): ?>
                                            <?php echo get_the_post_thumbnail($f->ID, [42, 42]); ?>
                                        <?php else: ?>
                                            <div style="width:42px; height:42px; background:#f0f0f1; border-radius:6px;"></div>
                                        <?php endif; ?>
                                        <div class="fd-item-name"><?php echo esc_html($f->post_title); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fd-price-tag"><?php echo $price ? esc_html($price) : '--'; ?></span>
                                </td>
                                <td>
                                    <span class="fd-badge <?php echo $status_class; ?>">
                                        <?php echo esc_html($f->post_status); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="<?php echo get_edit_post_link($f->ID); ?>" class="button button-secondary">
                                        <span class="dashicons dashicons-edit" style="font-size:16px; padding-top:4px;"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 50px; color: #646970;">
                                <span class="dashicons dashicons-info" style="font-size:30px; width:30px; height:30px; margin-bottom:10px; display:block; margin: 0 auto 10px;"></span>
                                No items found in this category.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}