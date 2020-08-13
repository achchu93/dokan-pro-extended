(function ($, window, document) {

    var restrictedDays = {};

    $(function () {

        var datePickerEl = $('#dokan_subscription_start_date');

        datePickerEl.datepicker({
            defaultDate: new Date(),
            dateFormat: "yy-mm-dd",
            minDate: "+3d",
            maxDate: "+1y",
            onChangeMonthYear: function (year, month, instance) {

                currentMonth = month;

                showLoadingOnDatePicker( instance.dpDiv );
                setRestrcitedDays( year, month, instance );
                
            },
            beforeShow: function (input, instance) {
                var val  = instance.input.datepicker('getDate');
                var date = !val ? new Date() :  new Date(val);

                if( !date ) return;

                showLoadingOnDatePicker(instance.dpDiv);
                setRestrcitedDays(
                    $.datepicker.formatDate('yy', date), 
                    $.datepicker.formatDate('mm', date), 
                    instance
                );                
            },
            beforeShowDay: function (date) {
                var string = $.datepicker.formatDate('yy-mm-dd', date);
                var month  = $.datepicker.formatDate('mm', date);
                return [!restrictedDays[month] ||  restrictedDays[month].indexOf(string) == -1];
            }
        });

        datePickerEl.on( 'change, blur', function() {
            var date = new Date(datePickerEl.datepicker('getDate'));

            if( !date ) {
                datePickerEl.val('');
                return;
            }

            var month = $.datepicker.formatDate('mm', date);
            var day = $.datepicker.formatDate('yy-mm-dd', date);
            if ( restrictedDays[month] && restrictedDays[month].indexOf(day) != -1 ) {
                datePickerEl.val('');
                return;
            }
        });

    });


    function showLoadingOnDatePicker( div ) {
        setTimeout( 
            function(){
                div.block({ message: null });
            },
            50
        );
    }

    function fetchRestrictedDays( year, month ) {
        return $.ajax({
            url: dokan.ajaxurl,
            method: 'POST',
            data: {
                action: 'dpe_get_restrcited_days_for_month',
                year: year,
                month : month
            }
        });
    }

    function setRestrcitedDays( year, month, instance ) {

        var formatted = String(month).padStart(2, "0");


        if( restrictedDays[formatted] ) {
            setTimeout( function(){
                instance.dpDiv.unblock();
            }, 50 );
            return;
        }

        fetchRestrictedDays(year, month)
            .then(function (response) {
                restrictedDays[formatted] = response.data;
            })
            .always(function () {
                instance.input.datepicker('refresh');
                instance.dpDiv.unblock();
            });
    }

}(jQuery));