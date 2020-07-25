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
    $custom_product_id = get_user_meta( $user->ID, 'vendor_custom_product_id', true );
    ?>
    <tr>
        <td>
            <h3>Vendor product ID</h3>
        </td>
    </tr>
    <tr>
        <th><label for="">Custom product ID</label></th>
        <td>
            <select name="vendor_custom_product_id" id="vendor_custom_product_id">
                <option value="">Select ID</option>
                <?php foreach( $terms as $term ): ?>
                <option value="<?php echo $term->term_id; ?>" <?php selected( intval( $custom_product_id ), $term->term_id, true ) ?> >
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
    $product_id = !empty( $_POST['vendor_custom_product_id'] ) ? $_POST['vendor_custom_product_id'] : "";
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

    $custom_product_id = get_user_meta( $author_id, 'vendor_custom_product_id', true );
    if( empty( $custom_product_id ) ) {
        return;
    }

    printf( '<div class="vendor-product-id">Product ID: %s-%s</span>', $product->get_id(), $custom_product_id );
}
add_action( 'woocommerce_before_order_itemmeta', 'dpe_order_line_item_product_id', 10, 3 );


/**
 * Admin product table product id replace with custom product id
 */
function dpe_product_list_table_custom_id( $actions, $post ) {

    if( $post->post_type === 'product' && dokan_is_user_seller( $post->post_author ) ) {
        $custom_id = get_user_meta( $post->post_author, 'vendor_custom_product_id', true );

        if( !empty( $custom_id ) ) {
            $actions['id'] = sprintf( 'ID: %s', "{$post->ID}-$custom_id" );
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