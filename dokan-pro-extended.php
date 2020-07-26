<?php
/**
  Plugin Name: Dokan Pro Extended
  Plugin URI: https://github.com/achchu93/dokan-pro-extended
  Description: An extension to customize Dokan Pro Plugin
  Version: 0.0.1
  Author: Ahamed Arshad
  Author URI: https://github.com/achchu93
 */



/**
 * Dependancies check
 */
$deps           = array( 'woocommerce/woocommerce.php', 'dokan-lite/dokan.php' );
$active_plugins = get_option( 'active_plugins', array() );

// bail if dependancies not found
if( count( array_diff( $deps, $active_plugins ) ) > 0 ) {
    return;
}

/**
 * Plugin file constant
 */
define( 'DPE_PLUGIN', __FILE__ );


// asset includes
include_once dirname( __FILE__ ) . "/includes/assets.php";

// ajax includes
if( defined( 'DOING_AJAX' ) ) {
    include_once dirname( __FILE__ ) . "/includes/ajax.php";
}

// admin includes
if( is_admin() ) {
    include_once dirname( __FILE__ ) . "/includes/admin.php";
}


/**
 * Adds subscription start date field
 */
function dpe_subscription_checkout_field( $fields ) {
    ?>
    <p class="form-row form-group form-row-wide" style="margin-top:90px;">
        <label for="dokan_subscription_start_date">Subscription Start Date<span class="required">*</span></label>
        <input 
            type="text" 
            class="input-text form-control" 
            name="dokan_subscription_start_date" 
            id="dokan_subscription_start_date" 
            required="required"
        />
    </p>
    <?php
}
add_action( 'dokan_seller_registration_field_after', 'dpe_subscription_checkout_field', 11 );


/**
 * Store user selected date on checkout process
 */
function dpe_save_subscription_start_date( $user_id, $dokan_settings ) {

	update_user_meta( 
		$user_id, 
		'dokan_subscription_start_date', 
		date( 'Y-m-d H:i:s', strtotime( $_POST['dokan_subscription_start_date'] ) )
	);
}
add_action( 'dokan_new_seller_created', 'dpe_save_subscription_start_date', 10, 2 );


/**
 * Override subscription start date by user entered date
 */
function dpe_extende_subscription_start_date( $vendor_id ) {

	$pack_id   =  get_user_meta( $vendor_id, 'product_package_id', true );
	$date_meta = get_user_meta( $vendor_id, 'dokan_subscription_start_date', true );

	if( !$pack_id || empty( $pack_id ) || empty( $date_meta ) ) {
		return;
	}

	$pack_id       = intval( $pack_id );
	$validitiy     = get_post_meta( $pack_id, '_pack_validity', true );

	if( empty( $validitiy ) ) {
		$validitiy = 'unlimited';
	}

	update_user_meta( $vendor_id, 'product_pack_startdate', date( 'Y-m-d H:i:s', strtotime( $date_meta ) ) );
	update_user_meta( $vendor_id, 'product_pack_enddate', date( 'Y-m-d H:i:s', strtotime( "+$validitiy days", strtotime( $date_meta ) ) ) );

}
add_action( 'dokan_vendor_purchased_subscription', 'dpe_extende_subscription_start_date' );


/**
 * Validate subscription start date
 */
function dpe_validate_subscription_start_date( $error ) {
    if ( is_checkout() ) {
        return $error;
    }

    if( empty( $_POST['dokan_subscription_start_date'] ) ) {
        return new WP_Error( 'subscription-start-date-error', 'Please enter a valid subscription start date' );
    }

    $date = new DateTime( $_POST['dokan_subscription_start_date'] );
    $now  = new DateTime();
    $next = (new DateTime())->add( date_interval_create_from_date_string( '+1 years' ) );
    
    if( !$date || !in_array( $date->format('Y'), array( $now->format('Y'), $next->format('Y') ) ) ) {
        return new WP_Error( 'subscription-start-date-error', 'Invalid subscription date' );
    }

    $restricted_days = dpe_get_restrcited_days_for_month( $date->format('Y'), $date->format('m') );
    
    if( count( $restricted_days ) && in_array( $date->format('Y-m-d'), $restricted_days ) ) {
        return new WP_Error( 'subscription-start-date-error', 'Restricted subscription date' );
    }

    return $error;
}
add_filter( 'woocommerce_registration_errors', 'dpe_validate_subscription_start_date' );


