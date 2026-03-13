jQuery(document).ready(function($) {

    // Helper function to safely initialize DataTables
    function initAfonDataTable(selector, options) {
        var $target = $(selector);
        if ($target.length) {
            // If already initialized, destroy or return
            if ($.fn.DataTable.isDataTable(selector)) {
                return; 
            }
            $target.DataTable($.extend({
                retrieve: true,
                language: {
                    search: "",
                    paginate: { next: '→', previous: '←' }
                },
                dom: '<"top"f>rt<"bottom"ip><"clear">'
            }, options));
        }
    }

    /* --- 1. Initialize All Tables --- */

    initAfonDataTable('#afon-users-directory-table', {
        pageLength: 20,
        language: { searchPlaceholder: "Search by name or email..." }
    });

    initAfonDataTable('#afon-customer-orders-table', { 
        pageLength: 10, 
        dom: 'rtip' 
    });

    initAfonDataTable('#afon-reports-table', {
        pageLength: 10,
        language: { searchPlaceholder: "Search transactions..." }
    });

    initAfonDataTable('#afon-extras-directory-table', {
        pageLength: 10,
        language: { searchPlaceholder: "Search toppings..." },
        columnDefs: [{ orderable: false, targets: [0, 4] }] // Photo and Management
    });


    /* --- 2. WP Media Uploader for Extra Icons --- */

    var afon_media_frame;
    $('#afon-extra-dropzone').on('click', function(e) {
        e.preventDefault();
        if (afon_media_frame) {
            afon_media_frame.open();
            return;
        }

        afon_media_frame = wp.media({
            title: 'Select Extra Icon',
            button: { text: 'Use Icon' },
            multiple: false
        });

        afon_media_frame.on('select', function() {
            var attachment = afon_media_frame.state().get('selection').first().toJSON();
            $('#afon-extra-file-id').val(attachment.id);
            var thumbUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
            $('#afon-extra-preview').html(
                '<img src="' + thumbUrl + '"><p class="afon-upload-text">Change image</p>'
            );
        });
        afon_media_frame.open();
    });


    /* --- 3. Real-time Sidebar Summary Updates --- */

    $('input[name="afon_extra_price"]').on('input', function() {
        var price = parseFloat($(this).val() || 0).toFixed(2);
        $('#afon-live-price').text(price + ' £');
    });

    $('input[name="afon_extra_qty"]').on('input', function() {
        var qty = parseInt($(this).val() || 0);
        var status = qty > 0 ? 'Available' : 'No Stock';
        var color = qty > 0 ? '#1d2327' : '#d63638';
        
        $('#afon-live-status').text(status).css('color', color);
    });

    // Shared Data Table and Media Uploader logic as established in previous steps...
    
    // Optional: Log View page visits for analytics
    if ($('.afon-view-header').length) {
        console.log("Restaurant Red UI: Viewing resource details.");
    }

    var $afonOrdersTable = $('#afon-orders-table');

    if ($afonOrdersTable.length) {
        // Prevent reinitialization error
        if (!$.fn.DataTable.isDataTable('#afon-orders-table')) {
            $afonOrdersTable.DataTable({
                retrieve: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: "",
                    searchPlaceholder: "Search orders...",
                    paginate: { next: '→', previous: '←' }
                },
                dom: '<"top"f>rt<"bottom"ip><"clear">',
                columnDefs: [{ orderable: false, targets: [2, 5] }]
            });
        }
    }

    // 4. Order Calculation Engine
    function afon_recalculate_order() {
        let afon_total = 0;
        $('.afon-item-row-edit').each(function() {
            let afon_q = parseFloat($(this).find('.afon-qty-trigger').val()) || 0;
            let afon_p = parseFloat($(this).find('.afon-price-trigger').val()) || 0;
            let afon_sub = afon_q * afon_p;
            
            $(this).find('.afon-sub-val').text(afon_sub.toFixed(2));
            afon_total += afon_sub;
        });
        $('.afon-total-input').val(afon_total.toFixed(2));
    }

    $(document).on('input', '.afon-qty-trigger', function() {
        afon_recalculate_order();
    });

    
});