(function($) {

    /****************
     * Main Function *
     *****************/
    $.fn.priceFormat = function() {

        return this.each(function() {
            // pre defined options
            var obj = $(this);
            var value = '';
            var is_number = /[0-9]|./;

            // Check if is an input
            if (obj.is('input'))
                value = obj.val();
            else
                value = obj.html();

            function set(nvalue) {
                if (obj.is('input'))
                    obj.val(nvalue);
                else
                    obj.html(nvalue);

                obj.trigger('pricechange');
            }

            function get() {
                if (obj.is('input'))
                    value = obj.val();
                else
                    value = obj.html();

                return value;
            }


            function price_it() {
                var value = get().toString().replace(/[^0-9.]/g, "");
                if(value.length == 0){
                    value = '0';
                }
                var parts = value.split(".");
                if(parts[0].length > 1) {
                    parts[0] = parts[0].replace(/^[0]+/g, "");
                }

                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                var newval = parts.join(".");
                set(newval);
            }

            obj.bind('keydown.price_format', price_it);
            obj.bind('keyup.price_format', price_it);
            obj.bind('focusout.price_format', price_it);

            // If value has content
            if (get().length > 0) {
                price_it();
            }
        });
    };

    /**********************
     * Remove price format *
     ***********************/
    $.fn.unpriceFormat = function() {
        return $(this).unbind(".price_format");
    };

    /******************
     * Unmask Function *
     *******************/
    $.fn.unmask = function() {

        var field;
        var result = "";

        if ($(this).is('input'))
            field = $(this).val() || [];
        else
            field = $(this).html();

        for (var f = 0; f < field.length; f++) {
            if (!isNaN(field[f]) || field[f] == "-" || field[f] == ".") result += field[f];
        }

        return parseFloat(result);
    };
})(jQuery);