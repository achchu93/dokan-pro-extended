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
 * Adds subscription start date field
 */
function dpe_subscription_checkout_field( $fields ) {
    ?>
    <p class="form-row form-group form-row-wide" style="margin-top:90px;">
        <label for="dokan_subscription_start_date">Subscription Start Date<span class="required">*</span></label>
        <input 
            type="date" 
            class="input-text form-control" 
            name="dokan_subscription_start_date" 
            id="dokan_subscription_start_date" 
            required="required"
            min="<?php echo date( 'Y-m-d' ); ?>"
            max="<?php echo date( 'Y-m-d', strtotime( '+1 year' ) ); ?>"
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
            $template = plugin_dir_path( __FILE__ ) . 'templates/seller-registration-form.php';
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
 * Inline style for in someones cart label
 */
function dpe_in_someones_cart_style() {
    wp_add_inline_style( 
        'dokan-style', 
        '.loop-in-someones-cart{position:absolute;left:0;right:0;margin:1em auto 0;background:rgba(0,0,0,.3);color:#fff;width: 80%;padding: 5px;}' 
    );
}
add_action( 'wp_enqueue_scripts', 'dpe_in_someones_cart_style' );


/**
 * Vendor dashboard styles and scripts
 */
function dpe_dashboard_media_library_style() {

    $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

    if( empty( $page_id ) || !is_page( $page_id ) ) {
        return;
    }

    $css = dpe_dokan_dashboard_css();
    $js  = dpe_dokan_dashboard_js();

    wp_add_inline_style( 'dokan-style', $css );
    wp_add_inline_script( 'dokan-script', $js );
}
add_action( 'wp_enqueue_scripts', 'dpe_dashboard_media_library_style' );

/**
 * Vendor dashboard inline css
 */
function dpe_dokan_dashboard_css() {
    ob_start();
    ?>
    body.dokan-dashboard .media-modal.wp-core-ui {
        outline: none;
    }
    body.dokan-dashboard .media-modal-content {
        max-width: 600px;
        height: 450px;
        left: 0;
        right: 0;
        margin: auto;
        border-radius: 0;
        box-shadow: none;
        background: #fff;
        outline: none;
    }
    body.dokan-dashboard .media-frame-title {
        display: none;
    }
    body.dokan-dashboard .media-frame-router {
        top: 0;
    }
    body.dokan-dashboard .media-router {
        text-align: center;
    }
    body.dokan-dashboard .media-menu-item {
        border: none;
        float: none;
    }
    body.dokan-dashboard .media-menu-item.active {
        background: #0e8c3a !important;
        color: #fff !important;
        outline: none !important;
        box-shadow: none !important;
    }
    body.dokan-dashboard .media-frame-content {
        border: none;
        outline: none;
    }
    body.dokan-dashboard .uploader-inline .button {
        color: #fff;
        border-color: #0e8c3a;
        background-color: #0e8c3a;
        border-radius: 0;
    }
    body.dokan-dashboard .media-toolbar {
        border: none;
    }
    body.dokan-dashboard .media-button-select,
    body.dokan-dashboard .media-button-select[disabled] {
        background-color: #0e8c3a !important;
        border-color: #0e8c3a !important;
        border-radius: 0;
    }
    <?php
    $css = ob_get_clean();

    return $css;
}

/**
 * Vendor dashboard inline js
 */
function dpe_dokan_dashboard_js() {
    ob_start();
    ?>
    jQuery(function($){
        $('.pack-start-date')
            .insertAfter( $('.seller_subs_info p').eq(1) )
            .show();
    });
    <?php
    $js = ob_get_clean();
    
    return $js;
}


/**
 * Adds custom product id to user fields
 */
function dpe_vendor_custom_product_id( $user ) {
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
            <input 
                type="number" 
                name="vendor_custom_product_id" 
                id="vendor_custom_product_id" 
                value="<?php echo $custom_product_id; ?>"
            />
        </td>
    </tr>
    <?php
}
add_action( 'dokan_seller_meta_fields', 'dpe_vendor_custom_product_id', 11 );


/**
 * Save custom product id to user meta
 */
function dpe_save_vendor_custom_product_id( $user_id ) {
    if( !empty( $_POST['vendor_custom_product_id'] ) ) {
        update_user_meta( $user_id, 'vendor_custom_product_id', $_POST['vendor_custom_product_id'] );
    }
}
add_action( 'dokan_process_seller_meta_fields', 'dpe_save_vendor_custom_product_id' );


function dpe_show_subscription_start_date( $content ) {
    $subscription   = dokan()->vendor->get( get_current_user_id() )->subscription;
    $starte_date_el = sprintf( 
       '<p class="pack-start-date" style="display:none;">Your package start date is <span>%s</span></p>', 
        date_i18n( 
            get_option( 'date_format' ), 
            strtotime( $subscription->get_pack_start_date() ) 
        ) 
    );
    $content .= $starte_date_el;

    return $content;
}
add_filter( 'dokan_sub_shortcode', 'dpe_show_subscription_start_date' );


/**
 * Replace order's product id with vendor custom product ID
 */
function dpe_order_line_item_product_id( $item_id, $item, $product ) {

    if( $item->get_type() !== 'line_item' ) {
        return;
    }

    $order_id          = $item->get_order_id();
    $product_id        = $product->get_id();
    $order             = wc_get_order( $order_id );
    $custom_product_id = get_user_meta( $order->get_customer_id(), 'vendor_custom_product_id', true );

    printf( '<div class="vendor-product-id">Product ID: %s</span>', "$product_id-$custom_product_id" );
}
add_action( 'woocommerce_before_order_itemmeta', 'dpe_order_line_item_product_id', 10, 3 );


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


function dpe_admin_assets() {

    wp_enqueue_script( 
        'full-calendar-js', 
        'https://cdn.jsdelivr.net/npm/fullcalendar@5.1.0/main.min.js', 
        array(), 
        true
    );

    wp_enqueue_script( 
        'moment-js', 
        'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js', 
        array(), 
        true
    );

    wp_enqueue_script(
        'dpe-admin-js',
        plugins_url( '/assets/js/admin.js', __FILE__ ),
        array(),
        true
    );

    wp_enqueue_style( 
        'full-calendar-css', 
        'https://cdn.jsdelivr.net/npm/fullcalendar@5.1.0/main.min.css'
    );

    wp_enqueue_style( 
        'dpe-admin-css', 
        plugins_url( '/assets/css/admin.css', __FILE__ )
    );
}
add_action( 'admin_enqueue_scripts', 'dpe_admin_assets' );

function dpe_save_restrcited_days(){

    $date_range    = $_POST['restricted_days'];
    $result        = array();
    $year          = date( 'Y', strtotime( current( array_keys( $date_range ) ) ) );
    $months        = array();

    foreach( $date_range as $date => $limit ) {
        $month      = date( 'm', strtotime( $date ) );
        if( !array_key_exists( $month, $months ) ) {
            $months[$month] = array();
        }
        $months[$month][] = $date;
    }


    foreach( $months as $month => $dates ) {
        $option_key = "sub_restricted_days_{$year}{$month}";
        $days       = get_option( $option_key, array() );

        if( !is_array( $days ) ) {
            $days = array();
        }

        foreach( $dates as $date ) {
            $day        = strtotime( $date );
            $days[$day] = $date_range[$date];
        }

        update_option( $option_key, $days );
    }

    wp_send_json_success( true );
}
add_action( 'wp_ajax_dpe_save_restrcited_days', 'dpe_save_restrcited_days' );


function dpe_sub_restricted_days() {
    wp_verify_nonce( $_GET['nonce'] );

    $start = new DateTime( $_GET['start'] );
    $end   = new DateTime( $_GET['end'] );

    $year      = $start->format( 'Y' );
    $start_key = "sub_restricted_days_{$year}{$start->format('m')}";
    $end_key   = "sub_restricted_days_{$year}{$end->format('m')}";

    $days = (array) get_option( $start_key, array() );
    $days = array_replace( $days, ( array ) get_option( $end_key, array() ) );

    $events = array();
    foreach( $days as $day => $count ) {
        $events[] = array(
            'title'  => "Limited to $count",
            'start'  => date( 'Y-m-d', $day )
        );
    }

    wp_send_json_success( $events );
}
add_action( 'wp_ajax_dpe_sub_restricted_days', 'dpe_sub_restricted_days' );