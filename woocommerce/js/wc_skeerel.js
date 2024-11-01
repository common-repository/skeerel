(function ($) {	
$(document).ready(function() {
	// load lang settings
	var test_apikey_btn = $('#lang-test-apikey').val();
	var placeholder_skeerel_id = $('#lang-api-placeholder').val();

	// make some adjustements to woocommerce payment settings page
	$('label[for="woocommerce_skeerel_display_skeerel_on_product_page"]').parents('fieldset').css('margin-top', '-20px');
	$('#woocommerce_skeerel_skeerel_id').parents('tr').before('<br>');

	// add a button to check api credentials
	$('#woocommerce_skeerel_skeerel_secret_key').parents('tr').after("<tr valign='top'><th scope='row' class='titledesc'></th><td class='forminp'><div id='check_api' class='button-secondary' style='margin-top:-15px'>" + test_apikey_btn + "</div></td><br>");
	$('#check_api').before("<div id='error-msg' class='error-msg'></div>");

	// placeholders
	$('#woocommerce_skeerel_skeerel_secret_key').attr("placeholder", "*******************************");
	$('#woocommerce_skeerel_skeerel_id').attr("placeholder", placeholder_skeerel_id );

	$('#woocommerce_skeerel_skeerel_secret_key').addClass('form-control');
	$('#woocommerce_skeerel_skeerel_secret_key').password();

	$.ajax( {
                   url: wpApiSettings.root + 'skeerel/v1/get-custom-checkbox',
                   method: 'GET',
                   beforeSend: function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
                   }
                }).done( function ( response ) {
		   $.each(response, function(i, enabled) {
   			if ( !enabled ) 
			  $('#woocommerce_skeerel_'+i).attr('disabled', true);
		   });
                })
              .error( function( response ) {
               // disable all checkboxes
		$('#woocommerce_skeerel_display_skeerel_at_checkout').attr('disabled', true);
		$('#woocommerce_skeerel_display_skeerel_encart_at_checkout').attr('disabled', true);
		$('#woocommerce_skeerel_display_skeerel_on_product_page').attr('disabled', true);
		$('#woocommerce_skeerel_admin_only').attr('disabled', true);
		$('#woocommerce_skeerel_use_test_mode').attr('disabled', true);
	 });

	$('#check_api').on('click', function() {
		$('#error-msg').hide()
		$.ajax( {
                   url: wpApiSettings.root + 'skeerel/v1/check-api-key',
                   method: 'POST',
    		   beforeSend: function ( xhr ) {
        		xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			$('#check_api').hide();
			$('#error-msg').html( '<img src="'+ loader_url +'" width="20" style="margin-top:5px"></img>' );
			$('#error-msg').fadeIn();
    		   },
    		   data:{
        		'website_id' : $('#woocommerce_skeerel_skeerel_id').val(),
                        'secret' : $('#woocommerce_skeerel_skeerel_secret_key').val()
    		   }
            	}).done( function ( response ) {
            	 $('#error-msg').css('color', 'green'); 
		 $('#error-msg').text(response.message);
		 $('#error-msg').fadeIn();

		 $('#check_api').fadeIn();  
	        })
	      .error( function( response ) {
		$('#error-msg').css('color', 'red');
		$('#error-msg').text(response.responseJSON.message);
		$('#error-msg').fadeIn();

		$('#check_api').fadeIn();
	      });
	});
});
})(jQuery);	