/**
 * Remove shopname field from validation process
 */
function dpe_remove_shopname_required_field( $fields ) {

    if( isset( $fields['shopname'] ) ) {
        unset( $fields['shopname'] );
    }
    return $fields;
}
add_filter( 'dokan_seller_registration_required_fields', 'dpe_remove_shopname_required_field' );


/**
 * Update seller data with user id and override it for later use
 */
function dpe_set_shop_data( $user_id, $data ) {

    wp_update_user( 
        array(
            'ID'            => $user_id,
            'user_nicename' => $user_id
        )
    );

    $_POST['shopname'] = $user_id;
    $_POST['shopurl']  = $user_id;

}
add_action( 'woocommerce_created_customer', 'dpe_set_shop_data', 5, 2 );

/**
 * Override registration form
 */
function dpe_override_registration_form( $template, $slug, $name ) {

    if( $slug === 'global/seller-registration-form' ) {
        $child_theme_file = get_stylesheet_directory() . '/dokan/global/seller-registration-form.php';
        if( !file_exists( $child_theme_file ) ) {
            $template = plugin_dir_path( DPE_PLUGIN ) . 'templates/seller-registration-form.php';
        }
    }
    return $template;
}
add_filter( 'dokan_get_template_part', 'dpe_override_registration_form', 10, 3 );


/**
 * Set product default data
 */
function dpe_update_dokan_added_product( $product_id, $post_data ) {

    update_post_meta( $product_id, '_manage_stock', 'yes' );
    update_post_meta( $product_id, '_stock', 1 );
    update_post_meta( $product_id, '_backorders', 'no' );
    update_post_meta( $product_id, '_low_stock_amount', 1 );
}
add_action( 'dokan_new_product_added', 'dpe_update_dokan_added_product', 10, 2 );


/**
 * Show `in someones cart` message 
 * if the items is in another users cart
 */
function dpe_in_someones_cart_to_loop() {

    global $wpdb;

    $product_id = get_the_ID();

    if( empty( $product_id ) || !$product_id ) {
        return;
    }

    $query   = 's:10:"product_id";i:'.$product_id;
    $user_id = get_current_user_id();
    $carts   = $wpdb->get_var(
        "SELECT COUNT(*) from {$wpdb->usermeta} WHERE meta_key = '_woocommerce_persistent_cart_1' and user_id != {$user_id} and meta_value like '%{$query}%'"
    );
    

    if( intval( $carts ) > 0 ) {
        echo '<div class="loop-in-someones-cart">In Someone\'s Cart</div>';
    }

}
add_action( 'woocommerce_before_shop_loop_item_title', 'dpe_in_someones_cart_to_loop' );


/**
 * Include subscription start date in vendor dashboard
 */
function dpe_show_subscription_start_date( $content ) {
    $subscription   = dokan()->vendor->get( get_current_user_id() )->subscription;
    if( $subscription ) {
        $starte_date_el = sprintf( 
            '<p class="pack-start-date" style="display:none;">Your package start date is <span>%s</span></p>', 
            date_i18n( 
                get_option( 'date_format' ), 
                strtotime( $subscription->get_pack_start_date() ) 
            ) 
        );
        $content .= $starte_date_el;
    }

    return $content;
}
add_filter( 'dokan_sub_shortcode', 'dpe_show_subscription_start_date' );


/**
 * Helper to get subscriptions count
 */
function dpe_get_subscriptions_count( $start, $end ) {

    global $wpdb;

    $query = $wpdb->prepare(
        "SELECT meta_value as s_date, COUNT(*) as s_count
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'product_pack_startdate' and CAST(meta_value AS DATE) between %s and %s
        GROUP BY meta_value",
        array( $start, $end )
    );
    return $wpdb->get_results( $query, ARRAY_A );
}


