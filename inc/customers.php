<?php
/*--------------------------------------------------------------
# Professional Customers Tab - Default WP Admin Layout
--------------------------------------------------------------*/
if(!class_exists('FD_Customers_List_Table')){
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';

    class FD_Customers_List_Table extends WP_List_Table {
        function __construct(){
            parent::__construct(['singular'=>'food_customer','plural'=>'food_customers','ajax'=>false]);
        }

        function get_columns(){
            return [
                'cb'=>'<input type="checkbox"/>',
                'id'=>'ID',
                'name'=>'Name',
                'email'=>'Email',
                'phone'=>'Phone',
                'total_orders'=>'Total Orders'
            ];
        }

        function column_cb($item){ return sprintf('<input type="checkbox" name="customer[]" value="%s"/>',$item->ID); }

        function column_id($item){ return $item->ID; }

        function column_name($item){ return $item->post_title; }

        function column_email($item){ return get_post_meta($item->ID,'email',true) ?: '-'; }

        function column_phone($item){ return get_post_meta($item->ID,'phone',true) ?: '-'; }

        function column_total_orders($item){
            $orders = get_posts(['post_type'=>'food_order','meta_key'=>'customer_id','meta_value'=>$item->ID,'numberposts'=>-1]);
            return count($orders);
        }

        function prepare_items(){
            $columns = $this->get_columns();
            $hidden = [];
            $sortable = [];
            $this->_column_headers = [$columns,$hidden,$sortable];

            $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = wp_count_posts('food_customer')->publish;

            $this->items = get_posts([
                'post_type'=>'food_customer',
                'numberposts'=>$per_page,
                'offset'=>($current_page-1)*$per_page
            ]);

            $this->set_pagination_args([
                'total_items'=>$total_items,
                'per_page'=>$per_page
            ]);
        }
    }
}

/*--------------------------------------------------------------
# Display Customers Tab
--------------------------------------------------------------*/
function fd_customers_tab(){
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Customers</h1>';

    $table = new FD_Customers_List_Table();
    $table->prepare_items();
    echo '<form method="post"><input type="hidden" name="page" value="food_delivery"/>';
    $table->display();
    echo '</form>';

    echo '</div>'; // wrap
}
