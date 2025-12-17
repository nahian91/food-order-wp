<?php
/*-----------------------------------------
# Categories Tab Main
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
    $active_sub = $_GET['sub'] ?? 'add';
    $current_page = 'awesome_food_delivery'; // Main menu slug

    echo '<h2 class="nav-tab-wrapper">';
    foreach ($sub_tabs as $key => $label) {
        // Skip 'view' tab in main tabs (it's for internal use)
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
            fd_category_add_edit();
            break;

        case 'all':
            fd_category_list();
            break;

        case 'view':
            $item_id = intval($_GET['item'] ?? 0);
            fd_category_view($item_id);
            break;

        default:
            echo '<p>Select a tab above.</p>';
            break;
    }

    echo '</div>';
}
