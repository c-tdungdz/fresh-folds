jQuery(document).ready(function() {


    jQuery("#input_19_159").on('change', function() {
        let price = jQuery('#input_19_159').val();
        let minimum_price = parseInt(jQuery('#minimum-order').val());
        let left;
        if (price < minimum_price) {
            left = minimum_price - price;
            jQuery('#field_19_397').show();
            jQuery('#left-order').text('£ ' + left.toFixed(2));
            jQuery('#gform_next_button_19_144').prop('disabled', true);
        } else {
            jQuery('#field_19_397').hide();
            jQuery('#gform_next_button_19_144').prop('disabled', false);
        }
    });

    jQuery("#gform_submit_button_19").on('click', function() {
        var username = jQuery("#input_19_305").val();
        var username_register = jQuery("#input_19_156").val();
        if (username == "") {
            jQuery("#input_19_305").val(username_register);
        }
    })

});