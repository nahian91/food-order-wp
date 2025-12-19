<?php
if (!defined('ABSPATH')) exit;

function fd_orders_tab() {
    echo '<div class="wrap"><h1>Latest Orders</h1>';
    $table = new FD_Orders_List_Table();
    $table->prepare_items();
    echo '<form method="post">';
    $table->display();
    echo '</form></div>';
}
