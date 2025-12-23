<?php
/*-----------------------------------------
# Categories Tab Main - FIXED
-----------------------------------------*/
function fd_category_tab() {
    wp_enqueue_media(); // Media uploader

    // Define sub-tabs
    $sub_tabs = [
        'add'  => 'Add Category',
        'all'  => 'All Categories',
        'view' => 'View Category'
    ];

    // Determine active sub-tab
    $active_sub = $_GET['sub'] ?? 'all'; // Default to 'all' to see the list first
    $current_page = 'awesome_food_delivery'; 

    echo '<h2 class="nav-tab-wrapper">';
    foreach ($sub_tabs as $key => $label) {
        // Skip 'view' tab in main nav bar (accessed via buttons only)
        if ($key === 'view') continue;

        $url = add_query_arg([
            'page' => $current_page,
            'tab'  => 'categories',
            'sub'  => $key
        ], admin_url('admin.php'));

        echo '<a class="nav-tab' . ($active_sub === $key ? ' nav-tab-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    echo '</h2><div style="margin-top:20px;">';

    // Switch content based on sub-tab
    switch ($active_sub) {
        case 'add':
            // Check if we are editing or adding
            $edit_id = intval($_GET['edit'] ?? 0);
            fd_category_add_edit($edit_id);
            break;

        case 'all':
            fd_category_list();
            break;

        case 'view':
            // FIX: Changed 'item' to 'view' to match your button URL: &view=4
            $cat_id = intval($_GET['view'] ?? 0); 
            
            if ($cat_id > 0) {
                fd_category_view($cat_id);
            } else {
                echo '<div class="notice notice-error"><p>Invalid Category ID.</p></div>';
                fd_category_list();
            }
            break;

        default:
            fd_category_list();
            break;
    }

    echo '</div>';
}