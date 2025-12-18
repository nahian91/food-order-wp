<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Customers Tab - CRM SaaS Design (Refactored)
 */
function fd_customers_tab() {
    $afon_currency = get_option( 'fd_currency', '€' );
    $afon_page_slug = 'awesome_food_delivery';

    // ----- SINGLE CUSTOMER VIEW -----
    if ( isset( $_GET['view'] ) ) :
        $afon_user_id = intval( $_GET['view'] );
        $afon_user    = get_userdata( $afon_user_id );
        
        if ( ! $afon_user ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'User not found.', 'text-domain' ) . '</p></div>';
            return;
        }

        // Performance Fix: Only get required fields
        $afon_orders = get_posts( [
            'post_type'   => 'food_order',
            'meta_key'    => 'customer_id',
            'meta_value'  => $afon_user_id,
            'numberposts' => -1,
            'fields'      => 'ids', // Get IDs first to minimize memory
        ] );
        
        $afon_total_spent = 0;
        foreach ( $afon_orders as $afon_order_id ) {
            $afon_total_spent += floatval( get_post_meta( $afon_order_id, 'total_price', true ) );
        }
        ?>

        <div class="wrap afon-wrap">
            <div class="afon-profile-header">
                <?php echo get_avatar( $afon_user_id, 80, '', '', [ 'class' => 'afon-avatar-big' ] ); ?>
                <div>
                    <h1><?php echo esc_html( $afon_user->display_name ); ?></h1>
                    <p class="afon-email-text"><?php echo esc_html( $afon_user->user_email ); ?></p>
                    <div class="afon-stats-mini">
                        <div class="afon-stat-item">
                            <span><?php esc_html_e( 'Total Orders', 'text-domain' ); ?></span>
                            <strong><?php echo count( $afon_orders ); ?></strong>
                        </div>
                        <div class="afon-stat-item">
                            <span><?php esc_html_e( 'Total Revenue', 'text-domain' ); ?></span>
                            <strong><?php echo esc_html( $afon_currency ) . esc_html( number_format( $afon_total_spent, 2 ) ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="afon-table-card">
                <div class="afon-table-header"><h3><?php esc_html_e( 'Order History', 'text-domain' ); ?></h3></div>
                <div class="afon-table-body">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="100"><?php esc_html_e( 'Order ID', 'text-domain' ); ?></th>
                                <th><?php esc_html_e( 'Items Ordered', 'text-domain' ); ?></th>
                                <th><?php esc_html_e( 'Grand Total', 'text-domain' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'text-domain' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'text-domain' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ( $afon_orders as $afon_order_id ) :
                                $afon_status = strtolower( get_post_meta( $afon_order_id, 'status', true ) ?: 'pending' );
                                $afon_total  = floatval( get_post_meta( $afon_order_id, 'total_price', true ) );
                                $afon_items  = get_post_meta( $afon_order_id, 'items', true ) ?: [];
                            ?>
                                <tr>
                                    <td><strong>#<?php echo (int) $afon_order_id; ?></strong></td>
                                    <td>
                                        <?php 
                                        if ( ! empty( $afon_items ) ) {
                                            foreach ( $afon_items as $afon_it ) {
                                                echo '<span class="afon-badge">' . esc_html( $afon_it['name'] ) . ' x' . (int) $afon_it['qty'] . '</span> ';
                                            }
                                        } else { 
                                            echo '-'; 
                                        }
                                        ?>
                                    </td>
                                    <td><strong style="color:#d63638;"><?php echo esc_html( $afon_currency ) . esc_html( number_format( $afon_total, 2 ) ); ?></strong></td>
                                    <td><span class="afon-badge afon-badge-<?php echo esc_attr( $afon_status ); ?>"><?php echo esc_html( ucfirst( $afon_status ) ); ?></span></td>
                                    <td class="afon-email-text"><?php echo esc_html( get_the_date( 'M j, Y H:i', $afon_order_id ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $afon_page_slug . '&tab=customers' ) ); ?>" class="button button-large"><?php esc_html_e( '← Back to Directory', 'text-domain' ); ?></a></p>
        </div>
        <?php
        return;
    endif;

    // ----- DEFAULT: LIST ALL CUSTOMERS -----
    $afon_all_users = get_users( [ 'number' => 50 ] ); // Added limit for performance
    ?>

    <div class="wrap afon-wrap">
        <div class="afon-flex-header">
            <h1><?php esc_html_e( 'Customer Directory', 'text-domain' ); ?></h1>
        </div>

        <div class="afon-table-card">
            <div class="afon-table-body">
                <table id="afon-users-directory-table" class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="80"><?php esc_html_e( 'ID', 'text-domain' ); ?></th>
                            <th><?php esc_html_e( 'Customer', 'text-domain' ); ?></th>
                            <th><?php esc_html_e( 'Contact Email', 'text-domain' ); ?></th>
                            <th><?php esc_html_e( 'Engagement', 'text-domain' ); ?></th>
                            <th width="140" class="afon-text-right"><?php esc_html_e( 'Management', 'text-domain' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ( $afon_all_users as $afon_u ) :
                            // Optimized count check
                            $afon_order_count = count( get_posts( [
                                'post_type'   => 'food_order', 
                                'meta_key'    => 'customer_id', 
                                'meta_value'  => $afon_u->ID, 
                                'numberposts' => -1,
                                'fields'      => 'ids' 
                            ] ) );
                            
                            $afon_view_url = admin_url( 'admin.php?page=' . $afon_page_slug . '&tab=customers&view=' . $afon_u->ID );
                        ?>
                        <tr>
                            <td><code class="afon-text-muted">#<?php echo (int) $afon_u->ID; ?></code></td>
                            <td>
                                <div class="afon-user-row">
                                    <?php echo get_avatar( $afon_u->ID, 32, '', '', [ 'class' => 'afon-avatar-sm' ] ); ?>
                                    <div>
                                        <div class="afon-user-name"><?php echo esc_html( $afon_u->display_name ); ?></div>
                                        <?php if ( $afon_order_count > 5 ) : ?>
                                            <span class="afon-text-muted"><span class="dashicons dashicons-awards afon-loyal-icon"></span> <?php esc_html_e( 'VIP Customer', 'text-domain' ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="afon-email-text"><?php echo esc_html( $afon_u->user_email ); ?></td>
                            <td>
                                <span class="afon-badge">
                                    <?php printf( esc_html__( '%d Orders', 'text-domain' ), (int) $afon_order_count ); ?>
                                </span>
                            </td>
                            <td class="afon-text-right">
                                <a href="<?php echo esc_url( $afon_view_url ); ?>" class="afon-btn-action">
                                    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View Details', 'text-domain' ); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
}