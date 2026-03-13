<?php
if(!defined('ABSPATH')) exit;

/**
 * View Extra Details - Restaurant Red SaaS UI
 */
function fd_view_extra_tab($afon_item_id){
    $afon_extras = get_option('fd_extras', []);
    
    if(!isset($afon_extras[$afon_item_id])){
        echo '<div class="notice notice-error is-dismissible"><p>Topping resource not found.</p></div>';
        return;
    }

    $afon_item = $afon_extras[$afon_item_id];
    $afon_img_id = !empty($afon_item['file_id']) ? $afon_item['file_id'] : 0;
    $afon_img_url = $afon_img_id ? wp_get_attachment_url($afon_img_id) : '';
    ?>

    <div class="wrap afon-wrap">
        <div class="afon-extra-wrapper">
            
            <div class="afon-conf-card">
                <div class="afon-view-header">
                    <h2>Resource Details: <?php echo esc_html($afon_item['name']); ?></h2>
                    <span class="afon-id-badge">ID: #<?php echo esc_html($afon_item_id); ?></span>
                </div>
                
                <div class="afon-conf-body">
                    <div class="afon-view-grid">
                        
                        <div class="afon-view-asset-col">
                            <?php if($afon_img_url): ?>
                                <div class="afon-view-image-container">
                                    <?php echo wp_get_attachment_image($afon_img_id, 'medium', false, ['class' => 'afon-view-main-img']); ?>
                                </div>
                                <a href="<?php echo esc_url($afon_img_url); ?>" target="_blank" class="afon-btn-action afon-btn-full">
                                    <span class="dashicons dashicons-external"></span> View Full Size
                                </a>
                            <?php else: ?>
                                <div class="afon-view-no-image">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p>No Image Assigned</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="afon-view-data-col">
                            <div class="afon-data-group">
                                <label class="afon-label-muted">Topping Display Name</label>
                                <div class="afon-data-value-large"><?php echo esc_html($afon_item['name']); ?></div>
                            </div>

                            <div class="afon-data-divider"></div>

                            <div class="afon-data-stats-row">
                                <div class="afon-data-group">
                                    <label class="afon-label-muted">Unit Price</label>
                                    <div class="afon-price-display">
                                        <?php echo number_format($afon_item['price'] ?? 0, 2); ?> £
                                    </div>
                                </div>
                                
                                <div class="afon-data-group">
                                    <label class="afon-label-muted">Current Inventory</label>
                                    <?php if(isset($afon_item['quantity']) && $afon_item['quantity'] > 0): ?>
                                        <div class="afon-badge-qty afon-view-badge">
                                            <?php echo esc_html($afon_item['quantity']); ?> In Stock
                                        </div>
                                    <?php else: ?>
                                        <div class="afon-badge-qty afon-badge-out afon-view-badge">
                                            Out of Stock
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="afon-sidebar">
                <div class="afon-summary-box">
                    <h3 class="afon-sidebar-title">Quick Actions</h3>
                    
                    <a href="?page=awesome_food_delivery&tab=extras&sub=add&edit=<?php echo $afon_item_id; ?>" class="afon-btn-save afon-btn-block">
                        Edit Settings
                    </a>
                    
                    <a href="?page=awesome_food_delivery&tab=extras&sub=all" class="afon-btn-action afon-btn-block">
                        &larr; Back to Directory
                    </a>
                    
                    <div class="afon-sidebar-info">
                        <p>Stock and price updates on this resource affect all active restaurant menus instantly.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php
}