<?php
/**
 * Assets handler
 */

 defined( 'ABSPATH' ) || exit;

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

    if( empty( $page_id ) || !( ( get_query_var( 'edit' ) && is_singular( 'product' ) ) || is_page( $page_id ) ) ) {
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
    
    //deactivating  css
    $css = '';
    
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


        $('body').on( 'change', '.product_cat', function( e ) {

            $(this).parent().nextAll('.cat-group').each( function(){
                var productCatSelect = $(this).find('.product_cat');
                if( productCatSelect.length ) {
                    $(this).remove();
                }
            });

            renderChildCategories( $(this) );

            function renderChildCategories( parentCatEl ) {
                var parentCat = parentCatEl.val();
                if( $.isArray( parentCat ) ) {
                    return;
                }
                if( parseInt( parentCat ) < 1 ) {
                    return;
                }
                if( $( '#dokan-add-new-product-form .product_cat' ).length > 3 ) {
                    return;
                }

                var wrapperEl = parentCatEl.parents('.product-full-container');
                wrapperEl.block({message:null});

                $.ajax({
                    url: dokan.ajaxurl,
                    data: {
                        action: 'dpe_get_child_category_el',
                        category: parentCat
                    }
                }).then( function( response ) {
                    if( response.success ) {
                        var catWrapper = $(response.data);
                        
                        if( catWrapper.find('.product_cat option').length < 2 ) {
                            return;
                        }

                        catWrapper.insertAfter(parentCatEl.parent());
                        catWrapper.find('.product_cat').select2();
                    }
                }).always( function() {
                    wrapperEl.unblock();
                });
            }
        } );
    });
    <?php
    $js = ob_get_clean();
    
    return $js;
}


/**
 * Assets for subscription calendar page
 */
function dpe_admin_assets() {

    $screen = get_current_screen();

    if( $screen && $screen->id === 'user-edit' ) {
        wp_enqueue_script( 'jquery-ui-datepicker', null, array(), true );

        wp_localize_script( 'jquery-ui-datepicker', 'dpe_datepicker', array( 'format' => get_option( 'date_format' ) ) );
        return;
    }

    if( !$screen || $screen->id !== 'dokan_page_subscription-calendar' ){
        return;
    }

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
        plugins_url( '/assets/js/admin.js', DPE_PLUGIN ),
        array(),
        true
    );

    wp_enqueue_style( 
        'full-calendar-css', 
        'https://cdn.jsdelivr.net/npm/fullcalendar@5.1.0/main.min.css'
    );

    wp_enqueue_style( 
        'dpe-admin-css', 
        plugins_url( '/assets/css/admin.css', DPE_PLUGIN )
    );
}
add_action( 'admin_enqueue_scripts', 'dpe_admin_assets' );

function dpe_registration_scripts() {
    if( !is_account_page() || is_user_logged_in() ) {
        return;
    }

    wp_enqueue_style( 
        'jquery-ui-datepicker', 
        'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' 
    );
    wp_enqueue_script( 
        'dpe-frontend-js', 
        plugins_url( 'assets/js/frontend.js', DPE_PLUGIN ), 
        array( 'jquery', 'jquery-ui-datepicker' ), 
        true 
    );
}
add_action( 'wp_enqueue_scripts', 'dpe_registration_scripts', 99 );