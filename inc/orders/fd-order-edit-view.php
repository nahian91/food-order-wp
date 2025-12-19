<?php
if (!defined('ABSPATH')) exit;

function fd_edit_order_page() {
    if (isset($_GET['order_id'])) {
        fd_single_order_layout(intval($_GET['order_id']), true);
    }
}

function fd_view_order_page() {
    if (isset($_GET['order_id'])) {
        fd_single_order_layout(intval($_GET['order_id']), false);
    }
}

function fd_single_order_layout($order_id, $editable = false) {
    $order = get_post($order_id);
    if (!$order) {
        echo '<div class="notice notice-error"><p>Order not found.</p></div>';
        return;
    }

    $items    = get_post_meta($order_id, 'items', true) ?: [];
    $total    = floatval(get_post_meta($order_id, 'total_price', true) ?: 0);
    $customer = get_post_meta($order_id, 'customer_name', true);
    $status   = get_post_meta($order_id, 'status', true) ?: 'Pending';
    $date     = get_the_date('d M Y H:i', $order_id);

    if ($editable && isset($_POST['update_order'])) {
        $status = sanitize_text_field($_POST['status']);
        update_post_meta($order_id, 'status', $status);

        $new_items = [];
        $total_price = 0;

        foreach ($_POST['items'] as $i) {
            if (!empty($i['remove'])) continue;
            $name  = sanitize_text_field($i['name']);
            $qty   = intval($i['qty']);
            $price = floatval($i['price']);

            if ($name && $qty > 0) {
                $new_items[] = compact('name', 'qty', 'price');
                $total_price += $qty * $price;
            }
        }

        update_post_meta($order_id, 'items', $new_items);
        update_post_meta($order_id, 'total_price', $total_price);

        $items = $new_items;
        $total = $total_price;

        echo '<div class="notice notice-success"><p>Order updated successfully.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo $editable ? 'Edit' : 'View'; ?> Order #<?php echo $order_id; ?></h1>
        <p><strong>Customer:</strong> <?php echo esc_html($customer); ?></p>
        <p><strong>Status:</strong> <?php echo esc_html($status); ?></p>
        <p><strong>Date:</strong> <?php echo esc_html($date); ?></p>
        <p><strong>Total:</strong> €<?php echo number_format($total, 2); ?></p>

        <form method="post">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price (€)</th>
                        <?php if ($editable): ?><th>Remove</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $i): ?>
                        <tr>
                            <td><?php echo $editable ? '<input type="text" name="items['.$index.'][name]" value="'.esc_attr($i['name']).'">' : esc_html($i['name']); ?></td>
                            <td><?php echo $editable ? '<input type="number" min="1" name="items['.$index.'][qty]" value="'.esc_attr($i['qty']).'">' : esc_html($i['qty']); ?></td>
                            <td><?php echo $editable ? '<input type="number" step="0.01" name="items['.$index.'][price]" value="'.esc_attr($i['price']).'">' : '€'.number_format($i['price'],2); ?></td>
                            <?php if ($editable): ?><td><input type="checkbox" name="items[<?php echo $index; ?>][remove]"></td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($editable): ?>
                <p>
                    <select name="status">
                        <option value="Pending" <?php selected($status,'Pending');?>>Pending</option>
                        <option value="Processing" <?php selected($status,'Processing');?>>Processing</option>
                        <option value="Completed" <?php selected($status,'Completed');?>>Completed</option>
                    </select>
                    <input type="submit" name="update_order" class="button button-primary" value="Update Order">
                </p>
            <?php endif; ?>
        </form>
        <p><a href="<?php echo admin_url('admin.php?page=fd_orders'); ?>" class="button">Back</a></p>
    </div>
    <?php
}
