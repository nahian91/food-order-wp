<?php
/**
 * View Order Details - Restaurant Red SaaS UI
 */
if (!defined('ABSPATH')) exit;

// Fetch Data
$afon_customer_name    = get_post_meta($order_id, 'customer_name', true);
$afon_customer_phone   = get_post_meta($order_id, 'customer_phone', true);
$afon_customer_address = get_post_meta($order_id, 'customer_address', true);
$afon_notes            = get_post_meta($order_id, 'notes', true);
$afon_status           = strtolower(get_post_meta($order_id, 'status', true) ?: 'pending');
$afon_items            = get_post_meta($order_id, 'items', true);
$afon_total            = get_post_meta($order_id, 'total_price', true);

// Custom ID Format: SOI-MON-DAY-ID
$date_prefix = strtoupper(get_the_date('M-d', $order_id));
$display_id  = "SOI-{$date_prefix}-{$order_id}";
?>

<style>
    :root { --res-primary: #d63638; --res-dark: #1d2327; --res-border: #ccd0d4; }
    .afon-wrap { margin-top: 20px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
    .afon-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .afon-page-title { font-weight: 800; font-size: 24px; margin: 0; }
    
    /* Layout */
    .afon-extra-wrapper { display: grid; grid-template-columns: 1fr 350px; gap: 20px; }
    .afon-conf-card { background: #fff; border: 1px solid var(--res-border); border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
    .afon-conf-header { background: #fafafa; padding: 15px 20px; border-bottom: 1px solid #f0f0f1; }
    .afon-conf-header h2 { margin: 0; font-size: 16px; font-weight: 700; color: var(--res-dark); display: flex; align-items: center; gap: 8px; }
    .afon-conf-body { padding: 20px; }
    
    /* Table */
    .afon-view-table { width: 100%; border-collapse: collapse; }
    .afon-view-table th { text-align: left; padding: 12px 20px; background: #fdfdfd; font-size: 11px; text-transform: uppercase; color: #646970; }
    .afon-view-table td { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; }
    .afon-qty-badge { background: #f1f1f1; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 12px; margin-right: 10px; }
    
    /* Totals */
    .afon-footer-totals { padding: 20px; background: #f9f9f9; text-align: right; }
    .afon-price-display { font-size: 24px; font-weight: 800; color: var(--res-primary); display: block; }
    
    /* Sidebar */
    .afon-summary-box { background: #fff; border: 1px solid var(--res-border); border-radius: 8px; padding: 20px; }
    .afon-sidebar-title { margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
    .afon-info-grid { display: grid; grid-template-columns: 100px 1fr; gap: 10px; font-size: 13px; margin-bottom: 15px; }
    .afon-label-muted { color: #646970; }
    .afon-data-value { font-weight: 600; color: var(--res-dark); }
    
    /* Badges */
    .afon-status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 15px; }
    .afon-status-pending { background: #fff8e5; color: #856404; border: 1px solid #ffeeba; }
    .afon-status-completed { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    
    .afon-btn-action { text-decoration: none; color: #2c3338; border: 1px solid #dcdcde; padding: 8px 15px; border-radius: 6px; font-weight: 600; background: #fff; }
    .afon-btn-save { display: block; text-align: center; background: var(--res-dark); color: #fff; text-decoration: none; padding: 12px; border-radius: 6px; font-weight: 600; }
</style>

<div class="wrap afon-wrap">
    <div class="afon-header-flex">
        <h1 class="afon-page-title">Order ID: <span style="color:var(--res-primary)"><?php echo esc_html($display_id); ?></span></h1>
        <a href="?page=awesome_food_delivery&tab=orders" class="afon-btn-action">
            <span class="dashicons dashicons-arrow-left-alt"></span> Back to List
        </a>
    </div>

    <div class="afon-extra-wrapper">
        
        <div class="afon-main-col">
            <div class="afon-conf-card">
                <div class="afon-conf-header">
                    <h2><span class="dashicons dashicons-cart"></span> Order Summary</h2>
                </div>
                <table class="afon-view-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th width="100">Qty</th>
                            <th width="130" style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(is_array($afon_items)): 
                            foreach($afon_items as $item): 
                                $qty = isset($item['qty']) ? intval($item['qty']) : 1;
                                $price = isset($item['price']) ? floatval($item['price']) : 0;
                                $name = !empty($item['name']) ? $item['name'] : (!empty($item['item_name']) ? $item['item_name'] : 'Product');
                        ?>
                            <tr>
                                <td>
                                    <span class="afon-qty-badge"><?php echo $qty; ?></span>
                                    <strong style="font-size:14px;"><?php echo esc_html($name); ?></strong>
                                </td>
                                <td>&times; <?php echo $qty; ?></td>
                                <td style="text-align:right; font-weight:700;">
                                    <?php echo number_format($qty * $price, 2) . ' €'; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div class="afon-footer-totals">
                    <span class="afon-label-muted">Total Amount</span>
                    <span class="afon-price-display"><?php echo number_format(floatval($afon_total), 2) . ' €'; ?></span>
                </div>
            </div>

            <div class="afon-conf-card">
                <div class="afon-conf-header">
                    <h2><span class="dashicons dashicons-testimonial"></span> Special Instructions</h2>
                </div>
                <div class="afon-conf-body">
                    <?php if($afon_notes): ?>
                        <div style="background: #fdf2cd; padding: 15px; border-left: 4px solid #f0ad4e; font-style: italic;">
                            <?php echo nl2br(esc_html($afon_notes)); ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #a7aaad;">No special notes provided.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="afon-sidebar">
            <div class="afon-summary-box">
                <h3 class="afon-sidebar-title">Logistic Status</h3>
                <span class="afon-status-badge afon-status-<?php echo esc_attr($afon_status); ?>">
                    ● <?php echo strtoupper($afon_status); ?>
                </span>
                <div class="afon-info-grid">
                    <span class="afon-label-muted">Post ID</span>
                    <span class="afon-data-value">#<?php echo esc_html($order_id); ?></span>
                    
                    <span class="afon-label-muted">Date</span>
                    <span class="afon-data-value"><?php echo get_the_date('d M Y', $order_id); ?></span>

                    <span class="afon-label-muted">Time</span>
                    <span class="afon-data-value"><?php echo get_the_date('g:i a', $order_id); ?></span>
                </div>
                <a href="?page=awesome_food_delivery&tab=orders&order_id=<?php echo $order_id; ?>&action=edit" class="afon-btn-save">
                    Update Order Status
                </a>
            </div>

            <div class="afon-summary-box" style="margin-top: 20px;">
                <h3 class="afon-sidebar-title">Customer Details</h3>
                <div class="afon-info-grid">
                    <span class="afon-label-muted">Name</span>
                    <span class="afon-data-value"><?php echo esc_html($afon_customer_name ?: 'Guest'); ?></span>
                    
                    <span class="afon-label-muted">Phone</span>
                    <span class="afon-data-value" style="color:var(--res-primary)"><?php echo esc_html($afon_customer_phone ?: 'N/A'); ?></span>
                </div>
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #eee;">
                    <span class="afon-label-muted" style="font-size:11px; text-transform:uppercase;">Delivery Address</span>
                    <p style="margin:8px 0 0 0; line-height:1.5; font-weight:500;">
                        <?php echo nl2br(esc_html($afon_customer_address ?: 'Pickup / No Address')); ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>