(function ( $ ) {
    'use strict';

    var rg = {};

    rg.conditional_payment_mode_display_onload = function () {
        let test_mode_value = $('input[name="rave_enable_test_mode"]').is(':checked');
        if(test_mode_value) {
            $('.give-rave-check-mode.test-key').show();
            $('.give-rave-check-mode.live-key').hide();
        } else {
            $('.give-rave-check-mode.test-key').hide();
            $('.give-rave-check-mode.live-key').show();
        }
        console.log(test_mode_value);
    }

    rg.init = function () {
        $(document).on('change', 'input[name="rave_enable_test_mode"]', function () {
            rg.conditional_payment_mode_display_onload()
        });
        rg.conditional_payment_mode_display_onload();
    }

    $(window).on('load', rg.init);

})(jQuery);