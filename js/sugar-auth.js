( function ($)
{
    $('.element').on( 'submit', function(e) {
        e.preventDefault();
        var username = $('input[name=log]').val();

        $.post( swaajax.ajaxurl, {
                // wp ajax action
                action : 'swa_forgot_password_action',
                // vars
                user_name : username,
                // send the nonce along with the request
                primeNonce : swaajax.primeNonce
            }, function( response ) {
                var message = JSON.parse(response);

                if ( message.error != '' ) {
                    alert(message.error);
                } else {
                    alert(message.result);
                }
            }
        );
        return false;
    });

}(jQuery));