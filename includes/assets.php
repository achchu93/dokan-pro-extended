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
 * Assets for subscription calendar page
 */
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