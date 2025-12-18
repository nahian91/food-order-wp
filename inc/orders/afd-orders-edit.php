<?php
if (!defined('ABSPATH')) exit;

/**
 * Order Editing Page - Restaurant Red SaaS UI
 */
$afon_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$afon_order_id) {
    echo '<div class="notice notice-error"><p>Invalid Order Resource.</p></div>';
    return;
}

// 1. UPDATE LOGIC
if (isset($_POST['afon_update_order'])) {
    check_admin_referer('afon_update_order_action', 'afon_update_order_nonce');

    update_post_meta($afon_order_id, 'customer_name', sanitize_text_field($_POST['afon_customer_name']));
    update_post_meta($afon_order_id, 'customer_phone', sanitize_text_field($_POST['afon_customer_phone']));
    update_post_meta($afon_order_id, 'customer_address', sanitize_textarea_field($_POST['afon_customer_address']));
    update_post_meta($afon_order_id, 'notes', sanitize_textarea_field($_POST['afon_notes']));
    update_post_meta($afon_order_id, 'status', sanitize_text_field($_POST['afon_status']));

    $afon_updated_items = [];
    if (isset($_POST['afon_items_name']) && is_array($_POST['afon_items_name'])) {
        foreach ($_POST['afon_items_name'] as $afon_index => $afon_name) {
            if (!empty($afon_name)) {
                $afon_updated_items[] = [
                    'name'  => sanitize_text_field($afon_name),
                    'qty'   => intval($_POST['afon_items_qty'][$afon_index]),
                    'price' => floatval($_POST['afon_items_price'][$afon_index]) 
                ];
            }
        }
    }
    update_post_meta($afon_order_id, 'items', $afon_updated_items);
    update_post_meta($afon_order_id, 'total_price', floatval($_POST['afon_total_price']));

    echo '<div class="updated notice is-dismissible afon-notice-red"><p><strong>Order #' . $afon_order_id . ' resource synchronized.</strong></p></div>';
}

// 2. DATA RETRIEVAL
$afon_customer_name    = get_post_meta($afon_order_id, 'customer_name', true);
$afon_customer_phone   = get_post_meta($afon_order_id, 'customer_phone', true);
$afon_customer_address = get_post_meta($afon_order_id, 'customer_address', true);
$afon_notes            = get_post_meta($afon_order_id, 'notes', true);
$afon_status           = get_post_meta($afon_order_id, 'status', true) ?: 'pending';
$afon_items            = get_post_meta($afon_order_id, 'items', true) ?: [];
$afon_total_price      = get_post_meta($afon_order_id, 'total_price', true) ?: 0;
?>

<div class="wrap afon-wrap">
    <div class="afon-header-flex">
        <h1 class="afon-page-title">Edit Order #<?php echo esc_html($afon_order_id); ?></h1>
        <a href="?page=awesome_food_delivery&tab=orders" class="afon-btn-action">
            <span class="dashicons dashicons-arrow-left-alt"></span> Back to Directory
        </a>
    </div>

    <form method="post" class="afon-form-sync">
        <?php wp_nonce_field('afon_update_order_action', 'afon_update_order_nonce'); ?>

        <div class="afon-extra-wrapper">
            
            <div class="afon-main-col">
                <div class="afon-conf-card">
                    <div class="afon-conf-header">
                        <h2><span class="dashicons dashicons-admin-users"></span> Customer Contact</h2>
                    </div>
                    <div class="afon-conf-body">
                        <div class="afon-view-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="afon-form-row">
                                <label>Customer Name</label>
                                <input type="text" name="afon_customer_name" value="<?php echo esc_attr($afon_customer_name); ?>" class="afon-input-modern">
                            </div>
                            <div class="afon-form-row">
                                <label>Phone Number</label>
                                <input type="text" name="afon_customer_phone" value="<?php echo esc_attr($afon_customer_phone); ?>" class="afon-input-modern">
                            </div>
                        </div>
                        <div class="afon-form-row">
                            <label>Delivery Address</label>
                            <textarea name="afon_customer_address" class="afon-input-modern" rows="2"><?php echo esc_textarea($afon_customer_address); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="afon-conf-card">
                    <div class="afon-conf-header">
                        <h2><span class="dashicons dashicons-cart"></span> Order Line Items</h2>
                    </div>
                    <table class="afon-extras-table afon-edit-table">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th width="100">Qty</th>
                                <th width="130">Price (€)</th>
                                <th width="130" class="afon-text-right">Subtotal (€)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($afon_items)): foreach($afon_items as $afon_item): ?>
                                <tr class="afon-item-row-edit">
                                    <td><input type="text" name="afon_items_name[]" value="<?php echo esc_attr($afon_item['name']); ?>" class="afon-input-modern"></td>
                                    <td><input type="number" name="afon_items_qty[]" value="<?php echo intval($afon_item['qty']); ?>" class="afon-input-modern afon-qty-trigger" min="1"></td>
                                    <td><input type="number" step="0.01" name="afon_items_price[]" value="<?php echo number_format($afon_item['price'], 2, '.', ''); ?>" class="afon-input-modern afon-price-trigger afon-readonly" readonly></td>
                                    <td class="afon-text-right"><strong class="afon-sub-val"><?php echo number_format($afon_item['qty'] * $afon_item['price'], 2, '.', ''); ?></strong></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    
                    <div class="afon-footer-totals">
                        <div class="afon-price-display">
                            <span class="afon-label-muted">Final Amount:</span>
                            <input type="number" step="0.01" name="afon_total_price" class="afon-total-input" value="<?php echo number_format(floatval($afon_total_price), 2, '.', ''); ?>" readonly>
                            <span class="afon-currency">€</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="afon-sidebar">
                <div class="afon-summary-box">
                    <h3 class="afon-sidebar-title">Workflow Execution</h3>
                    <div class="afon-form-row">
                        <label>Current Status</label>
                        <select name="afon_status" class="afon-input-modern afon-status-select">
                            <option value="pending" <?php selected($afon_status,'pending'); ?>>⏳ Pending</option>
                            <option value="processing" <?php selected($afon_status,'processing'); ?>>👨‍🍳 Processing</option>
                            <option value="completed" <?php selected($afon_status,'completed'); ?>>✅ Completed</option>
                            <option value="cancelled" <?php selected($afon_status,'cancelled'); ?>>❌ Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" name="afon_update_order" class="afon-btn-save afon-btn-block">Commit Changes</button>
                </div>

                <div class="afon-summary-box" style="margin-top: 20px;">
                    <h3 class="afon-sidebar-title">Kitchen Log</h3>
                    <textarea name="afon_notes" class="afon-input-modern" rows="4" placeholder="Chef notes..."><?php echo esc_textarea($afon_notes); ?></textarea>
                </div>
            </div>

        </div>
    </form>
</div>