<?php
/**
 * Handles admin hooks
 */


 /**
 * Adds custom product id to user fields
 */
function dpe_vendor_custom_product_id( $user ) {
    global $wpdb;

    $occupied = $wpdb->get_col( "SELECT meta_value from {$wpdb->usermeta} WHERE meta_key = 'vendor_custom_product_id' and user_id != $user->ID and 1=1" );
    $exclude  = is_array( $occupied ) ? array_map( 'intval', $occupied ) : array();
    
    $terms = get_terms( array(
        'taxonomy'   => 'vendor_shelf',
        'hide_empty' => false,
        'exclude'    => $exclude
    ));
    $shelves = (array) get_user_meta( $user->ID, 'vendor_custom_product_id', true );
    ?>
    <tr>
        <td>
            <h3>Vendor Shelf Name & Location</h3>
        </td>
    </tr>
    <tr>
        <th><label for="">Shelf Address (Row.Number.Level)</label></th>
        <td>
            <select name="vendor_custom_product_id[]" 
                id="vendor_custom_product_id" 
                class="wc-enhanced-select" multiple="true" 
                data-placeholder="Select ID"
                style="min-width:350px;"
            >
                <?php foreach( $terms as $term ): ?>
                <option value="<?php echo $term->term_id; ?>" <?php selected( in_array( $term->term_id, $shelves ) ) ?> >
                    <?php echo $term->name; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php
}
add_action( 'dokan_seller_meta_fields', 'dpe_vendor_custom_product_id', 11 );


/**
 * Save custom product id to user meta
 */
function dpe_save_vendor_custom_product_id( $user_id ) {
    $product_id = !empty( $_POST['vendor_custom_product_id'] ) ? $_POST['vendor_custom_product_id'] : array();
    update_user_meta( $user_id, 'vendor_custom_product_id', $product_id );
}
add_action( 'dokan_process_seller_meta_fields', 'dpe_save_vendor_custom_product_id' );


/**
 * Replace order's product id with vendor custom product ID
 */
function dpe_order_line_item_product_id( $item_id, $item, $product ) {

    if( $item->get_type() !== 'line_item' ) {
        return;
    }

    $author_id = get_post_field( 'post_author', $product->get_id() );
    if( !dokan_is_user_seller( $author_id ) ) {
        return;
    }

    $shelves = implode( ' - ', dpe_get_vendor_shelves( $author_id ) );

    printf( '<div class="vendor-product-id">Product ID: %s %s</span>', $product->get_id(), !empty( $shelves ) ? "- $shelves" : "" );
}
add_action( 'woocommerce_before_order_itemmeta', 'dpe_order_line_item_product_id', 10, 3 );


/**
 * Admin product table product id replace with custom product id
 */
function dpe_product_list_table_custom_id( $actions, $post ) {

    if( $post->post_type === 'product' && dokan_is_user_seller( $post->post_author ) ) {
        $shelves = implode( ' - ', dpe_get_vendor_shelves( $post->post_author ) );

        if( !empty( $shelves ) ) {
            $actions['id'] = sprintf( 'ID: %s', "{$post->ID} - {$shelves}" );
        }
    }

    return $actions;
}
add_filter( 'post_row_actions', 'dpe_product_list_table_custom_id', 101, 2 );


/**
 * Sub admin menu page for subscription calendar
 */
function dpe_admin_subscription_calendar() {
    add_submenu_page( 
        'dokan', 
        'Subscription calendar', 
        'Subscription calendar', 
        'manage_options', 
        'subscription-calendar',
        function(){
            echo '<div id="subscription_calendar"></div>';
        }
    );
}
add_action( 'admin_menu', 'dpe_admin_subscription_calendar', 81 );


/**
 * Show vendor shelves under dokan menu
 */
