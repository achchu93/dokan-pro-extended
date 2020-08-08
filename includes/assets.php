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
        '.loop-in-someones-cart{position:absolute;left:0;right:0;margin:1em auto 0;background:rgba(0,0,0,.3);color:#fff;width: 80%;padding: 5px;text-align: center;z-index: 100;}
        .woocommerce-product-gallery__wrapper + .loop-in-someones-cart{top:0; margin:0; width:100%; }' 
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
    
    ob_start();
    ?>
    #datepicker_container {
        padding: 20px;
    }
    #datepicker_container .ui-datepicker-inline {
        margin: auto;
    }
    #datepicker_container #cancel-picker {
        float: right;
        margin: 20px 0;
        padding: 10px;
        font-size: 1.2rem;
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
                /*if( $( '#dokan-add-new-product-form .product_cat' ).length > 3 ) {
                    return;
                }*/ 

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


        var restrictedDays = {},
            chosenPack = null,
            chosen = false;

        var picker = $('#datepicker').datepicker({
            minDate: new Date(),
            defaultDate: new Date(),
            dateFormat: 'yy-mm-dd',
            onSelect: function(date, instance){
                var wrapper = $('#datepicker_container');
                wrapper.block({message:null});

                $.ajax({
                    url: dokan.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'dpe_save_subscription_start_date',
                        dokan_subscription_start_date: date
                    }
                }).then(function(response){
                    if( response.data ){
                        chosen = true;
                        chosenPack.click();
                    }
                }).always(function(){
                    $.unblockUI();
                });
            },
            onChangeMonthYear: function(year, month, instance){
                restrictDays(year, month);
            },
            beforeShowDay: function (date) {
                var string = $.datepicker.formatDate('yy-mm-dd', date);
                var month  = $.datepicker.formatDate('mm', date);
                return [!restrictedDays[month] ||  restrictedDays[month].indexOf(string) == -1];
            }
        });

        restrictDays(
            $.datepicker.formatDate('yy', picker.datepicker('getDate')), 
            $.datepicker.formatDate('mm', picker.datepicker('getDate'))
        )

        $('.product_pack_item').not('.current_pack').find('.buy_product_pack').on( 'click', function( e ) {
            if( !chosenPack || !chosen ){
                e.preventDefault();
                chosenPack = $(this);
                $.blockUI(
                    { 
                        message: $('#datepicker_container'), 
                        css: { width: '400px' } 
                    }
                );
            }else{
                window.location.href = $(this).attr('href');
            }
        });

        $('#cancel-picker').on( 'click', function(){
            chosen     = false;
            chosenPack = null;
            $.unblockUI();
        });

        function restrictDays(year, month){
            var blockDiv  = $('.ui-datepicker-inline');
            var formatted = String(month).padStart(2, "0");

            if( restrictedDays[formatted] ){
                return;
            }

            setTimeout(function(){
                blockDiv.block({message:null});
            }, 50);

            $.ajax({
                url: dokan.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dpe_get_restrcited_days_for_month',
                    year: year,
                    month : month
                }
            }).then(function(response){
                restrictedDays[formatted] = response.data;
            }).always(function(){
                blockDiv.unblock();
                picker.datepicker('refresh');
            });
        }
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


function dpe_vendor_dashboard_picker() {
    ?>
    <div id="datepicker_container" style="display:none; cursor: default"> 
        <p>Please choose a subscription start date. Default will be current date.</p>
        <div id="datepicker"></div>
        <button id="cancel-picker">Cancel</button>
    </div> 
    <?php
}
add_action( 'wp_footer', 'dpe_vendor_dashboard_picker' );