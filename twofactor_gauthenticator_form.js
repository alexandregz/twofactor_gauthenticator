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
    text += '<tr>';
    text += '<td class="title"><label for="2FA_code">'+rcmail.gettext('two_step_verification_form', 'twofactor_gauthenticator')+'</label></td>';
    text += '<td class="input"><input name="_code_2FA" id="2FA_code" size="10" autocapitalize="off" autocomplete="off" type="' + twoFactorCodeFieldType + '" maxlength="10"></td>';
    text += '</tr>';

    // remember option
    if(rcmail.env.allow_save_device_xdays){
		text += `<tr class="form-group row">
            <td class="title" style="display: none;">
              <label for="rcmloginuser">Username</label>
            </td>
            <td class="input input-group input-group-lg">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="remember_2FA" name="_remember_2FA" value="yes">
                <label class="custom-control-label" for="remember_2FA">`+rcmail.gettext('dont_ask_me_xdays', 'twofactor_gauthenticator').replace("%",rcmail.env.save_device_xdays)+`</label>
              </div>
            </td>
          </tr>`;
	 }


    // create textbox
    $('form > table > tbody:last').append(text);

    // focus
    $('#2FA_code').focus();
    
  });

};
