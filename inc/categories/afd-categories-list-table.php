<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Enqueue DataTable (Admin Only) - Optimized
--------------------------------------------------------------*/
add_action('admin_enqueue_scripts', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'awesome_food_delivery') return;

    wp_enqueue_style('fd-datatable-css', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css');
    wp_enqueue_script('fd-datatable-js', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['jquery'], null, true);
});

/*--------------------------------------------------------------
# List All Categories - Restaurant Red UI
--------------------------------------------------------------*/
function fd_category_list() {
    $page_slug = 'awesome_food_delivery';

    // Single Delete Logic
    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        $term_id = intval($_GET['delete']);
        $nonce   = $_GET['_wpnonce'] ?? '';
        if (wp_verify_nonce($nonce, 'fd_delete_cat_' . $term_id)) {
            wp_delete_term($term_id, 'food_category');
            echo '<div class="notice notice-success is-dismissible" style="border-left-color: #d63638;"><p>Category removed successfully.</p></div>';
        }
    }

    $terms = get_terms(['taxonomy' => 'food_category', 'hide_empty' => false]);
    ?>

    <style>
        :root { 
            --res-primary: #d63638; 
            --res-dark: #1d2327;    
            --res-border: #ccd0d4; 
        }

        /* Table Modernization */
        #fd-category-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.05); }
        #fd-category-table thead th { background: #fafafa; padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; }
        #fd-category-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
        
        /* Category Image */
        .fd-cat-thumb { width: 54px; height: 54px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        .fd-cat-no-img { width: 54px; height: 54px; border-radius: 8px; background: #f6f7f7; display: flex; align-items: center; justify-content: center; color: #c3c4c7; border: 1px solid #eee; }

        /* Restaurant Buttons */
        .fd-btn { padding: 6px 14px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #dcdcde; background: #fff; color: #2c3338; }
        .fd-btn:hover { border-color: var(--res-primary); color: var(--res-primary); background: #fff9f9; }
        
        .fd-btn-danger:hover { color: #fff; border-color: var(--res-primary); background: var(--res-primary); }
        .fd-btn-danger .dashicons { transition: 0.2s; }
        .fd-btn-danger:hover .dashicons { color: #fff !important; }

        /* Items Count Badge */
        .fd-count-badge { background: #f0f0f1; color: #3c434a; font-weight: 700; font-size: 11px; padding: 3px 10px; border-radius: 12px; border: 1px solid #dcdcde; }

        /* DataTable Search Fixes */
        .dataTables_wrapper .dataTables_filter { float: none; text-align: left; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #c3c4c7; border-radius: 4px; padding: 8px 12px; margin-bottom: 20px; width: 300px; transition: 0.2s; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--res-primary); box-shadow: 0 0 0 1px var(--res-primary); outline: none; }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--res-primary) !important; color: #fff !important; border: 1px solid var(--res-primary) !important; border-radius: 4px; }
    </style>

    <div class="wrap" style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin:0; font-weight: 700;"><?php esc_html_e('Food Categories', 'text-domain'); ?></h1>
            <a href="<?php echo admin_url("admin.php?page=$page_slug&tab=categories&sub=add"); ?>" class="button button-primary" style="background:var(--res-primary); border-color:var(--res-primary); font-weight:600; padding: 0 20px;">+ Add New Category</a>
        </div>

        <table id="fd-category-table" class="widefat">
            <thead>
                <tr>
                    <th width="85">Thumbnail</th>
                    <th>Category Name</th>
                    <th>Items Linked</th>
                    <th width="200" style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                <?php foreach ($terms as $term) :
                    $img_id = get_term_meta($term->term_id, 'fd_category_image', true);
                    $edit_url = admin_url("admin.php?page=$page_slug&tab=categories&sub=add&edit={$term->term_id}");
                    $delete_url = wp_nonce_url(admin_url("admin.php?page=$page_slug&tab=categories&sub=all&delete={$term->term_id}"), 'fd_delete_cat_' . $term->term_id);
                ?>
                <tr>
                    <td>
                        <?php if ($img_id) : 
                            echo wp_get_attachment_image($img_id, [60, 60], false, ['class' => 'fd-cat-thumb']);
                        else : ?>
                            <div class="fd-cat-no-img"><span class="dashicons dashicons-format-image"></span></div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <strong style="font-size:15px; color: var(--res-dark);"><?php echo esc_html($term->name); ?></strong><br>
                        <code style="background:none; padding:0; color: #a7aaad;">slug: <?php echo esc_html($term->slug); ?></code>
                    </td>

                    <td>
                        <span class="fd-count-badge">
                            <?php echo $term->count; ?> Products
                        </span>
                    </td>

                    <td style="text-align: right;">
                        <a class="fd-btn" href="<?php echo esc_url($edit_url); ?>">
                            <span class="dashicons dashicons-edit" style="font-size:16px; margin-top:3px;"></span> Edit
                        </a>
                        <a class="fd-btn fd-btn-danger" 
                           href="<?php echo esc_url($delete_url); ?>" 
                           onclick="return confirm('Careful! This category and its links will be removed. Continue?')">
                            <span class="dashicons dashicons-trash" style="font-size:16px; margin-top:3px; color: var(--res-primary);"></span> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function ($) {
        $('#fd-category-table').DataTable({
            pageLength: 10,
            ordering: true,
            searching: true,
            language: {
                search: "",
                searchPlaceholder: "Search categories...",
                paginate: { next: '→', previous: '←' }
            },
            columnDefs: [
                { orderable: false, targets: [0, 3] }
            ],
            dom: '<"top"f>rt<"bottom"ip><"clear">'
        });
    });
    </script>
<?php
}