<?php
if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# View Item - Classic WP admin style, Extras name only
--------------------------------------------------------------*/
function fd_view_item_tab($item_id){

    $item = get_post($item_id);
    if(!$item){
        echo '<div class="notice notice-error"><p>Item not found.</p></div>';
        return;
    }

    $price      = get_post_meta($item_id,'price',true);
    $cats       = wp_get_post_terms($item_id,'food_category');
    $extra_ids  = get_post_meta($item_id,'fd_item_extras',true);
    $extras_all = get_option('fd_extras',[]);

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">'.esc_html($item->post_title).'</h1>';
    echo '<a class="page-title-action" href="?page=awesome_food_delivery&tab=items&sub=edit&item='.$item_id.'">Edit</a>';
    echo '<hr class="wp-header-end">';

    echo '<table class="form-table" style="margin-top:20px;">';

    // Featured Image
    echo '<tr><th>Image</th><td>';
    if(has_post_thumbnail($item_id)){
        echo get_the_post_thumbnail($item_id,[150,150]);
    } else {
        echo '-';
    }
    echo '</td></tr>';

    // Description
    echo '<tr><th>Description</th><td>'.nl2br(esc_html($item->post_content)).'</td></tr>';

    // Price
    echo '<tr><th>Price</th><td>$'.esc_html($price).'</td></tr>';

    // Category
    echo '<tr><th>Category</th><td>';
    if($cats){
        foreach($cats as $c){
            echo esc_html($c->name).'<br>';
        }
    } else {
        echo '-';
    }
    echo '</td></tr>';

    // Extras (only name)
    echo '<tr><th>Extras</th><td>';
    if($extra_ids){
        foreach($extra_ids as $id){
            if(isset($extras_all[$id])){
                echo esc_html($extras_all[$id]['name']).'<br>';
            }
        }
    } else {
        echo '-';
    }
    echo '</td></tr>';

    echo '</table>';

    echo '<p><a class="button" href="?page=awesome_food_delivery&tab=items&sub=all">← Back to All Items</a></p>';
    echo '</div>';
}