/**
 * Register vendoe shelf taxonomy
 */
function dpe_vendor_shelf_taxonomy() {
	register_taxonomy(
		'vendor_shelf',
		'user',
		array(
            'description' => 'Vendor Shelves which will be assigned per a vendor',
			'public' => true,
			'labels' => array(
				'name'		=> 'Vendor Shelves',
				'singular_name'	=> 'Vendor Shelf',
				'menu_name'	=> 'Vendor Shelves',
				'search_items'	=> 'Search Vendor Shelf',
				'popular_items' => 'Popular Vendor Shelves',
				'all_items'	=> 'All Vendor Shelves',
				'edit_item'	=> 'Edit Vendor Shelf',
				'update_item'	=> 'Update Vendor Shelf',
				'add_new_item'	=> 'Add New Vendor Shelf',
				'new_item_name'	=> 'New Vendor Shelf Name',
            ),
            'update_count_callback' => function() {
				return;
			}
		)
	);
}
add_action( 'init', 'dpe_vendor_shelf_taxonomy' );


/**
 * Assign available vendor shelf for vendor
 */
function dpe_update_vendor_shelf ( $user_id, $settings ) {
    global $wpdb;

    $occupied = $wpdb->get_col( "SELECT meta_value from {$wpdb->usermeta} WHERE meta_key = 'vendor_custom_product_id' and 1=1" );
    $exclude  = is_array( $occupied ) ? array_map( 'intval', $occupied ) : array();
    
    $terms = get_terms( array(
        'taxonomy'   => 'vendor_shelf',
        'hide_empty' => false,
        'exclude'    => $exclude
    ));
    
    if( is_array( $terms ) && count( $terms ) ) {
        $term = current( $terms );
        update_user_meta( $user_id, 'vendor_custom_product_id', $term->term_id );
    }
}
add_action( 'dokan_new_seller_created', 'dpe_update_vendor_shelf', 10, 2 );


/**
 * Override vendor add new product template
 */
function dpe_vendor_add_product_popup( $template, $slug, $name ) {

    $product_temps = array( 'products/tmpl-add-product-popup', 'products/new-product', 'products/new-product-single' );

    if( in_array( $slug, $product_temps ) ) {
        $child_theme_file = get_stylesheet_directory() . "/dokan/{$slug}.php";
        if( !file_exists( $child_theme_file ) ) {
            $path = explode( '/', $slug );
            $file  = $path[count($path) - 1]; 
            $template = plugin_dir_path( DPE_PLUGIN ) . "templates/{$file}.php";
        }
    }

    return $template;
}
add_filter( 'dokan_get_template_part', 'dpe_vendor_add_product_popup', 10, 3 );


/**
 * Helper to get restricted dates in a month
 */
function dpe_get_restrcited_days_for_month( $year, $month ) {

    $start = ( new DateTime() )->setDate( intval( $year ), intval( $month ), intval( 01 ) );
    $end   = ( new DateTime() )->setDate( intval( $year ), intval( $month ), intval( 31 ) );

    $key           = "sub_restricted_days_{$start->format('Y')}{$start->format('m')}";
    $days          = get_option( $key, array() );
    $subscriptions = dpe_get_subscriptions_count( $start->format('Y-m-d'), $end->format('Y-m-d') );

    $results = array();

    if( is_array( $days ) && is_array( $subscriptions ) ) {
        $filtered = array_filter(
            $days,
            function( $count, $date ) use ($subscriptions) {
                $s_date = array_filter( 
                    $subscriptions,
                    function( $subscription ) use ($date) {
                        return date( 'Y-m-d', intval( $date ) ) === date( 'Y-m-d', strtotime( $subscription['s_date'] ) );
                    }
                );
                return current( $s_date ) && !( intval( $count ) > intval( current( $s_date )['s_count'] ) );
            },
            ARRAY_FILTER_USE_BOTH
        );

        $results = array_map(
            function( $date ) {
                return date( 'Y-m-d', intval( $date ) );
            },
            array_keys( $filtered )
        );
    }

    return $results;
}