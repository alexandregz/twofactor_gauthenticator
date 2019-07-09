if (window.rcmail) {
  rcmail.addEventListener('init', function() {
	// remove the user/password/... input from login
    $('form > table > tbody > tr').each(function(){
    	$(this).remove();
    });
	
    // change task & action
    $('form').attr('action', './');
    $('input[name=_task]').attr('value', 'mail');
    $('input[name=_action]').attr('value', '');

	//determine twofactor field type based on config settings
	if(rcmail.env.twofactor_formfield_as_password)
		var twoFactorCodeFieldType = 'password';
	else
		var twoFactorCodeFieldType = 'text';
	
	//twofactor input form
    var text = '';
    text += '<tr><td class="title" colspan="2"><label for="2FA_code">'+rcmail.gettext('two_step_verification_form', 'twofactor_gauthenticator')+'</label></td></tr>';
    text += '<tr><td class="input" colspan="2"><input class="form-control" name="_code_2FA" id="2FA_code" size="10" autocapitalize="off" autocomplete="off" type="' + twoFactorCodeFieldType + '" maxlength="10"></td></tr>';

    // remember option
    if(rcmail.env.allow_save_device_30days){
		text += '<tr>';
		text += '<td class="title" colspan="2"><div class="form-check"><input type="checkbox" class="form-check-input" id="remember_2FA" name="_remember_2FA" value="yes"/> <label class="form-check-label" for="remember_2FA">'+rcmail.gettext('dont_ask_me_30days', 'twofactor_gauthenticator')+'</label></div></td>';
		text += '</tr>';
	}

    // create textbox
    $('form > table > tbody:last').append(text);

    // focus
    $('#2FA_code').focus();
    
  });

};
