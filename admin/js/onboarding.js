(function ($) {	
$(document).ready(function() {
		
		var current_step = $('#step_id').val();
		
		// show current step + all completed steps
		$('div[id="step'+current_step+'"]').show();
		$('div[id="step'+current_step+'"]').prevAll().filter('div').each(function() { 
			$(this).hide();
			$(this).filter('div[id*="completed"]').show();		
		});

		function goToStep2() {
                       $('#step1').slideUp(400, function() {
                             $('#step1_completed').slideDown(400, function() {
                                   $('#step2').fadeIn();
                             });
                       });
		}
	
		// open a new window when the signup button is clicked
		$('#signup_btn').on('click', function(e) {
			var signup_screen = window.open( $(this).attr('href'),'popUpWindow','height=650,width=1200,left=200,top=50,resizable=yes,scrollbars=no,toolbar=yes,menubar=no,location=no,directories=no, status=yes');
			var pollTimer = window.setInterval(function() {
				if (signup_screen.closed !== false) { // !== is required for compatibility with Opera
					window.clearInterval(pollTimer);
					goToStep2();
				}
			}, 200);
			e.preventDefault();
			$('#next').fadeIn();
		});
		
		// go to next step if the user already has an account
		$('#login_btn').on('click', function(e) {
			e.preventDefault();
			goToStep2();
		});



		$('#next').on('click', function() {
			goToStep2();
		});

	// check api key values
        $('#test_api_key').on('click', function() {
	    	$('#error-msg').hide()
		$.ajax( {
                   url: wpApiSettings.root + 'skeerel/v1/submit-api-key',
                   method: 'POST',
    		   beforeSend: function ( xhr ) {
        		xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
				$('#error-msg').html( '<img src="' + loader_url + '" width="20" style="margin-top:5px"/>' );
    		   	$('#error-msg').fadeIn();
			},
    		   data:{
        		'website_id' : $('#secret_id').val(),
                        'secret' : $('#secret_key').val()
    		   }
            } ).done( function ( response ) {
            	 $('#step2').slideUp(400, function() {
                      $('#step2_completed').slideDown(400, function() {
                            $('#step'+response).fadeIn();

			    if( response === '3_3' || response === '3_4' ) { // if the domain name doesn't match
				$.ajax( {
                		   url: wpApiSettings.root + 'skeerel/v1/get-authorized-domain-names',
                   	           method: 'GET',
                   	 	   beforeSend: function ( xhr ) {
                        		xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
                   		   },
            			} ).done( function (domains) {
					$('#step'+response+' span[id=authorized_domain_names]').text(domains);
				});

	    		    }					

			  submit_settings();
                      });
                 });  

	    } )
	      .error( function( response ) {
		$('#error-msg').text(response.responseJSON.message);
		$('#error-msg').fadeIn();
	      });

        });

	function submit_settings() {
	    $('[name="finish"]:visible').on('click', function() {
	        $.ajax( {
                       url: wpApiSettings.root + 'skeerel/v1/submit-settings',
                       method: 'POST',
                       beforeSend: function ( xhr ) {
                            xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
                       },
                       data:{
                       	'test-mode' : $('[name="test-mode"]:visible').is(":checked"),
						   'admin-mode' : $('[name="admin-mode"]:visible').is(":checked"),
						   'checkout-button' : $('[name="checkout-button"]:visible').is(":checked"),
						   'checkout-encart-button' : $('[name="checkout-encart-button"]:visible').is(":checked"),
						   'product-button' : $('[name="product-button"]:visible').is(":checked")
                       }
               }).done( function ( response ) {
                 $('[name="finish"]:visible').parents('.card').slideUp(400, function() {
                      $('#step3_completed').slideDown(400, function() {
                       	$('#loading').show();
						  window.scrollBy(0, 100); // scroll on mobile to display the loading bar
						  setTimeout(function(){
						  	window.location.href = './admin.php?page=wc-settings&tab=checkout'
						  }, 3000);
                      });
                 });
               })
              .error( function( response ) {
                console.log(response);
              });
	    });
	}

})
})(jQuery);
