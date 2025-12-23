<?php
// All Orders Tab (shows list + handles routing to Edit/View/Print)

function fd_orders_tab() {

    $action = $_GET['action'] ?? '';
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    // Include specific files based on action
    switch($action){
        case 'edit':
            if($order_id){
                require plugin_dir_path(__FILE__) . 'orders/afd-orders-edit.php';
            }
            break;

        case 'view':
            if($order_id){
                require plugin_dir_path(__FILE__) . 'orders/afd-orders-view.php';
            }
            break;

        case 'print':
            if ($order_id) {
                $type = $_GET['type'] ?? 'customer';
                if ($type === 'kitchen') {
                    require plugin_dir_path(__FILE__) . 'orders/afd-orders-print-kitchen.php';
                } else {
                    require plugin_dir_path(__FILE__) . 'orders/afd-orders-print-customer.php';
                }
            }
            break;

        default:
            require plugin_dir_path(__FILE__) . 'orders/afd-orders-list.php';
            break;
    }
}
