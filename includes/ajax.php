<?php
/**
 * Ajax method handlers
 */


 /**
 * Ajax handler to save restricted days
 */
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
            $limit      = intval($date_range[$date]);

            if( $limit < 1 ) {
                unset( $days[$day] );
                continue;
            }

            $days[$day] = $date_range[$date];
        }

        update_option( $option_key, $days );
    }

    wp_send_json_success( true );
}
add_action( 'wp_ajax_dpe_save_restrcited_days', 'dpe_save_restrcited_days' );


/**
 * Ajax handler for subscription limits
 */
function dpe_sub_restricted_days() {
    wp_verify_nonce( $_GET['nonce'] );

    $start     = new DateTime( $_GET['start'] );
    $end       = new DateTime( $_GET['end'] );
    $year      = $start->format( 'Y' );

    $days = array();
    $diff = intval( $end->format('m') - $start->format('m') );

    while( $diff >= 0 ) {
        $key       = "sub_restricted_days_{$year}{$start->format('m')}";
        $days      = array_replace( $days, ( array ) get_option( $key, array() ) );

        $start->add( date_interval_create_from_date_string( '+1 months' ) );
        $diff--;
    }

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


/**
 * Ajax handler for subscribed count
 */
function dpe_sub_filled_count() {
    wp_verify_nonce( $_GET['nonce'] );

    $start     = ( new DateTime( $_GET['start'] ) )->format('Y-m-d');
    $end       = ( new DateTime( $_GET['end'] ) )->format('Y-m-d');

    $results = dpe_get_subscriptions_count( $start, $end );
    $events  = array();

    if( is_array( $results ) ) {
        foreach( $results as $result ) {
            $events[] = array(
                'title'  => "Subscribed {$result['s_count']}",
                'start'  => date( 'Y-m-d', strtotime( $result['s_date'] ) )
            );
        }
    }
    wp_send_json_success( $events );

}
add_action( 'wp_ajax_dpe_sub_filled_count', 'dpe_sub_filled_count' );


/**
 * Ajax handler for frontend restrcited days
 */
function dpe_get_restrcited_days_for_month() {

    $year  = $_POST['year'];
    $month = $_POST['month'];
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

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_dpe_get_restrcited_days_for_month', 'dpe_get_restrcited_days_for_month' );
add_action( 'wp_ajax_nopriv_dpe_get_restrcited_days_for_month', 'dpe_get_restrcited_days_for_month' );