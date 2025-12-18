<?php
/**
 * View Order Details - Restaurant Red SaaS UI
 */
if (!defined('ABSPATH')) exit;

// Fetch Data with afon_ prefix
$afon_customer_name    = get_post_meta($order_id, 'customer_name', true);
$afon_customer_phone   = get_post_meta($order_id, 'customer_phone', true);
$afon_customer_address = get_post_meta($order_id, 'customer_address', true);
$afon_notes            = get_post_meta($order_id, 'notes', true);
$afon_status           = strtolower(get_post_meta($order_id, 'status', true) ?: 'pending');
$afon_items            = get_post_meta($order_id, 'items', true);
$afon_total            = get_post_meta($order_id, 'total_price', true);
?>

<div class="wrap afon-wrap">
    <div class="afon-header-flex">
        <h1 class="afon-page-title">Order Details #<?php echo esc_html($order_id); ?></h1>
        <a href="?page=awesome_food_delivery&tab=orders" class="afon-btn-action">
            <span class="dashicons dashicons-arrow-left-alt"></span> Back to Directory
        </a>
    </div>

    <div class="afon-extra-wrapper">
        
        <div class="afon-main-col">
            <div class="afon-conf-card">
                <div class="afon-conf-header">
                    <h2><span class="dashicons dashicons-cart"></span> <?php esc_html_e('Order Summary', 'text-domain'); ?></h2>
                </div>
                <table class="afon-extras-table afon-view-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th width="100" class="afon-text-center">Qty</th>
                            <th width="130" class="afon-text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(is_array($afon_items)): 
                            foreach($afon_items as $afon_item): 
                                $afon_qty = isset($afon_item['qty']) ? intval($afon_item['qty']) : 1;
                                $afon_price = isset($afon_item['price']) ? floatval($afon_item['price']) : 0;
                        ?>
                            <tr>
                                <td>
                                    <span class="afon-qty-badge"><?php echo $afon_qty; ?></span>
                                    <strong class="afon-item-name-bold"><?php echo esc_html($afon_item['name']); ?></strong>
                                </td>
                                <td class="afon-text-center afon-text-muted">&times; <?php echo $afon_qty; ?></td>
                                <td class="afon-text-right">
                                    <span class="afon-subtotal-val">
                                        <?php echo number_format($afon_qty * $afon_price, 2, '.', '') . ' €'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div class="afon-footer-totals">
                    <span class="afon-label-muted">Amount Due:</span>
                    <span class="afon-price-display"><?php echo number_format(floatval($afon_total), 2, '.', '') . ' €'; ?></span>
                </div>
            </div>

            <div class="afon-conf-card">
                <div class="afon-conf-header">
                    <h2><span class="dashicons dashicons-testimonial"></span> Special Instructions</h2>
                </div>
                <div class="afon-conf-body">
                    <?php if($afon_notes): ?>
                        <div class="afon-notes-box">
                            <?php echo nl2br(esc_html($afon_notes)); ?>
                        </div>
                    <?php else: ?>
                        <p class="afon-text-empty">No special notes provided for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="afon-sidebar">
            <div class="afon-summary-box">
                <h3 class="afon-sidebar-title">Logistic Info</h3>
                <div class="afon-status-wrapper">
                    <span class="afon-status-badge afon-status-<?php echo esc_attr($afon_status); ?> afon-status-full">
                        <?php echo esc_html($afon_status); ?>
                    </span>
                </div>
                <div class="afon-info-grid">
                    <span class="afon-label-muted">Order ID</span>
                    <span class="afon-data-value">#<?php echo esc_html($order_id); ?></span>
                    
                    <span class="afon-label-muted">Created</span>
                    <span class="afon-data-value"><?php echo get_the_date('H:i | d M', $order_id); ?></span>
                </div>
                <div class="afon-sidebar-divider"></div>
                <a href="?page=awesome_food_delivery&tab=orders&order_id=<?php echo $order_id; ?>&action=edit" class="afon-btn-save afon-btn-block">
                    Modify Order Status
                </a>
            </div>

            <div class="afon-summary-box" style="margin-top: 20px;">
                <h3 class="afon-sidebar-title">Customer Resource</h3>
                <div class="afon-info-grid">
                    <span class="afon-label-muted">Name</span>
                    <span class="afon-data-value"><?php echo esc_html($afon_customer_name ?: 'Guest'); ?></span>
                    
                    <span class="afon-label-muted">Phone</span>
                    <span class="afon-data-value afon-text-primary"><?php echo esc_html($afon_customer_phone ?: '—'); ?></span>
                </div>
                <div class="afon-address-card">
                    <span class="afon-label-muted-small">Delivery Destination</span>
                    <p class="afon-address-text">
                        <?php echo nl2br(esc_html($afon_customer_address ?: 'No address / Pickup')); ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>