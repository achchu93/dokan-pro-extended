(function ($, window, document) {

    // var adminDatepicker;

    $(function () {
        // The DOM is ready!
        var el = $('#subscription_calendar');
        var calendar = new FullCalendar.Calendar(el[0], {
            dayMaxEvents: 5,
            initialView: 'dayGridMonth',
            selectable: true,
            lazyFetching: true,
            validRange: function ( nowDate ) {
                nowDate = moment( nowDate );
                return {
                    start: nowDate.toDate(),
                    end: nowDate.clone().add( 1, 'years' ).toDate()
                };
            },
            select: function ( selection ) {
                var start      = moment(selection.start);
                var end        = moment(selection.end).add(-1, 'days');
                var isMultiple = start.isSame(end) ? false : true;
                var message    = 'Please set a limit for selection ';
                var format     = 'Y-MM-DD';
                
                message += isMultiple ? start.format(format) + " to " + end.format(format) : start.format(format);

                var limit = prompt( message, 1 );
                limit     = parseInt(limit);

                if( isNaN(limit) ){
                    return;
                }
                
                var data = {};
                while( !start.isAfter(end) ){
                    data[start.format('YYYY-MM-DD')] = limit;
                    start.add(1, "days");
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'dpe_save_restrcited_days',
                        restricted_days: data
                    }
                }).then(function(response){
                    if( response.data ) {
                        var source = calendar.getEventSources()[0];

                        source.refetch();
                        calendar.unselect();
                    }
                });
            },
            eventSources: [
                {
                    url: ajaxurl,
                    method: 'GET',
                    extraParams: {
                        action: 'dpe_sub_restricted_days',
                        nonce: heartbeatSettings.nonce
                    },
                    failure: function () {
                        alert('there was an error while fetching dates!');
                    },
                },
                {
                    url: ajaxurl,
                    method: 'GET',
                    extraParams: {
                        action: 'dpe_sub_filled_count',
                        nonce: heartbeatSettings.nonce
                    },
                    failure: function () {
                        alert('there was an error while fetching dates!');
                    },
                }
            ],
            eventSourceSuccess: function (content, xhr) {
                return content.data;
            }
        });

        // renders calendar
        calendar.render();
    });

}(jQuery));