function dpe_admin_vendor_shelves() {
    $taxonomy = get_taxonomy( 'vendor_shelf' );
    add_submenu_page( 
        'dokan', 
        $taxonomy->labels->menu_name, 
        $taxonomy->labels->menu_name,
        'manage_options', 
        'edit-tags.php?taxonomy=' . $taxonomy->name
    );
}
add_action( 'admin_menu', 'dpe_admin_vendor_shelves', 82 );


/**
 * Make dokan menu item active on vendor shelves page
 */
function dpe_set_vendor_shelf_submenu_active( $submenu_file ) {
    global $parent_file;
	if( 'edit-tags.php?taxonomy=vendor_shelf' == $submenu_file ) {
		$parent_file = 'dokan';
	}
	return $submenu_file;
}
add_filter( 'submenu_file', 'dpe_set_vendor_shelf_submenu_active' );


/**
 * Removes dokan subscription data 
 * from admin user profile page to override
 */
add_action( 'dokan_seller_meta_fields', function(){
    remove_action( 'dokan_seller_meta_fields', array( 'DPS_Admin', 'add_subscription_packs_dropdown' ), 10 );
}, 1 );


/**
 * Add subscription pack fields
 */
function dpe_add_subscription_packs_dropdown ( $user ) {
    $users_assigned_pack       = get_user_meta( $user->ID, 'product_package_id', true );
    $vendor_allowed_categories = get_user_meta( $user->ID, 'vendor_allowed_categories', true );

    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'product_pack',
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_enable_recurring_payment',
                'value' => 'no',
            )
        )
    );
    $sub_packs = get_posts( apply_filters( 'dps_get_non_recurring_pack_arg', $args ) );
    ?>
    <tr>
        <td>
            <h3><?php _e( 'Dokan Subscription', 'dokan' ); ?> </h3>
        </td>
    </tr>

    <?php if ( $users_assigned_pack ) : ?>
        <tr>
            <td><?php _e( 'Currently Activated Pack', 'dokan' ); ?></td>
            <td> <?php echo get_the_title( $users_assigned_pack ); ?> </td>
        </tr>
        <tr>
            <td><?php _e( 'Start Date :' ) ;?></td>
            <td>
                <input 
                    type="text" 
                    name="product_pack_startdate"
                    id="product_pack_startdate" 
                    value="<?php echo date( get_option( 'date_format' ), strtotime( get_user_meta( $user->ID, 'product_pack_startdate', true ) ) ); ?>"
                    readonly
                />
            </td>
        </tr>
        <tr>
            <td><?php _e( 'End Date :' ) ;?></td>
            <td>
                <?php if ( 'unlimited' === get_user_meta( $user->ID, 'product_pack_enddate', true ) ) {
                    printf( __( 'Lifetime package.', 'dokan' ) );
                } else {
                    ?>
                    <input 
                        type="text"
                        name="product_pack_enddate"
                        id="product_pack_enddate"
                        value="<?php echo date( get_option( 'date_format' ), strtotime( get_user_meta( $user->ID, 'product_pack_enddate', true ) ) ); ?>"
                        readonly
                    />
                <?php } ?>
            </td>
        </tr>
    <?php endif; ?>

    <tr>
            <?php if ( $users_assigned_pack  && get_user_meta( $user->ID, '_customer_recurring_subscription', true ) == 'active' ) : ?>
            <td colspan="2"><?php  _e( '<i>This user already has recurring pack assigned. Are you sure to assign a new normal pack to the user? If you do so, the existing recurring plan will be replaced with the new one<i>', 'dokan' ); ?></td>
        <?php endif; ?>
    </tr>

    <tr>
        <td><?php _e( 'Allowed categories', 'dokan' ); ?></td>
        <td>
            <?php
                $selected_cat = ! empty( $vendor_allowed_categories ) ? $vendor_allowed_categories : get_post_meta( $users_assigned_pack, '_vendor_allowed_categories', true );
                echo '<select multiple="multiple" data-placeholder=" '. __( 'Select categories&hellip;', 'dokan' ) .'" class="wc-enhanced-select" id="vendor_allowed_categories" name="vendor_allowed_categories[]" style="width: 350px;">';
                $r = array();
                $r['pad_counts']    = 1;
                $r['hierarchical']  = 1;
                $r['hide_empty']    = 0;
                $r['value']         = 'id';
                $r['orderby']       = 'name';
                $r['selected']      = ! empty( $selected_cat ) ? array_map( 'absint', $selected_cat ) : '';

                $categories = get_terms( 'product_cat', $r );

                include_once( WC()->plugin_path() . '/includes/walkers/class-product-cat-dropdown-walker.php' );

                echo wc_walk_category_dropdown_tree( $categories, 0, $r );
                echo '</select>';
            ?>
            <p class="description"><?php _e( 'You can override allowed categories for this user. If empty then the predefined category for this pack will be selected', 'dokan' ); ?></p>
        </td>
    </tr>

    <tr class="dps_assign_pack">
        <td><?php _e( 'Assign Subscription Pack', 'wedevs' ); ?></td>
        <td>
            <select name="_dokan_user_assigned_sub_pack">
                <option value="" <?php selected( $users_assigned_pack, '' ); ?>><?php _e( '-- Select a pack --', 'dokan' ); ?></option>
                <?php foreach ( $sub_packs as $pack ) : ?>
                    <option value="<?php echo $pack->ID;?>" <?php selected( $users_assigned_pack, $pack->ID ); ?>><?php echo $pack->post_title; ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e( 'You can only assign non-recurring packs', 'dokan' ); ?></p>
        </td>
    </tr>
    <style>
        #ui-datepicker-div {
            z-index: 999999 !important;
        }
    </style>
    <script type="text/javascript">
        jQuery(document).ready( function($) {
            $('#product_pack_startdate, #product_pack_enddate').each( function() {
                $(this).datepicker({
                    minDate: $(this).is('#product_pack_enddate') ?  getMinEndDate() : new Date(),
                    maxDate: $(this).is('#product_pack_startdate') ? getMaxStartDate(): null,
                    onSelect: function( date, instance ) {
                        if( instance.id === 'product_pack_startdate' ) {
                            $('#product_pack_enddate').datepicker( 'option', 'minDate', getMinEndDate() );
                        }else{
                            $('#product_pack_startdate').datepicker( 'option', 'maxDate', getMaxStartDate() );
                        }
                    },
                    beforeShow: function() {
                        if( $(this).is('#product_pack_startdate') ) {
                            $('#product_pack_enddate').datepicker( 'option', 'minDate', getMinEndDate() );
                        }else{
                            $('#product_pack_startdate').datepicker( 'option', 'maxDate', getMaxStartDate() );
                        }
                    }
                });

                $(this).datepicker('refresh');
            });

            function getMinEndDate(){
                var startDateEl = $('#product_pack_startdate');
                var startDate   = startDateEl.datepicker('getDate');

                startDate.setDate( startDate.getDate() + 1 );

                return startDate;
            }

            function getMaxStartDate(){
                var endDateEl = $('#product_pack_enddate');
                var endDate   = endDateEl.length ? endDateEl.datepicker('getDate') : '';

                return endDate;
            }
        });
    </script>
<?php
}
add_action( 'dokan_seller_meta_fields', 'dpe_add_subscription_packs_dropdown' );


/**
 * Update vendor subscription pack start date
 */
function dpe_save_pack_start_date( $user_id ) {
    if( !empty( $_POST['product_pack_startdate'] ) ) {
        update_user_meta( $user_id, 'product_pack_startdate', date( 'Y-m-d H:i:s', strtotime( $_POST['product_pack_startdate'] ) ) );
    }

    if( !empty( $_POST['product_pack_enddate'] ) ) {
        update_user_meta( $user_id, 'product_pack_enddate', date( 'Y-m-d H:i:s', strtotime( $_POST['product_pack_enddate'] ) ) );
    }
}
add_action( 'dokan_process_seller_meta_fields', 'dpe_save_pack_start_date', 15 );