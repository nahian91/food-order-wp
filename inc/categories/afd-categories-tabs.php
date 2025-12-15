<?php 

/*-----------------------------------------
# Categories Tab Main
-----------------------------------------*/
function fd_category_tab(){
    wp_enqueue_media(); // Media uploader

    $sub_tabs = ['add'=>'Add Category','all'=>'All Categories'];
    $active_sub = $_GET['sub'] ?? 'add';

    echo '<h2 class="nav-tab-wrapper">';
    foreach($sub_tabs as $k=>$label){
        echo '<a class="nav-tab'.($active_sub==$k?' nav-tab-active':'').'" href="?page=food_delivery&tab=categories&sub='.$k.'">'.$label.'</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    switch($active_sub){
        case 'add':
            fd_category_add_edit();
            break;

        case 'all':
            fd_category_list();
            break;

        case 'view':
            fd_category_view(intval($_GET['item'] ?? 0));
            break;
    }

    echo '</div>';
}