<?php
/**
  Plugin Name: Dokan Pro Extended
  Plugin URI: 
  Description: An extension to customize Dokan Pro Plugin
  Version: 0.0.1
  Author: Faisal Akram
  Author URI: 
 */

use WeDevs\Dokan\Walkers\TaxonomyDropdown;

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
//add_action( 'dokan_seller_registration_field_after', 'dpe_subscription_checkout_field', 11 );


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


    $shelves = dpe_get_vendor_shelves( get_post_field( 'post_author', $product_id ) );
    if( !empty( $shelves ) ) {
        update_post_meta( $product_id, '_sku', $product_id . ' - ' . implode( ' - ', $shelves ) );
    }
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
        echo '<div class="loop-in-someones-cart">This product is in another cart</div>';
    }

}
add_action( 'woocommerce_before_shop_loop_item_title', 'dpe_in_someones_cart_to_loop' );
add_action( 'woocommerce_after_product_gallery', 'dpe_in_someones_cart_to_loop' );


/**
 * Include subscription start date in vendor dashboard
 */
function dpe_show_subscription_start_date( $content ) {
    $subscription   = dokan()->vendor->get( get_current_user_id() )->subscription;
    if( $subscription ) {
        $translated_txt = __('Your package start date is','Dokan');
        $starte_date_el = sprintf( 
            '<p class="pack-start-date" style="display:none;">%s <span>%s</span></p>', 
            $translated_txt,
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
 * Helper to get subscriptions dates with user
 */
function dpe_get_subscriptions_dates( $start, $end ) {
    global $wpdb;

    $query = $wpdb->prepare(
        "SELECT meta.user_id, meta.meta_value as startdate, meta_1.meta_value as enddate
        FROM {$wpdb->usermeta} meta
        JOIN {$wpdb->usermeta} meta_1 ON meta.user_id = meta_1.user_id and meta_1.meta_key = 'product_pack_enddate'
        WHERE meta.meta_key = 'product_pack_startdate' and 
        ( CAST(meta.meta_value AS DATE) between %s and %s or CAST(meta_1.meta_value AS DATE) between %s and %s)",
        array( $start, $end, $start, $end )
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
    $terms = get_unused_shelves();

    if( is_array( $terms ) && count( $terms ) ) {
        $term = current( $terms );
        update_user_meta( $user_id, 'vendor_custom_product_id', $term->term_id );
    }
}
add_action( 'dokan_new_seller_created', 'dpe_update_vendor_shelf', 10, 2 );


function get_unused_shelves() {
    global $wpdb;

    $occupied = $wpdb->get_col( "SELECT meta_value from {$wpdb->usermeta} WHERE meta_key = 'vendor_custom_product_id' and 1=1" );
    $occupied = array_map( function( $value ){
        return (array) $value;
    }, array_map( 'maybe_unserialize', $occupied ) );
    $occupied = call_user_func_array( 'array_merge', $occupied );

    $exclude  = is_array( $occupied ) ? array_map( 'intval', $occupied ) : array();
    
    $terms = get_terms( array(
        'taxonomy'   => 'vendor_shelf',
        'hide_empty' => false,
        'exclude'    => $exclude
    ));

    return $terms;
}


/**
 * Override vendor add new product template
 */
function dpe_vendor_add_product_popup( $template, $slug, $name ) {
    $product_temps = array( 'products/tmpl-add-product-popup', 'products/new-product', 'products/new-product-single', 'products/product-edit', 'store-lists-filter' );
    $slug          = str_replace( '.php', '', $slug );

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
add_filter( 'dokan_locate_template', 'dpe_vendor_add_product_popup', 10, 3 );


/**
 * Helper to get restricted dates in a month
 */
function dpe_get_restrcited_days_for_month( $year, $month ) {

    $start = ( new DateTime() )->setDate( intval( $year ), intval( $month ), intval( 01 ) );
    $end   = ( new DateTime() )->setDate( intval( $year ), intval( $month ), intval( 31 ) );

    $option_key    = "sub_restricted_days_{$start->format('Y')}{$start->format('m')}";
    $days          = get_option( $option_key, array() );

    $results = array();
    $dates   = dpe_get_subscriptions_dates( $start->format('Y-m-d'),  $end->format('Y-m-d') );
    $l_date  = $start->format('Y-m-d');
    $s_count = array();

    while( $l_date <= $end->format('Y-m-d') ) {

        $key          = strtotime( $l_date );
        $shelves_left = get_unused_shelves();

        if( count( $shelves_left ) ) {
            foreach( $dates as $date ) {
                $r_start = date( 'Y-m-d', strtotime( $date['startdate'] ) );
                $r_end   = date( 'Y-m-d', strtotime( $date['enddate'] ) );

                if( $l_date >= $r_start && $l_date <= $r_end  ) {
                    $s_count[$key] = array_key_exists( $key, $s_count ) ? $s_count[$key] + 1 : 1;
                }
            }
        }

        if( !count( $shelves_left ) || isset( $days[$key], $s_count[$key] ) && intval( $days[$key] ) <= $s_count[$key] ) {
            $results[] = date( 'Y-m-d', intval( $key ) );
        }

        $l_date  = date( 'Y-m-d', strtotime( '+1 day', strtotime( $l_date ) ) );
    }

    return $results;
}


/**
 * Modify brand dropdown at vendor dashboard
 */
function dpe_brand_tags_data_attribute( $output, $args ) {

    global $post;

    if( $args['taxonomy'] === 'product_brand' ) {

        $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

        if( !is_page( $page_id ) && !( get_query_var( 'edit' ) && is_singular( 'product' ) ) ) {
            return $output;
        }

        $selected = isset( $post ) ? wp_get_post_terms( $post->ID, 'product_brand' ) : [];
        $terms    = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) ); 
        ob_start(); 
        ?>

        <select name="product_brand[]" id="product_brand" class="product_brand dokan-form-control dokan-select2" data-tags="true" multiple="true">
        <?php foreach( $terms as $term ): ?>
        <option value="<?php echo $term->term_id; ?>" <?php selected( in_array( $term->term_id, array_column( $selected, 'term_id' ) ) ); ?> >
            <?php echo $term->name; ?>
        </option>
        <?php endforeach; ?>
        </select>

        <?php
        $output = ob_get_clean();
    }

    return $output;
}
add_filter( 'wp_dropdown_cats', 'dpe_brand_tags_data_attribute', 10, 2 );


/**
 * Save brands on product add and edit
 */
function dpe_new_product_brand_added( $product_id, $data ) {
    if( !empty( $data['product_brand'] ) ) {
        $brands = array_map( 
            function( $term ) {
                return intval($term) ? intval($term) : $term;
            },
            (array) $data['product_brand']
        );
        wp_set_object_terms( $product_id, $brands, 'product_brand', true );
    }
}
add_action( 'dokan_new_product_added', 'dpe_new_product_brand_added', 21, 2 );
add_action( 'dokan_product_updated', 'dpe_new_product_brand_added', 21, 2 );


/**
 * Store ID on Vendor dashboard
 */
// function dpe_dashboard_vendor_id(){
//     echo sprintf(
//         '<div class="dashboard-widget"><div class="widget-title" style="border:none;margin-bottom:0;">%s %s : %d</div></div>', 
//         '<i class="fa fa-home"></i>',
//         'Store ID', 
//         get_current_user_id()
//     );
// }
// add_action( 'dokan_dashboard_before_widgets', 'dpe_dashboard_vendor_id' );


/**
 * Get vendor shelves by vendor id
 */
function dpe_get_vendor_shelves( $vendor_id ) {

    $shelves = (array) get_user_meta( $vendor_id, 'vendor_custom_product_id', true );
    if( empty( array_filter( $shelves ) ) ) {
        return [];
    }

    $terms = array_map( 
        function( $id ) {
            $term = get_term( $id, 'vendor_shelf' );
            return $term && !is_wp_error( $term ) ?  $term->name : "";
        },
        $shelves
    );

    return array_filter( $terms );
}


/**
 * Show SKU based on Vendor Shelves
 */
function dpe_vendor_product_sku( $sku, $product ) {

    $author_id = get_post_field( 'post_author', $product->get_id() );
    if( dokan_is_user_seller( $author_id ) ) {
        $shelves = dpe_get_vendor_shelves( $author_id );
        if( !empty( $shelves ) ) {
            $new_sku = $product->get_id() . ' - ' . implode( ' - ', dpe_get_vendor_shelves( $author_id ) );
            if( $sku !== $new_sku ) {
                update_post_meta( $product->get_id(), '_sku', $new_sku );
                $sku = $new_sku;
            }
        }
    }

    return $sku;
}
add_filter( 'woocommerce_product_get_sku', 'dpe_vendor_product_sku', 10, 2 );


function dpe_vendor_product_sku_update(){    
    global $pagenow;

    $is_admin_edit = is_admin() && $pagenow === 'post.php' && ( !empty( $_GET['action'] ) && $_GET['action'] == 'edit' ) && !empty( $_GET['post'] );
    if( !$is_admin_edit ) return;

    $post_id = intval( $_GET['post'] );
    $author_id = get_post_field( 'post_author', $post_id );
    if( !$author_id || !dokan_is_user_seller( $author_id ) ) return;

    $shelves = dpe_get_vendor_shelves( $author_id );
    if( empty( $shelves ) ) return;

    $sku = $post_id . ' - ' . implode( ' - ', $shelves );
    if( get_post_meta( $post_id, '_sku', true ) !== $sku ) {
        update_post_meta( $post_id, '_sku', $sku );
    }
}
add_action( 'init', 'dpe_vendor_product_sku_update', 10 );


/**
 * Add vendor dashboard bulk sale price
 */
function dpe_vendor_bulk_sale_price( $statuses ) {

    $statuses['sale'] = 'Sale Price'; 

    return $statuses;
}
add_filter( 'dokan_bulk_product_statuses', 'dpe_vendor_bulk_sale_price' );


/**
 * Process vendor dashboard bulk sale price
 */
function dpe_vendor_bulk_sale_price_process( $status, $products ){
    
    $sale_percent = !empty( $_POST['sale_value'] ) ? floatval( $_POST['sale_value'] ) : 0;

    foreach( $products as $product ) {
        $price = floatval( get_post_meta( $product, '_regular_price', true ) );
        if( !$price ) {
            continue;
        }

        if( $sale_percent > 0 ) {
            $sale_price = $price - ( $price * ( $sale_percent / 100 ) );
        }else{
            $sale_price = "";
        }
        
        update_post_meta( $product, '_sale_price',  $sale_price );
    }

}
add_action( 'dokan_bulk_product_status_change', 'dpe_vendor_bulk_sale_price_process', 10, 2 );


/**
 * Process vendor sales settings
 */
function dpe_save_vendor_sales_data( $vendor_id, $settings ) {

    $post_data = wp_unslash( $_POST );
    $rate      = !empty( $post_data['dokan_store_discount_rate'] ) ? $post_data['dokan_store_discount_rate'] : '';
    $start     = !empty( $post_data['dokan_store_discount_start'] ) ? $post_data['dokan_store_discount_start'] : '';
    $end       = !empty( $post_data['dokan_store_discount_end'] ) ? $post_data['dokan_store_discount_end'] : '';

    if( $rate = floatval( $rate ) ) {
        update_user_meta( $vendor_id, 'store_discount_rate', $rate );
    }

    if( !empty( $start ) ) {
        $start = date( 'Y-m-d 00:00:00', strtotime( $start ) );
        update_user_meta( $vendor_id, 'store_discount_start', $start );
    }

    if( !empty( $end ) ) {
        $end = date( 'Y-m-d 23:59:59', strtotime( $end ) );
        update_user_meta( $vendor_id, 'store_discount_end', $end );
    }


    $products = wc_get_products( array(
        'status' => 'publish',
        'limit'  => -1,
        'author' => $vendor_id
    ));

    foreach( $products as $product ) {

        $sale_price = '';

        if( $product->get_children() ) {

            foreach( $product->get_children_() as $variation ) {

                if( $rate ){
                    $sale_price = $variation->get_price() - ( $variation->get_price() * ( $rate / 100 ) ); 
                }
                $errors = $variation->set_props(
                    array(
                        'date_on_sale_from' => $start,
                        'date_on_sale_to'   => $end,
                        'sale_price'        => $sale_price
                    )
                );
                
                if( !is_wp_error( $errors ) ) {
                    $variation->save();
                }
            }
            continue;
        }

        if( $rate ){
            $sale_price = $product->get_price() - ( $product->get_price() * ( $rate / 100 ) ); 
        }
        $errors = $product->set_props(
            array(
                'date_on_sale_from' => $start,
                'date_on_sale_to'   => $end,
                'sale_price'        => $sale_price
            )
        );
        if( !is_wp_error( $errors ) ) {
            $product->save();
        }

    }

}
add_action( 'dokan_store_profile_saved', 'dpe_save_vendor_sales_data', 10, 2 );