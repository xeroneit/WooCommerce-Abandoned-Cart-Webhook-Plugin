jQuery(document).ready(function($) {
    // Listen for changes to the billing phone field

    $("#billing-phone, #billing_phone").on('blur', function() {
        var phone_number = $(this).val();
        // Send an AJAX request to update the cart data with the phone number
        $.ajax({
            type: 'POST',
            url: ajax_obj.ajax_url,
            data: {
                action: 'update_cart_with_phone',
                billing_phone_number: phone_number
            },
            success: function(response) {
                console.log(response); // Check the response
            }
        });
    });

     $("#shipping-phone, #shipping_phone").on('blur', function() {
        var phone_number = $(this).val();
        // Send an AJAX request to update the cart data with the phone number
        $.ajax({
            type: 'POST',
            url: ajax_obj.ajax_url,
            data: {
                action: 'update_cart_with_phone',
                shipping_phone_number: phone_number
            },
            success: function(response) {
                console.log(response); // Check the response
            }
        });
    });
});
