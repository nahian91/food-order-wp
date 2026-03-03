<?php
if (!defined('ABSPATH')) exit;

/**
 * List View for Food Menu Items
 * Optimized for DataTable 1.13+ with Visibility Filtering
 */
function fd_items_list() {
    $items = get_posts([
        'post_type'   => 'food_item',
        'numberposts' => -1, 
        'orderby'     => 'ID',
        'order'       => 'DESC',
        'post_status' => array('publish', 'pending', 'draft'), 
    ]);
    ?>

    <style>
        :root { 
            --res-primary: #d63638; 
            --res-dark: #1d2327;    
            --res-border: #ccd0d4; 
            --res-success: #46b450;
            --res-bg-soft: #fafafa;
        }

        .afd-dashboard { margin-top: 20px; max-width: 1200px; }
        
        /* Filter Bar Styling */
        .afd-filter-bar { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            background: #fff; 
            padding: 15px 20px; 
            border: 1px solid var(--res-border); 
            border-radius: 8px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,.02);
        }
        .afd-filter-group { display: flex; align-items: center; gap: 10px; }
        .afd-filter-group label { font-weight: 700; color: var(--res-dark); font-size: 13px; }
        .afd-filter-select { 
            border: 1px solid var(--res-border); 
            border-radius: 6px; 
            padding: 5px 30px 5px 12px; 
            min-width: 160px; 
            cursor: pointer;
            height: 38px;
        }

        #fd-items-table { border: 1px solid var(--res-border); border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.05); border-spacing: 0; width: 100%; }
        #fd-items-table thead th { background: var(--res-bg-soft); padding: 15px; font-weight: 700; color: #50575e; border-bottom: 2px solid #f0f0f1; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        #fd-items-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f1; }
        
        /* Toggle Switch */
        .fd-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
        .fd-switch input { opacity: 0; width: 0; height: 0; }
        .fd-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .fd-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .fd-slider { background-color: var(--res-success); }
        input:checked + .fd-slider:before { transform: translateX(18px); }

        .fd-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; margin: 2px; border: 1px solid transparent; }
        .fd-cat-badge { background: #fff9f9; color: var(--res-primary); border-color: #f5c2c2; }
        
        .fd-btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #dcdcde; background: #fff; color: #2c3338; }
        .fd-btn:hover { border-color: var(--res-primary); color: var(--res-primary); background: #fff9f9; }
        
        /* DataTables Customizations */
        .dataTables_wrapper .dataTables_filter { margin: 0; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--res-border); border-radius: 6px; padding: 8px 12px; width: 280px; margin: 0; outline: none; }
    </style>

    <div class="wrap afd-dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="margin:0; font-weight: 800; font-size: 24px; color: var(--res-dark);">Menu Dashboard</h1>
                <p style="color: #646970; margin: 5px 0 0;"><?php echo count($items); ?> items in catalog.</p>
            </div>
            <a href="?page=awesome_food_delivery&tab=items&sub=add" class="button button-primary" style="background:var(--res-primary); border:none; font-weight:700; padding: 8px 25px; height: auto; border-radius: 6px;">+ Add Menu Item</a>
        </div>

        <div class="afd-filter-bar">
            <div class="afd-filter-group">
                <label for="visibility-filter">Visibility Status:</label>
                <select id="visibility-filter" class="afd-filter-select">
                    <option value="">All Statuses</option>
                    <option value="Live">Live (Published)</option>
                    <option value="Hidden">Hidden (Pending/Draft)</option>
                </select>
            </div>
            <div style="margin-left: auto;" id="custom-search-container">
                </div>
        </div>

        <table id="fd-items-table" class="display nowrap">
            <thead>
                <tr>
                    <th width="80">Preview</th>
                    <th>Item Information</th>
                    <th>Categories</th>
                    <th width="100">Visibility</th>
                    <th width="100">Price</th>
                    <th width="240" style="text-align: right;">Actions</th>
                    <th style="display:none;">FilterKey</th> </tr>
            </thead>
            <tbody>
                <?php if($items): 
                    foreach($items as $item):
                        $price    = get_post_meta($item->ID, 'price', true);
                        $cats     = wp_get_post_terms($item->ID, 'food_category');
                        $is_active = ($item->post_status === 'publish');
                        $status_label = $is_active ? 'Live' : 'Hidden';
                ?>
                    <tr id="item-row-<?php echo $item->ID; ?>">
                        <td>
                            <?php if (has_post_thumbnail($item->ID)): ?>
                                <?php echo get_the_post_thumbnail($item->ID, [50, 50], ['class' => 'fd-item-img']); ?>
                            <?php else: ?>
                                <div class="fd-no-img"><span class="dashicons dashicons-format-image"></span></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="font-size:15px; color: var(--res-dark); display: block;"><?php echo esc_html($item->post_title); ?></strong>
                            <code style="background: #f0f0f1; color: #50575e; font-size: 10px; border-radius: 3px; padding: 1px 4px;">#<?php echo $item->ID; ?></code>
                        </td>
                        <td>
                            <?php if($cats): foreach($cats as $c): ?>
                                <span class="fd-badge fd-cat-badge"><?php echo esc_html($c->name); ?></span>
                            <?php endforeach; else: echo '<span style="color:#a7aaad;">Uncategorized</span>'; endif; ?>
                        </td>
                        <td>
                            <label class="fd-switch">
                                <input type="checkbox" class="fd-status-toggle" data-id="<?php echo $item->ID; ?>" <?php checked($is_active); ?>>
                                <span class="fd-slider"></span>
                            </label>
                        </td>
                        <td>
                            <strong style="font-size:16px; color: var(--res-primary);">
                                <?php echo number_format(floatval($price), 2, '.', ''); ?> €
                            </strong>
                        </td>
                        <td style="text-align: right;">
                            <a class="fd-btn" href="?page=awesome_food_delivery&tab=items&sub=edit&item=<?php echo $item->ID; ?>">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </a>
                            <a class="fd-btn" style="color:red;" onclick="return confirm('Delete item?')" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=fd_delete_item&item='.$item->ID), 'fd_delete_item_'.$item->ID); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                        <td style="display:none;"><?php echo $status_label; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($){
        if ($.fn.DataTable) {
            // 1. Initialize Table
            var table = $('#fd-items-table').DataTable({
                "pageLength": 15,
                "order": [[1, "asc"]],
                "dom": '<"top"f>rt<"bottom"ip><"clear">',
                "columnDefs": [ { "orderable": false, "targets": [0, 3, 5] } ],
                "language": { "search": "", "searchPlaceholder": "Search menu..." }
            });

            // 2. Move Search Box to our Filter Bar
            $('.dataTables_filter').appendTo('#custom-search-container');

            // 3. Visibility Filter Logic
            $('#visibility-filter').on('change', function(){
                var val = $(this).val();
                table.column(6).search(val).draw();
            });

            // 4. AJAX Toggle Logic
            $('.fd-status-toggle').on('change', function(){
                var $this = $(this);
                var $row = $this.closest('tr');
                var itemId = $this.data('id');
                var isActive = $this.is(':checked');
                
                $this.closest('.fd-switch').css('opacity', '0.5');

                $.post(ajaxurl, {
                    action: 'fd_toggle_item_status',
                    item_id: itemId,
                    status: isActive ? 'publish' : 'pending',
                    nonce: '<?php echo wp_create_nonce("fd_status_nonce"); ?>'
                }, function(res) {
                    $this.closest('.fd-switch').css('opacity', '1');
                    if(res.success) {
                        // Update hidden column so the filter stays accurate
                        table.cell($row, 6).data(isActive ? 'Live' : 'Hidden').draw(false);
                    } else {
                        alert('Error updating status.');
                        $this.prop('checked', !isActive);
                    }
                });
            });
        }
    });
    </script>
<?php
}