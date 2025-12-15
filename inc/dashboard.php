<?php
if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Full Admin Dashboard for Food Delivery (No Overflow)
--------------------------------------------------------------*/
function fd_dashboard_tab(){

    echo '<div class="wrap" style="padding:20px;">';

    // Flex container
    echo '<div style="display:flex;gap:30px;flex-wrap:wrap;">';

    echo '<div style="flex:1;min-width:300px;">';

    // ----- Dashboard Data -----
    $all_orders = get_posts(['post_type'=>'food_order','numberposts'=>-1]);
    $total_orders = count($all_orders);
    $completed = $pending = $cancelled = 0;
    $total_revenue = 0;

    foreach($all_orders as $o){
        $status = get_post_meta($o->ID,'status',true) ?: 'Pending';
        $total_price = floatval(get_post_meta($o->ID,'total_price',true));
        $total_revenue += $total_price;

        switch(strtolower($status)){
            case 'completed': $completed++; break;
            case 'pending': $pending++; break;
            case 'cancelled': $cancelled++; break;
        }
    }

    // Top 5 selling items
    $item_counts = [];
    foreach($all_orders as $o){
        $order_items = get_post_meta($o->ID,'items',true);
        if($order_items && is_array($order_items)){
            foreach($order_items as $i){
                $item_name = $i['name'] ?? 'Unknown';
                $qty = isset($i['qty']) ? intval($i['qty']) : 1;
                $item_counts[$item_name] = ($item_counts[$item_name] ?? 0) + $qty;
            }
        }
    }
    arsort($item_counts);
    $top_items = array_slice($item_counts,0,5,true);

    // ----- Dashboard HTML -----
    echo '<h2>Dashboard Overview</h2>';

    // Orders Summary Box
    echo '<div class="fd-dashboard-box" style="margin-bottom:20px;padding:15px;border:1px solid #ddd;">';
    echo '<h3>Orders Summary</h3>';
    echo '<p>Total Orders: <strong>'.$total_orders.'</strong></p>';
    echo '<p>Completed: <strong>'.$completed.'</strong></p>';
    echo '<p>Pending: <strong>'.$pending.'</strong></p>';
    echo '<p>Cancelled: <strong>'.$cancelled.'</strong></p>';
    echo '<p>Total Revenue: <strong>'.get_option('fd_currency','৳').number_format($total_revenue,2).'</strong></p>';
    echo '</div>';

    // Top Selling Items Box
    echo '<div class="fd-dashboard-box" style="margin-bottom:20px;padding:15px;border:1px solid #ddd;">';
    echo '<h3>Top Selling Items</h3>';
    if($top_items){
        echo '<ul>';
        foreach($top_items as $name=>$count){
            $item_post = get_page_by_title($name,'OBJECT','food_item');
            $thumb = $item_post ? get_the_post_thumbnail($item_post->ID,[50,50]) : '';
            echo '<li style="margin-bottom:5px;display:flex;align-items:center;">'.$thumb.' <span style="margin-left:8px;"><strong>'.esc_html($name).'</strong> - '.$count.' sold</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No items sold yet.</p>';
    }
    echo '</div>';

    // Recent Orders Box
    echo '<div class="fd-dashboard-box" style="margin-bottom:20px;padding:15px;border:1px solid #ddd;overflow-x:auto;">';
    echo '<h3>Recent Orders</h3>';
    if($all_orders){
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        $recent = array_slice($all_orders,-5);
        foreach($recent as $o){
            $status = get_post_meta($o->ID,'status',true) ?: 'Pending';
            $customer = get_post_meta($o->ID,'customer_name',true) ?: '-';
            $total = get_post_meta($o->ID,'total_price',true) ?: 0;
            echo '<tr>';
            echo '<td>#'.$o->ID.'</td>';
            echo '<td>'.esc_html($customer).'</td>';
            echo '<td>'.get_option('fd_currency','৳').number_format($total,2).'</td>';
            echo '<td>'.ucfirst($status).'</td>';
            echo '<td>'.get_the_date('Y-m-d H:i',$o->ID).'</td>';
            echo '<td><a href="?page=fd_orders&view='.$o->ID.'">View</a> | <a href="?page=fd_orders&edit='.$o->ID.'">Edit</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No orders yet.</p>';
    }
    echo '</div>';

    echo '</div>'; // flex:1
    echo '</div>'; // flex container
    echo '</div>'; // wrap
}
?>
