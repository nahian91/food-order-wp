<?php
if(!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# Enqueue Media + DataTable Scripts (Extras tab only)
--------------------------------------------------------------*/
add_action('admin_enqueue_scripts', function($hook){
    if($hook !== 'toplevel_page_awesome_food_delivery') return;
    if(!isset($_GET['tab']) || $_GET['tab']!=='extras') return;

    // Media uploader
    wp_enqueue_media();

    // DataTable
    wp_enqueue_style(
        'fd-datatable-css',
        'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css'
    );
    wp_enqueue_script(
        'fd-datatable-js',
        'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
        ['jquery'],
        null,
        true
    );

    // Media JS
    add_action('admin_footer', function(){
        ?>
        <script>
        jQuery(document).ready(function($){
            var file_frame;
            $('#fd_extra_file_button').on('click', function(e){
                e.preventDefault();
                if(file_frame){ file_frame.open(); return; }
                file_frame = wp.media({
                    title: 'Select or Upload File',
                    button: { text: 'Use this file' },
                    multiple: false
                });
                file_frame.on('select', function(){
                    var attachment = file_frame.state().get('selection').first().toJSON();
                    $('#fd_extra_file').val(attachment.id);
                    var thumb = attachment.sizes?.thumbnail?.url || attachment.url;
                    $('#fd_extra_file_preview').html('<img src="'+thumb+'" style="max-width:80px;">');
                });
                file_frame.open();
            });
        });
        </script>
        <?php
    });
});

/*--------------------------------------------------------------
# Extras Tab Navigation
--------------------------------------------------------------*/
function fd_extras_tab(){
    $sub_tabs = ['add'=>'Add Extra','all'=>'All Extras'];
    $active_sub = $_GET['sub'] ?? 'add';
    ?>
    <h2 class="nav-tab-wrapper">
        <?php foreach($sub_tabs as $k=>$label): ?>
            <a class="nav-tab <?php echo ($active_sub === $k?'nav-tab-active':''); ?>"
               href="?page=awesome_food_delivery&tab=extras&sub=<?php echo esc_attr($k); ?>">
               <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div style="margin-top:20px;">
        <?php
        switch($active_sub){
            case 'add': fd_add_extra_tab(); break;
            case 'all': fd_all_extras_tab(); break;
            case 'view': fd_view_extra_tab(intval($_GET['item'] ?? 0)); break;
        }
        ?>
    </div>
    <?php
}
