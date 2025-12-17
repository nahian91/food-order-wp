<?php
if(!defined('ABSPATH')) exit;

function fd_items_tab(){
    $sub = $_GET['sub'] ?? 'add';

    $tabs = [
        'add' => 'Add Item',
        'all' => 'All Items',
    ];

    echo '<h2 class="nav-tab-wrapper">';
    foreach($tabs as $k=>$label){
        echo '<a class="nav-tab '.($sub==$k?'nav-tab-active':'').'" href="?page=awesome_food_delivery&tab=items&sub='.$k.'">'.$label.'</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    if($sub === 'add'){
        fd_add_edit_item_tab();
    }
    elseif($sub === 'edit'){
        fd_add_edit_item_tab(intval($_GET['item']));
    }
    elseif($sub === 'view'){
        fd_view_item_tab(intval($_GET['item']));
    }
    else{
        fd_items_list();
    }

    echo '</div>';
}
