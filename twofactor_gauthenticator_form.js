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
	  
    var text = '';
    text += '<tr>';
    text += '<td class="title"><label for="2FA_code">'+rcmail.gettext('two_step_verification_form', 'twofactor_gauthenticator')+'</label></td>';
    text += '<td class="input"><input name="_code_2FA" id="2FA_code" size="10" autocapitalize="off" autocomplete="off" type="text" maxlength="10" style="width: 100px;"></td>';
    text += '</tr>';

    // remember option
    text += '<tr>';
//    text += '<td colspan="2"><label style="color: #fefefe"><input type="checkbox" id="remember_2FA" name="_remember_2FA" value="yes"/>'+rcmail.gettext('dont_ask_me_30days', 'twofactor_gauthenticator')+'</label></td>';
    text += '<td class="title" colspan="2"><input type="checkbox" id="remember_2FA" name="_remember_2FA" value="yes"/>'+rcmail.gettext('dont_ask_me_30days', 'twofactor_gauthenticator')+'</td>';
    text += '</tr>';


    // create textbox
    $('form > table > tbody:last').append(text);

    // focus
    $('#2FA_code').focus();
    
  });

};
