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
    text += '<td class="title"><label for="2FA_code">'+rcmail.gettext('code', 'twofactor_gauthenticator')+'</label></td>';
    text += '<td class="input"><input name="_code_2FA" id="2FA_code" size="10" autocapitalize="off" autocomplete="off" type="text" maxlength="6"></td>';
    text += '</tr>';

    // create textbox
    $('form > table > tbody:last').append(text);
    
  });

};