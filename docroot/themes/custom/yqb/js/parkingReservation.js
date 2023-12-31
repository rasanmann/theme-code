jQuery(document).ready(function($){

    if($('.webform-submission-parking-reservation-form-form').length){
        var fulldate = "";

        $('input[name=start_date_form]').on('change', function(e){
            fulldate = "";
            // Change date format Y-m-d (2021-04-27) to dmy (27042021)
            if($('input[name=start_date]').length){
                var date = new Date(this.value);
                fulldate = fulldate.concat(addZero(date.getDate()+1), addZero(date.getMonth()+1), date.getFullYear());
                $('input[name=start_date]').val( fulldate );
            }
        });

        $('input[name=end_date_form]').on('change', function(e){
            fulldate = "";
            // Change date format Y-m-d (2021-04-27) to dmy (27042021)
            if($('input[name=end_date]').length){
                var date = new Date(this.value);
                fulldate = fulldate.concat(addZero(date.getDate()+1), addZero(date.getMonth()+1), date.getFullYear());
                $('input[name=end_date]').val( fulldate );
            }
        });
        
        $('.webform-submission-parking-reservation-form-form').submit(function (e) {
            setTimeout(function(){ 
                $('.webform-submission-parking-reservation-form-form').find('.webform-button--submit').removeClass('is-loading is-clicked');
            }, 1000);
        });
    }   

    //add leading zero if month/day is 9 or less
    function addZero(number) {
        if (number <= 9) {
            return '0'+number;
        } else {
            return number;
        }
    }
});
