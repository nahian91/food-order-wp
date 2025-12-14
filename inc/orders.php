<?php
/*--------------------------------------------------------------
# Professional Orders Tab
--------------------------------------------------------------*/
if(!class_exists('FD_Orders_List_Table')){
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';

    class FD_Orders_List_Table extends WP_List_Table {
        function __construct(){
            parent::__construct(['singular'=>'food_order','plural'=>'food_orders','ajax'=>false]);
        }

        function get_columns(){
            return [
                'cb'=>'<input type="checkbox"/>',
                'order_id'=>'Order ID',
                'customer'=>'Customer',
                'items'=>'Items Ordered',
                'total'=>'Total Price',
                'status'=>'Status',
                'date'=>'Date'
            ];
        }

        function column_cb($item){ return sprintf('<input type="checkbox" name="order[]" value="%s"/>',$item->ID); }

        function column_order_id($item){ return $item->ID; }

        function column_customer($item){
            $customer = get_post_meta($item->ID,'customer_name',true);
            return $customer ?: '-';
        }

        function column_items($item){
            $items = get_post_meta($item->ID,'items',true); // array of item IDs
            if(!$items) return '-';
            $out='';
            foreach($items as $i){
                $post = get_post($i);
                if($post) $out .= esc_html($post->post_title).'<br>';
            }
            return $out;
        }

        function column_total($item){
            $total = get_post_meta($item->ID,'total_price',true);
            return $total ? '$'.number_format($total,2) : '-';
        }

        function column_status($item){
            $status = get_post_meta($item->ID,'status',true) ?: 'Pending';
            return ucfirst($status);
        }

        function column_date($item){ return get_the_date('Y-m-d H:i',$item->ID); }

        function prepare_items(){
            $columns = $this->get_columns();
            $hidden = [];
            $sortable = ['order_id'=>['order_id',true],'date'=>['date',true]];
            $this->_column_headers = [$columns,$hidden,$sortable];

            $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = wp_count_posts('food_order')->publish;

            $this->items = get_posts([
                'post_type'=>'food_order',
                'numberposts'=>$per_page,
                'offset'=>($current_page-1)*$per_page,
                'orderby'=>'date',
                'order'=>'DESC'
            ]);

            $this->set_pagination_args([
                'total_items'=>$total_items,
                'per_page'=>$per_page
            ]);
        }
    }
}

/*--------------------------------------------------------------
# Display Orders Tab
--------------------------------------------------------------*/
function fd_orders_tab(){
    echo '<h2>All Orders</h2>';
    $table = new FD_Orders_List_Table();
    $table->prepare_items();
    echo '<form method="post"><input type="hidden" name="page" value="food_delivery"/>';
    $table->display();
    echo '</form>';
}
