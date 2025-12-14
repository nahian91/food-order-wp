<?php 

/*--------------------------------------------------------------
# Dashboard Tab
--------------------------------------------------------------*/
function fd_dashboard_tab(){
    // Orders overview
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
    $items = get_posts(['post_type'=>'food_item','numberposts'=>-1]);
    $item_counts = [];
    foreach($all_orders as $o){
        $order_items = get_post_meta($o->ID,'items',true);
        if($order_items){
            foreach($order_items as $i){
                if(isset($item_counts[$i])) $item_counts[$i]++;
                else $item_counts[$i]=1;
            }
        }
    }
    arsort($item_counts);
    $top_items = array_slice($item_counts,0,5,true);

    ?>
    <h2>Dashboard Overview</h2>

    <div class="fd-dashboard-box">
        <h3>Orders Summary</h3>
        <p>Total Orders: <strong><?php echo $total_orders; ?></strong></p>
        <p>Completed: <strong><?php echo $completed; ?></strong></p>
        <p>Pending: <strong><?php echo $pending; ?></strong></p>
        <p>Cancelled: <strong><?php echo $cancelled; ?></strong></p>
        <p>Total Revenue: <strong><?php echo get_option('fd_currency','৳').number_format($total_revenue,2); ?></strong></p>
    </div>

    <div class="fd-dashboard-box">
        <h3>Top Selling Items</h3>
        <?php if($top_items): ?>
            <ul>
                <?php foreach($top_items as $id=>$count):
                    $post = get_post($id);
                    $thumb = get_the_post_thumbnail($id,[40,40]);
                    ?>
                    <li><?php echo $thumb; ?> <?php echo esc_html($post->post_title); ?> - <strong><?php echo $count; ?> sold</strong></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No items sold yet.</p>
        <?php endif; ?>
    </div>

    <div class="fd-dashboard-box">
        <h3>Recent Orders</h3>
        <?php if($all_orders): ?>
            <ul>
                <?php
                $recent = array_slice($all_orders,-5);
                foreach($recent as $o){
                    $status = get_post_meta($o->ID,'status',true) ?: 'Pending';
                    echo '<li>#'.$o->ID.' - '.$o->post_title.' ('.$status.')</li>';
                }
                ?>
            </ul>
        <?php else: ?>
            <p>No orders yet.</p>
        <?php endif; ?>
    </div>
    <?php
}