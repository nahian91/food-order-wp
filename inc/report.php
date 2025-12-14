<?php
/*--------------------------------------------------------------
# Reports Tab - Default WP Admin Layout
--------------------------------------------------------------*/
function fd_reports_tab(){
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Reports</h1>';

    $orders = get_posts([
        'post_type'=>'food_order',
        'numberposts'=>-1,
        'orderby'=>'date',
        'order'=>'DESC'
    ]);

    $total_revenue = 0;
    $pending = 0;
    $completed = 0;

    foreach($orders as $o){
        $total_price = floatval(get_post_meta($o->ID,'total_price',true));
        $status = get_post_meta($o->ID,'status',true) ?: 'Pending';
        $total_revenue += $total_price;
        if($status=='Completed') $completed++;
        else $pending++;
    }

    // Summary metabox
    echo '<div class="metabox-holder columns-4">';
    echo '<div class="postbox"><h2 class="hndle"><span>Total Orders</span></h2><div class="inside">'.count($orders).'</div></div>';
    echo '<div class="postbox"><h2 class="hndle"><span>Total Revenue</span></h2><div class="inside">'.get_option('fd_currency','৳').' '.number_format($total_revenue,2).'</div></div>';
    echo '<div class="postbox"><h2 class="hndle"><span>Completed Orders</span></h2><div class="inside">'.$completed.'</div></div>';
    echo '<div class="postbox"><h2 class="hndle"><span>Pending Orders</span></h2><div class="inside">'.$pending.'</div></div>';
    echo '</div>';

    // Recent orders table
    echo '<h2>Recent Orders</h2>';
    if($orders){
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        foreach($orders as $o){
            $customer = get_post_meta($o->ID,'customer_name',true) ?: '-';
            $total = floatval(get_post_meta($o->ID,'total_price',true));
            $status = get_post_meta($o->ID,'status',true) ?: 'Pending';
            $date = get_the_date('Y-m-d H:i',$o->ID);
            echo "<tr><td>{$o->ID}</td><td>{$customer}</td><td>".get_option('fd_currency','৳')." {$total}</td><td>{$status}</td><td>{$date}</td></tr>";
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No orders found.</p>';
    }

    echo '</div>'; // wrap
}
