(function ($, window, document) {

    // var adminDatepicker;
    var calendar;

    $(function () {
        // The DOM is ready!
        var el = $('#subscription_calendar');
        calendar = new FullCalendar.Calendar(el[0], {
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

                restrictDays( selection.start, selection.end )
                    .then(function (response) {
                        if (response.data) {
                            var source = calendar.getEventSources()[0];

                            source.refetch();
                            calendar.unselect();
                        }
                    });
            },
            eventSources: [
                {
                    id: 1,
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
                    id: 2,
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


        from = $("#fromDatepicker").datepicker({
            minDate: "+3d",
            dateFormat: 'dd-mm-yy',
            onSelect: function(date){
                to.datepicker("option", "minDate", moment(date, "DD-MM-YYYY").add(1, "days").toDate());

                if (from.val() && to.val()) {
                    $(".submit_date").attr('disabled', false);
                }
            }
        }),
        to = $("#toDatepicker").datepicker({
            dateFormat: 'dd-mm-yy',
            onSelect: function (date) {
                from.datepicker("option", "maxDate", date);

                if (from.val() && to.val()) {
                    $(".submit_date").attr('disabled', false);
                }
            }
        });

        $( ".submit_date" ).on( 'click', function(){
            if ( !from.val() || !to.val() ) {
                return;
            }
            
            restrictDays(from.datepicker('getDate'), moment(to.datepicker('getDate')).add(1, "days").toDate() )
                .then(function (response) {
                    if (response.data) {
                        var source = calendar.getEventSources()[0];

                        source.refetch();
                        calendar.unselect();
                    }
                });
        });
    });

    function restrictDays( start, end ) {

        start = moment(start);
        end = moment(end).add(-1, 'days');
        var isMultiple = start.isSame(end) ? false : true;
        var message = 'Please set a limit for selection ';
        var format = 'Y-MM-DD';

        message += isMultiple ? start.format(format) + " to " + end.format(format) : start.format(format);

        var limit = prompt(message, 1);
        limit = parseInt(limit);

        if (isNaN(limit)) {
            return new Promise(function(resolve){resolve({});});
        }

        var data = {};
        var hasLimitExceeds = false;
        while (!start.isAfter(end)) {

            var events = calendar.getEvents().filter( function( event ){
                return moment(event.start).isSame(start) && !event.extendedProps.count;
            });

            if( events.length > limit ){
                hasLimitExceeds = true;
            }else{
                data[start.format('YYYY-MM-DD')] = limit;
            }

            start.add(1, "days");
        }

        return $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'dpe_save_restrcited_days',
                restricted_days: data
            },
            success: function() {
                if( hasLimitExceeds ){
                    alert('There were limits lower than registered vendors. Skipped those while saving.');
                }
            }
        });
    }

}(jQuery));