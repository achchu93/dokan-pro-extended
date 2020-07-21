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
	$packages = dokan()->subscription->all();

    if( $packages->have_posts() ) {
        
        $in_cart = false;

        foreach( $packages->get_posts() as $package ) {
            $cart_id    = WC()->cart->generate_cart_id( $package->ID );
            $in_cart_id = WC()->cart->find_product_in_cart( $cart_id );

            if( $cart_id === $in_cart_id ){
                $in_cart = true;
                break;
            }
        }

        if( $in_cart ) {
            $fields['billing']['dokan_subscription_start_date'] = array(
                'type' => 'date',
                'required' => true,
                'name' => 'dokan_subscription_start_date',
                'id' => 'dokan_subscription_start_date',
                'validate' => array( 'date' ),
                'label' => 'Subscription start date',
                'description' => 'Please select a subscription start date',
				'priority' => 111,
				'custom_attributes' => array( 
					'min' => date( 'Y-m-d' ),
					'max' => date( 'Y-m-d', strtotime( '+1 year' ) )
				)
            );
        }
	}

    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'dpe_subscription_checkout_field' );


/**
 * Store user selected date on checkout process
 */
function dpe_save_subscription_start_date( $order_id, $posted_data, $order ) {

	update_user_meta( 
		$order->get_customer_id(), 
		'dokan_subscription_start_date', 
		date( 'Y-m-d H:i:s', strtotime( $posted_data['dokan_subscription_start_date'] ) )
	);
}
add_action( 'woocommerce_checkout_order_processed', 'dpe_save_subscription_start_date', 10, 3 );


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
        $template = plugin_dir_path( __FILE__ ) . 'templates/seller-registration-form.php';
    }
    return $template;
}
add_filter( 'dokan_get_template_part', 'dpe_override_registration_form', 10, 3 );