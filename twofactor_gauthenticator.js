if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {

	  // ripped from PHPGansta/GoogleAuthenticator.php
		function createSecret(secretLength)
		{
			if(!secretLength) secretLength = 16;
			
		    LookupTable = new Array(
		            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
		            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
		            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
		            'Y', 'Z', '2', '3', '4', '5', '6', '7' // 31
		            //'='  // padding char
		        );
		
		    secret = '';
		    for (i = 0; i < secretLength; i++) {
		        secret += LookupTable[Math.floor(Math.random()*LookupTable.length)];
		    }
		    return secret;
		}

		
		// populate all fields
		function setup2FAfields() {
			if($('#2FA_secret').get(0).value) return;
			
			
			$('#twofactor_gauthenticator-form :input').each(function(){
				if($(this).get(0).type == 'password') $(this).get(0).type = 'text';
			});
			
			// secret button
			$('#2FA_create_secret').prop('id', '2FA_change_secret');
			$('#2FA_change_secret').get(0).value = rcmail.gettext('hide_secret', 'twofactor_gauthenticator');
			$('#2FA_change_secret').click(click2FA_change_secret);

			$('#2FA_activate').prop('checked', true);
			$('#2FA_show_recovery_codes').get(0).value = rcmail.gettext('hide_recovery_codes', 'twofactor_gauthenticator');
			$('#2FA_qr_code').slideDown();
			
			$('#2FA_secret').get(0).value = createSecret();
			$("[name^='2FA_recovery_codes']").each(function() {
				$(this).get(0).value = createSecret(10);
			});
			
			// add qr-code before msg_infor
			url_qr_code_values = encodeURIComponent('otpauth://totp/' +$('#prefs-title').html().split(/ - /)[1]+ '?secret=' +$('#2FA_secret').get(0).value);
			url_qr_code = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl='+url_qr_code_values;
			$('table tr:last').before('<tr><td>' +rcmail.gettext('qr_code', 'twofactor_gauthenticator')+ '</td><td><input type="button" class="button mainaction" id="2FA_change_qr_code" value="' 
					+rcmail.gettext('hide_qr_code', 'twofactor_gauthenticator')+ '"><div id="2FA_qr_code" style="display: visible"><img src="' +url_qr_code+ '" /></div></td></tr>');
			
			$('#2FA_change_qr_code').click(click2FA_change_qr_code);
		}
	  
	  $('#2FA_setup_fields').click(function(){
		  setup2FAfields();
	  });
	  
	  
	  // to show/hide secret
	  click2FA_change_secret = function(){
		  if($('#2FA_secret').get(0).type == 'text') {
			  $('#2FA_secret').get(0).type = 'password';
			  $('#2FA_change_secret').get(0).value = rcmail.gettext('show_secret', 'twofactor_gauthenticator');
		  }
		  else
		  {
			  $('#2FA_secret').get(0).type = 'text';
			  $('#2FA_change_secret').get(0).value = rcmail.gettext('hide_secret', 'twofactor_gauthenticator');
		  }
	  };
	  $('#2FA_change_secret').click(click2FA_change_secret);
	  
	  // to show/hide recovery_codes
	  $('#2FA_show_recovery_codes').click(function(){

		  if($("[name^='2FA_recovery_codes']")[0].type == 'text') {
			  $("[name^='2FA_recovery_codes']").each(function() {
				  $(this).get(0).type = 'password';
			  });
			  $('#2FA_show_recovery_codes').get(0).value = rcmail.gettext('show_recovery_codes', 'twofactor_gauthenticator');
		  }
		  else {
			  $("[name^='2FA_recovery_codes']").each(function() {
				  $(this).get(0).type = 'text';
			  });
			  $('#2FA_show_recovery_codes').get(0).value = rcmail.gettext('hide_recovery_codes', 'twofactor_gauthenticator');
		  }
	  });
	  
	  
	  // to show/hide qr_code
	  click2FA_change_qr_code = function(){
		  if( $('#2FA_qr_code').is(':visible') ) {
			  $('#2FA_qr_code').slideUp();
			  $(this).get(0).value = rcmail.gettext('show_qr_code', 'twofactor_gauthenticator');
		  }
		  else {
			$('#2FA_qr_code').slideDown();
		  	$(this).get(0).value = rcmail.gettext('hide_qr_code', 'twofactor_gauthenticator');
		  }
	  }
	  $('#2FA_change_qr_code').click(click2FA_change_qr_code);
	  
	  // create secret
	  $('#2FA_create_secret').click(function(){
		  $('#2FA_secret').get(0).value = createSecret();
	  });
	
	// ajax
	$('#2FA_check_code').click(function(){
		url = "./?_action=plugin.twofactor_gauthenticator-checkcode&code=" +$('#2FA_code_to_check').val() + '&secret='+$('#2FA_secret').val();
		$.post(url, function(data){
				alert(data);
			});
	});
	  
    
    // Define Variables
    var tabtwofactorgauthenticator = $('<span>').attr('id', 'settingstabplugintwofactor_gauthenticator').addClass('tablink');
    var button = $('<a>').attr('href', rcmail.env.comm_path + '&_action=plugin.twofactor_gauthenticator').html(rcmail.gettext('twofactor_gauthenticator', 'twofactor_gauthenticator')).appendTo(tabtwofactorgauthenticator);
    
    button.bind('click', function(e){ return rcmail.command('plugin.twofactor_gauthenticator', this) });

    // Button & Register commands
    rcmail.add_element(tabtwofactorgauthenticator, 'tabs');
    rcmail.register_command('plugin.twofactor_gauthenticator', function() { rcmail.goto_url('plugin.twofactor_gauthenticator') }, true);
    rcmail.register_command('plugin.twofactor_gauthenticator-save', function() {
    	if(!$('#2FA_secret').get(0).value) {
    		$('#2FA_secret').get(0).value = createSecret();
    	}
        rcmail.gui_objects.twofactor_gauthenticatorform.submit();
    }, true);
  });
}
