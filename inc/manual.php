<?php
/**
 * MASTER POS TERMINAL - ELITE STABLE EDITION
 * Fix: Live recalculation on Mode Switch (Delivery/Collection)
 * Prefix: afd-
 */

if ( ! function_exists( 'fd_manual_order_tab' ) ) {
    function fd_manual_order_tab() {
        $currency = '£';
        
        // 1. SETTINGS SYNC
        $config = [
            'srv_fee'       => floatval( get_option( 'afd_service_charge', '0.50' ) ),
            'del_fee'       => floatval( get_option( 'afd_delivery_charge', '1.00' ) ),
            'bag_fee'       => floatval( get_option( 'afd_bag_charge', '0.10' ) ),
            'del_disc_pct'  => floatval( get_option( 'afd_delivery_discount_percent', '0' ) ),
            'coll_disc_pct' => floatval( get_option( 'afd_collection_discount_percent', '0' ) ),
        ];

        // 2. PRODUCT & VARIANT VAULT
        $items_vault = [];
        $posts = get_posts([
            'post_type'      => 'food_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ]);

        if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
            foreach ( $posts as $p ) {
                $variants = get_post_meta( $p->ID, 'item_variants', true );
                if ( ! empty( $variants ) && is_array( $variants ) ) {
                    foreach ( $variants as $v ) {
                        $items_vault[] = [
                            'name'  => esc_attr( $p->post_title ) . ' - ' . esc_attr( $v['name'] ),
                            'price' => floatval( $v['price'] )
                        ];
                    }
                } else {
                    $items_vault[] = [
                        'name'  => esc_attr( $p->post_title ),
                        'price' => floatval( get_post_meta( $p->ID, 'price', true ) ?: 0 )
                    ];
                }
            }
        }
        ?>

        <div class="afd-pos-wrapper">
            <div class="afd-header-glass">
                <div class="afd-brand-meta">
                    <h1>Manual POS Terminal</h1>
                    <p>Live Inventory & Fulfillment Sync</p>
                </div>
                <div class="afd-badge-live">System Active</div>
            </div>

            <form id="afd-master-pos-form">
                <?php wp_nonce_field( 'afd_pos_action', 'afd_pos_nonce' ); ?>
                
                <div class="afd-layout-grid">
                    <div class="afd-entry-zone">
                        
                        <div class="afd-card-bento">
                            <div class="afd-card-label">Order Details</div>
                            <div class="afd-row-2">
                                <div class="afd-group"><label>Full Name</label><input type="text" name="c_name" required></div>
                                <div class="afd-group"><label>Phone Number</label><input type="text" name="c_phone" required></div>
                            </div>
                            
                            <div class="afd-sub-divider">Address Logistics</div>
                            <div class="afd-row-3 mt-10">
                                <div class="afd-group"><label>Flat/Suite</label><input type="text" name="a_flat"></div>
                                <div class="afd-group"><label>Building</label><input type="text" name="a_build"></div>
                                <div class="afd-group"><label>Door No.</label><input type="text" name="a_door"></div>
                            </div>

                            <div class="afd-row-2 mt-15">
                                <div class="afd-group"><label>Road Name</label><input type="text" name="a_road"></div>
                                <div class="afd-group"><label>Postcode</label><input type="text" name="a_zip"></div>
                            </div>

                            <div class="afd-sub-divider">Special Instructions</div>
                            <div class="afd-row-2 mt-10">
                                <div class="afd-group"><label>Kitchen Instructions</label><textarea name="n_kit" rows="2"></textarea></div>
                                <div class="afd-group"><label>Delivery Instructions</label><textarea name="n_del" rows="2"></textarea></div>
                            </div>

                            <div class="afd-row-1 mt-15">
                                <div class="afd-group">
                                    <label>Fulfillment Type</label>
                                    <select id="afd-mode-toggle" name="o_type">
                                        <option value="delivery" selected>🚀 Delivery</option>
                                        <option value="collection">🛍️ Collection</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="afd-card-bento mt-25">
                            <div class="afd-flex-header">
                                <div class="afd-card-label">Order Items</div>
                                <button type="button" id="afd-add-item" class="afd-btn-pill">+ Add Product</button>
                            </div>
                            
                            <div class="afd-table-scroll">
                                <table class="afd-table-pos">
                                    <thead>
                                        <tr>
                                            <th>Item Search - Variant</th>
                                            <th width="80">Qty</th>
                                            <th width="100">Unit Price</th>
                                            <th width="100">Total</th>
                                            <th width="40"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="afd-item-root"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="afd-receipt-zone">
                        <div class="afd-sticky-receipt">
                            <div class="afd-card-label">Live Calculation</div>
                            
                            <div id="afd-receipt-list" class="afd-receipt-list">
                                <div class="afd-empty-state">No items added yet</div>
                            </div>

                            <div class="afd-math-stack">
                                <div class="math-line"><span>Subtotal</span><span><?php echo $currency; ?><span id="m-sub">0.00</span></span></div>
                                <div class="math-line text-success"><span>Discount</span><span>-<?php echo $currency; ?><span id="m-disc">0.00</span></span></div>
                                <div class="math-sep"></div>
                                <div class="math-line text-bold"><span>Order Total</span><span><?php echo $currency; ?><span id="m-order">0.00</span></span></div>
                                <div class="math-line"><span>Service Charge</span><span><?php echo $currency; ?><span id="m-srv"><?php echo number_format($config['srv_fee'], 2); ?></span></span></div>
                                <div class="math-line" id="del-fee-row"><span>Delivery Fee</span><span><?php echo $currency; ?><span id="m-del"><?php echo number_format($config['del_fee'], 2); ?></span></span></div>
                                <div class="math-line"><span>Bag Charge</span><span><?php echo $currency; ?><span id="m-bag"><?php echo number_format($config['bag_fee'], 2); ?></span></span></div>
                                
                                <div class="afd-total-card mt-20">
                                    <p>Grand Total Due</p>
                                    <h3><?php echo $currency; ?><span id="m-grand">0.00</span></h3>
                                </div>

                                <button type="submit" class="afd-btn-finalize">Place Manual Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <style>
            .afd-pos-wrapper { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 30px; color: #334155; }
            .afd-header-glass { background: #fff; border: 1px solid #e2e8f0; padding: 20px 35px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
            .afd-header-glass h1 { margin: 0; font-size: 24px; font-weight: 900; color: #0f172a; }
            .afd-badge-live { background: #f0fdf4; color: #166534; padding: 6px 15px; border-radius: 50px; font-size: 11px; font-weight: 800; border: 1px solid #bbf7d0; }
            
            .afd-layout-grid { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }
            .afd-card-bento { background: #fff; border-radius: 24px; padding: 30px; border: 1px solid #e2e8f0; }
            .afd-card-label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 20px; letter-spacing: 1px; }
            .afd-sub-divider { font-size: 10px; font-weight: 800; color: #cbd5e1; text-transform: uppercase; margin: 30px 0 10px; display: flex; align-items: center; gap: 10px; }
            .afd-sub-divider::after { content: ""; flex: 1; height: 1px; background: #f1f5f9; }
            
            .afd-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .afd-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
            .afd-group label { display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px; color: #475569; }
            .afd-group input, .afd-group select, .afd-group textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #fafafa; }
            
            .afd-table-pos { width: 100%; border-spacing: 0 10px; border-collapse: separate; }
            .afd-table-pos th { text-align: left; font-size: 11px; color: #94a3b8; padding: 0 15px; }
            .afd-table-pos td { background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-style: solid none; }
            .afd-table-pos td:first-child { border-left: 1px solid #e2e8f0; border-radius: 14px 0 0 14px; }
            .afd-table-pos td:last-child { border-right: 1px solid #e2e8f0; border-radius: 0 14px 14px 0; }
            
            .afd-search-rel { position: relative; }
            .afd-search-input { width: 100%; border: none !important; background: transparent !important; font-weight: 700; color: #6366f1; outline: none; }
            .afd-results { position: absolute; background: #fff; width: 100%; border-radius: 12px; z-index: 99; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: none; max-height: 200px; overflow-y: auto; margin-top: 10px; }
            .afd-res-item { padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
            .afd-res-item:hover { background: #6366f1; color: #fff; }
            
            .afd-sticky-receipt { background: #fff; border-radius: 24px; padding: 30px; border: 1px solid #e2e8f0; position: sticky; top: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
            .afd-receipt-list { min-height: 100px; max-height: 300px; overflow-y: auto; margin-bottom: 25px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 20px; }
            .afd-empty-state { text-align: center; color: #cbd5e1; padding: 40px 0; font-style: italic; font-size: 13px; }
            
            .math-line { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px; color: #64748b; }
            .text-success { color: #10b981; font-weight: 700; }
            .text-bold { color: #0f172a; font-weight: 800; font-size: 16px; }
            .math-sep { border-top: 1px solid #f1f5f9; margin: 15px 0; }
            
            .afd-total-card { background: #0f172a; color: #fff; padding: 25px; border-radius: 20px; text-align: center; }
            .afd-total-card p { font-size: 11px; text-transform: uppercase; opacity: 0.6; letter-spacing: 1px; margin-bottom: 5px; }
            .afd-total-card h3 { font-size: 38px; font-weight: 900; margin: 0; letter-spacing: -1px; }
            
            .afd-btn-finalize { width: 100%; background: #6366f1; color: #fff; border: none; padding: 20px; border-radius: 16px; font-weight: 800; font-size: 16px; cursor: pointer; margin-top: 25px; box-shadow: 0 10px 15px -3px rgba(99,102,241,0.3); transition: 0.2s; }
            .afd-btn-finalize:hover { transform: translateY(-2px); background: #4f46e5; }
            .afd-btn-pill { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 18px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; }
            .mt-15 { margin-top: 15px; } .mt-25 { margin-top: 25px; } .mt-10 { margin-top: 10px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            const vault = <?php echo json_encode( $items_vault ); ?>;
            const config = <?php echo json_encode( $config ); ?>;

            const addRow = () => {
                $('#afd-item-root').append(`
                    <tr class="afd-item-row">
                        <td><div class="afd-search-rel"><input type="text" class="afd-search-input" placeholder="Search item or variant..." autocomplete="off"><div class="afd-results"></div></div></td>
                        <td><input type="number" class="r-qty" value="1" min="1" style="width:100%; border:none; background:transparent; font-weight:900; text-align:center;"></td>
                        <td><input type="number" class="r-prc" value="0.00" step="0.01" style="width:100%; border:none; background:transparent; font-weight:700;"></td>
                        <td><strong><span class="r-total">0.00</span></strong></td>
                        <td><span class="dashicons dashicons-trash r-del" style="color:#ef4444; cursor:pointer;"></span></td>
                    </tr>
                `);
            };
            addRow();

            $(document).on('keyup', '.afd-search-input', function() {
                let s = $(this).val().toLowerCase(), $r = $(this).siblings('.afd-results');
                if (s.length < 2) return $r.hide();
                let matches = vault.filter(i => i.name.toLowerCase().includes(s));
                if (matches.length) {
                    $r.empty().show();
                    matches.forEach(m => $r.append(`<div class="afd-res-item" data-p="${m.price}" data-n="${m.name}">${m.name} <strong>${m.price.toFixed(2)}</strong></div>`));
                } else { $r.hide(); }
            });

            $(document).on('click', '.afd-res-item', function() {
                let $row = $(this).closest('.afd-item-row'), d = $(this).data();
                $row.find('.afd-search-input').val(d.n);
                $row.find('.r-prc').val(d.p.toFixed(2));
                $(this).parent().hide();
                runMath();
            });

            const runMath = () => {
                let sub = 0, html = '', type = $('#afd-mode-toggle').val();

                $('.afd-item-row').each(function() {
                    let n = $(this).find('.afd-search-input').val(), 
                        q = parseFloat($(this).find('.r-qty').val()) || 0, 
                        p = parseFloat($(this).find('.r-prc').val()) || 0, 
                        total = q * p;

                    $(this).find('.r-total').text(total.toFixed(2));
                    if (n) { 
                        sub += total; 
                        html += `<div class="math-line" style="color:#0f172a;"><span>${q}x ${n}</span><strong>${total.toFixed(2)}</strong></div>`; 
                    }
                });

                // DISCOUNT LOGIC
                let pct = (type === 'delivery') ? config.del_disc_pct : config.coll_disc_pct;
                let discountVal = (sub * pct) / 100;
                let afterDisc = sub - discountVal;
                
                // FEE LOGIC
                let activeDelFee = (type === 'delivery') ? config.del_fee : 0;
                if(type === 'collection') { $('#del-fee-row').css('opacity', '0.3'); } else { $('#del-fee-row').css('opacity', '1'); }

                let grand = afterDisc + config.srv_fee + activeDelFee + config.bag_fee;

                // UI UPDATE
                $('#afd-receipt-list').html(html || '<div class="afd-empty-state">No items added yet</div>');
                $('#m-sub').text(sub.toFixed(2));
                $('#m-disc').text(discountVal.toFixed(2));
                $('#m-order').text(afterDisc.toFixed(2));
                $('#m-del').text(activeDelFee.toFixed(2));
                $('#m-grand').text(grand.toFixed(2));
            };

            // Recalculate on any input change
            $(document).on('input', '.r-qty, .r-prc', runMath);
            // RE-TRIGGER ON MODE CHANGE (Collection vs Delivery)
            $(document).on('change', '#afd-mode-toggle', runMath);
            
            $(document).on('click', '#afd-add-item', addRow);
            $(document).on('click', '.r-del', function() { $(this).closest('tr').remove(); runMath(); });
            $(document).on('click', e => { if (!$(e.target).closest('.afd-search-rel').length) $('.afd-results').hide(); });
        });
        </script>
        <?php
    }
}