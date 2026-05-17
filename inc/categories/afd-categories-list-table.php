<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Enqueue DataTable & Media Scripts
--------------------------------------------------------------*/
add_action('admin_enqueue_scripts', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'awesome_food_delivery') return;

    wp_enqueue_style('fd-datatable-css', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css');
    wp_enqueue_script('fd-datatable-js', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['jquery'], null, true);
    wp_enqueue_media();
});

/*--------------------------------------------------------------
# AJAX Handler for Toggle
--------------------------------------------------------------*/
add_action('wp_ajax_fd_toggle_featured', function() {
    if (!current_user_can('manage_options')) wp_send_json_error();
    
    $term_id = intval($_POST['term_id']);
    $featured = $_POST['featured'] === 'true' ? '1' : '0';
    
    update_term_meta($term_id, 'fd_is_featured', $featured);
    wp_send_json_success();
});

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

    // Fetch categories ordered by custom order meta
    $terms = get_terms([
        'taxonomy'   => 'food_category', 
        'hide_empty' => false,
        'meta_key'   => 'fd_category_order',
        'orderby'    => 'meta_value_num',
        'order'      => 'ASC'
    ]);
    ?>

    <style>
        :root { 
            --res-primary: #d63638; 
            --res-dark: #1d2327;    
            --res-border: #ccd0d4; 
        }

        /* Modern Toggle Switch */
        .fd-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
        .fd-switch input { opacity: 0; width: 0; height: 0; }
        .fd-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .fd-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .fd-slider { background-color: var(--res-primary); }
        input:checked + .fd-slider:before { transform: translateX(18px); }

        /* Table Styles */
        #fd-category-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.05); }
        #fd-category-table thead th { background: #fafafa; padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; }
        #fd-category-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
        .fd-cat-thumb { width: 54px; height: 54px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        .fd-btn { padding: 6px 14px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #dcdcde; background: #fff; color: #2c3338; }
        .fd-btn:hover { border-color: var(--res-primary); color: var(--res-primary); background: #fff9f9; }
        .fd-btn-danger:hover { color: #fff; border-color: var(--res-primary); background: var(--res-primary); }
        .fd-count-badge { background: #f0f0f1; color: #3c434a; font-weight: 700; font-size: 11px; padding: 3px 10px; border-radius: 12px; border: 1px solid #dcdcde; }
        .fd-order-badge { background: #fff; color: var(--res-primary); border: 1px solid var(--res-primary); padding: 2px 8px; border-radius: 6px; font-weight: 700; }
    </style>

    <div class="wrap" style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin:0; font-weight: 700;">Food Categories</h1>
            <a href="<?php echo admin_url("admin.php?page=$page_slug&tab=categories&sub=add"); ?>" class="button button-primary" style="background:var(--res-primary); border-color:var(--res-primary); font-weight:600; padding: 0 20px;">+ Add New Category</a>
        </div>

        <table id="fd-category-table" class="widefat">
            <thead>
                <tr>
                    <th width="60">Order</th>
                    <th width="80">Thumbnail</th>
                    <th>Category Name</th>
                    <th>Featured</th>
                    <th>Items</th>
                    <th width="250" style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                <?php foreach ($terms as $term) :
                    $img_id      = get_term_meta($term->term_id, 'fd_category_image', true);
                    $is_featured = get_term_meta($term->term_id, 'fd_is_featured', true);
                    $order       = get_term_meta($term->term_id, 'fd_category_order', true) ?: '0';
                    
                    $edit_url   = admin_url("admin.php?page=$page_slug&tab=categories&sub=add&edit={$term->term_id}");
                    $delete_url = wp_nonce_url(admin_url("admin.php?page=$page_slug&tab=categories&sub=all&delete={$term->term_id}"), 'fd_delete_cat_' . $term->term_id);
                ?>
                <tr>
                    <td><span class="fd-order-badge"><?php echo esc_html($order); ?></span></td>
                    <td>
                        <?php if ($img_id) : 
                            echo wp_get_attachment_image($img_id, [60, 60], false, ['class' => 'fd-cat-thumb']);
                        else : ?>
                            <div class="fd-cat-no-img" style="width:54px;height:54px;background:#f6f7f7;display:flex;align-items:center;justify-content:center;border-radius:8px;"><span class="dashicons dashicons-format-image"></span></div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <strong style="font-size:15px; color: var(--res-dark);"><?php echo esc_html($term->name); ?></strong><br>
                        <code style="background:none; padding:0; color: #a7aaad;">slug: <?php echo esc_html($term->slug); ?></code>
                    </td>

                    <td>
                        <label class="fd-switch">
                            <input type="checkbox" class="fd-featured-toggle" data-id="<?php echo $term->term_id; ?>" <?php checked($is_featured, '1'); ?>>
                            <span class="fd-slider"></span>
                        </label>
                    </td>

                    <td><span class="fd-count-badge"><?php echo $term->count; ?></span></td>

                    <td style="text-align: right;">
                        <a class="fd-btn" href="<?php echo esc_url($edit_url); ?>">
                            <span class="dashicons dashicons-edit"></span> Edit
                        </a>
                        <a class="fd-btn fd-btn-danger" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this category?')">
                            <span class="dashicons dashicons-trash" style="color: var(--res-primary);"></span> Delete
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
        // Initialize DataTable
        $('#fd-category-table').DataTable({
            pageLength: 10,
            order: [[0, 'asc']], // Sort by Order column by default
            columnDefs: [
                { orderable: false, targets: [1, 3, 5] },
                { type: 'num', targets: 0 } // Ensure Order column is numeric
            ],
            dom: '<"top"f>rt<"bottom"ip><"clear">'
        });

        // Toggle Featured AJAX
        $(document).on('change', '.fd-featured-toggle', function() {
            const termId = $(this).data('id');
            const isChecked = $(this).is(':checked');
            
            $.post(ajaxurl, {
                action: 'fd_toggle_featured',
                term_id: termId,
                featured: isChecked
            }, function(response) {
                if(!response.success) alert('Failed to update status');
            });
        });
    });
    </script>
    <?php
}