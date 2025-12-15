<?php

/*-----------------------------------------
# View Category (Name + Image, WP Admin Default Layout)
-----------------------------------------*/
function fd_category_view($id){
    $term = get_term($id,'food_category');
    if(!$term){
        echo '<div class="notice notice-error"><p>Category not found.</p></div>';
        return;
    }

    $img_id = get_term_meta($id,'fd_category_image',true);
    $foods = get_posts([
        'post_type'=>'food_item',
        'tax_query'=>[
            ['taxonomy'=>'food_category','field'=>'term_id','terms'=>$id]
        ],
        'numberposts'=>-1
    ]);
    ?>

    <div class="wrap">
        <h1>Category Details</h1>

        <table class="form-table">
            <tr>
                <th>Name</th>
                <td><?php echo esc_html($term->name); ?></td>
            </tr>
            <tr>
                <th>Image</th>
                <td>
                    <?php 
                    if($img_id){
                        echo wp_get_attachment_image($img_id,[150,150]);
                    } else {
                        echo 'No image set.';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Assigned Foods</th>
                <td>
                    <?php if($foods): ?>
                        <ul>
                            <?php foreach($foods as $f): ?>
                                <li><?php echo esc_html($f->post_title); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        No foods assigned.
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <p>
            <a class="button" href="?page=food_delivery&tab=categories&sub=all">Back to All Categories</a>
        </p>
    </div>

<?php
}
