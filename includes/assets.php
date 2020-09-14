<?php
/**
 * Assets handler
 */

 defined( 'ABSPATH' ) || exit;

 /**
 * Registering plugin main style for frontend
 */
function dpe_main_styles() {
    wp_register_style( 'dpe-main-style', plugins_url( '/assets/css/main.css', DPE_PLUGIN ), [ 'dokan-style' ] );
}
add_action( 'wp_enqueue_scripts', 'dpe_main_styles' );


/**
 * Vendor dashboard styles and scripts
 */
function dpe_vendor_dashboard_scripts() {

    $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

    if( empty( $page_id ) || !( ( get_query_var( 'edit' ) && is_singular( 'product' ) ) || is_page( $page_id ) ) ) {
        return;
    }

    $js  = dpe_dokan_dashboard_js();

    wp_enqueue_style( 'dpe-main-style' );
    wp_add_inline_script( 'dokan-script', $js );
}
add_action( 'wp_enqueue_scripts', 'dpe_vendor_dashboard_scripts' );


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

                $.blockUI({message:null});

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
                    $.unblockUI();
                });
            }
        } );


        var restrictedDays = {},
            chosenPack = null,
            chosen = false;

        var picker = $('#datepicker').datepicker({
            minDate: "+3d",
            defaultDate: new Date(),
            dateFormat: 'yy-mm-dd',
            onSelect: function(date, instance){
                $('#submit-picker').removeAttr('disabled');
            },
            onChangeMonthYear: function(year, month, instance){
                var url = new URL(chosenPack.get(0).href);
                var pack_id = url.searchParams.get('add-to-cart');

                restrictDays(year, month, pack_id);
            },
            beforeShowDay: function (date) {
                var string = $.datepicker.formatDate('yy-mm-dd', date);
                var month  = $.datepicker.formatDate('mm', date);
                return [!restrictedDays[month] ||  restrictedDays[month].indexOf(string) == -1];
            }
        });

        $('.product_pack_item').not('.current_pack').find('.buy_product_pack').on( 'click', function( e ) {
            if( !chosenPack || !chosen ){
                e.preventDefault();
                chosenPack = $(this);

                var url = new URL($(this).get(0).href);
                var pack_id = url.searchParams.get('add-to-cart');

                $.blockUI(
                    {
                        message: $('#datepicker_container'),
                        css: { width: '400px' }
                    }
                );

                if( !picker.datepicker('getDate') ){
                    picker.datepicker('setDate', '+3d');
                }

                restrictDays(
                    $.datepicker.formatDate('yy', picker.datepicker('getDate')),
                    $.datepicker.formatDate('mm', picker.datepicker('getDate')),
                    pack_id
                );
            }else{
                window.location.href = $(this).attr('href');
            }
        });

        $('#cancel-picker, #cancel-sale').on( 'click', function(){
            chosen     = false;
            chosenPack = null;
            $.unblockUI();

            $('#datepicker').datepicker('setDate', null); //set back the dates
            $('#submit-picker').attr('disabled', true);

        });

        $('#submit-picker').on( 'click', function(){
            var wrapper = $('#datepicker_container');
            wrapper.block({message:null});

            $.ajax({
                url: dokan.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dpe_save_subscription_start_date',
                    dokan_subscription_start_date: $.datepicker.formatDate('yy-mm-dd', picker.datepicker('getDate'))
                }
            }).then(function(response){
                if( response.data ){
                    chosen = true;
                    chosenPack.click();
                }
            }).always(function(){
                $.unblockUI();
                $('body').block({message:null});
            });
        });

        $('#bulk-product-action').on( 'click', function(e){
            if( $('#bulk-product-action-selector').val() === 'sale' && !$('#sale_value').length ){
                e.preventDefault();
                $.blockUI(
                    {
                        message: $('#sale_input_container'),
                        css: { width: '350px' }
                    }
                );
            }
        });

        $('#sale_input').on( 'input change', function(e){
            var value =  parseFloat($(this).val());
            var submit = $('#submit-sale');
            if( !isNaN(value) ){
                submit.removeAttr('disabled');
            }else{
                submit.attr('disabled', true);
            }
        });

        $('#submit-sale').on( 'click', function(e){
            var value =  parseFloat($('#sale_input').val());
            if( !isNaN(value) ){
                $('#sale_value').remove();
                $('<input type="hidden" name="sale_value" id="sale_value" value='+value+' />').insertAfter($('#security'));
            }
            $.unblockUI();
            $('#bulk-product-action').click();
        });


        $('#dokan-store-discount-start, #dokan-store-discount-end').each(function(){
            var el = $(this);

            $(this).datepicker({
                defaultDate:     '',
                dateFormat:      'yy-mm-dd',
                numberOfMonths:  1,
                onSelect: function( date, instance ){
                    el.val(date);
                }
            });

            $(this).on('change blur', function(){
                var pattern = new RegExp($(this).prop('pattern'));
                if( !pattern.test( el.val() ) ){
                    el.val('');
                }
                console.log(pattern, pattern.test( el.val() ));
            });
        });

        function restrictDays(year, month, pack_id){

            restrictedDays = {}; // resetting for now to avoid confusion, can use it later to optimize performance

            var blockDiv  = $('.ui-datepicker-inline');
             formatted = String(month).padStart(2, "0");

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
                    month : month,
                    pack_id: pack_id
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
        array('jquery-ui-datepicker'),
        true
    );

    wp_enqueue_style(
        'full-calendar-css',
        'https://cdn.jsdelivr.net/npm/fullcalendar@5.1.0/main.min.css'
    );

    wp_enqueue_style(
        'jquery-ui-datepicker',
        'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'
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
        <p><?php _e( 'Please choose a subscription start date. Default will be current date.', 'Dokan' ); ?> </p>
        <div id="datepicker"></div>
        <div class="actions">
            <button id="cancel-picker" class="button">Cancel</button>
            <button id="submit-picker" class="button" disabled>Submit</button>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'dpe_vendor_dashboard_picker' );


function dpe_vendor_dashboard_sale_input() {
    ?>
    <div id="sale_input_container" style="display:none; cursor: default">
        <p>Please input a sale percentage to be applied for products.</p>
        <input type="number" id="sale_input">
        <div class="actions">
            <button id="cancel-sale" class="button">Hætta við</button>
            <button id="submit-sale" class="button" disabled>Sendu inn</button>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'dpe_vendor_dashboard_sale_input' );


/**
 * Store list inline script
 */
function dpe_store_list_scripts() {

    if( !dokan_is_store_listing() ) {
        return;
    }

    ob_start();
    ?>

    jQuery(function($){
        $('#dokan-store-listing-filter-form-wrap').slideDown();

        $('#sale-price').on( 'change', function(e){
            delete dokan.storeLists.query.sale_price;

            if( $(this).prop('checked') ) {
                dokan.storeLists.query.sale_price = 'yes';
            }
        });
    });

    <?php
    $js = ob_get_clean();

    wp_enqueue_style( 'dpe-main-style' );
    wp_add_inline_script( 'dokan-script', $js );
}
add_action( 'wp_enqueue_scripts', 'dpe_store_list_scripts' );