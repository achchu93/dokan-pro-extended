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
        .woocommerce-product-gallery .loop-in-someones-cart{top:0; margin:0; width:100%; }' 
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
    #datepicker_container,
    #sale_input_container {
        padding: 20px;
    }
    #datepicker_container .ui-datepicker-inline {
        margin: auto;
    }
    #datepicker_container .actions,
    #sale_input_container .actions {
        display: flex;
        margin-top: 20px;
        justify-content: space-between;
    }
    #sale_input {
        width: 100%;
        padding: 1em;
        border: 1px solid #ddd;
        outline: none !important;
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
            minDate: "+3d",
            defaultDate: new Date(),
            dateFormat: 'yy-mm-dd',
            onSelect: function(date, instance){
                $('.ui-datepicker-inline').block({message:null}); //added

                console.log('date',date);
                console.log('restricted',restrictedDays); //added

                //added later

                var weeks = jQuery("div.jet-tabs div.active-tab").attr('data-tab');
                var days = weeks * 7;
                days  = days-1;

                this_date = new Date(date);
                next_date = new Date(date);
                next_date = next_date.setDate(next_date.getDate() + days);

                var getDaysArray = function(s,e) {for(var a=[],d=new Date(s);d<=e;d.setDate(d.getDate()+1)){ a.push(new Date(d));}return a;};

                all_days = getDaysArray(this_date, next_date);
                all_days = all_days.map((v)=>v.toISOString().slice(0,10));

                console.log('all_days',all_days); //added


                restrictedDaysOnly = restrictedDays[Object.keys(restrictedDays)[0]];

                if( all_days.some(r=> restrictedDaysOnly.indexOf(r) >= 0) ){ //true if any of all_days date is in restrictedDays array

                    var conflict_first_date = all_days.filter(function(item){ return restrictedDaysOnly.indexOf(item) > -1});
                    
                    var this_date_human = this_date.toLocaleDateString();
                    var next_date_human = new Date(next_date); next_date_human = next_date_human.toLocaleDateString();
                    var conflict_first_date_human = new Date(conflict_first_date[0]); conflict_first_date_human = conflict_first_date_human.toLocaleDateString();


                    alert('Selected subscription period  from '+this_date_human+' to '+next_date_human+' includes '+conflict_first_date_human+', which is not available. Please choose a different subscription start date.');
                //    $('#datepicker').datepicker('setDate', null);  //this returns user to initial month

                    $('#submit-picker').attr('disabled', true);



                }else{
                    $('#submit-picker').removeAttr('disabled');
                }


                $('.ui-datepicker-inline').unblock(); //added
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

        function restrictDays(year, month){


            restrictedDays = {}; // resetting for now to avoid confusion, can use it later to optimize performance

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
                console.log(response.data); //added
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
        <p>Please choose a subscription start date. Default will be current date.</p>
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
            <button id="cancel-sale" class="button">Cancel</button>
            <button id="submit-sale" class="button" disabled>Submit</button>
        </div>
    </div> 
    <?php
}
add_action( 'wp_footer', 'dpe_vendor_dashboard_sale_input' );