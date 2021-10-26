if (window.rcmail) {
  rcmail.addEventListener('init', function() {

	  var elem = $('#prefs-title');
	  var label = '';
	  if (elem.length) {
		  label = elem.html().split(/ - /)[1];
	  }
	  var url_qr_code_values = 'otpauth://totp/Roundcube:' + label + '?secret=' +$('#2FA_secret').get(0).value +'&issuer=RoundCube2FA%20'+window.location.hostname;
	  $('#2FA_qr_code').empty();
	  
	  var qrcode = new QRCode(document.getElementById("2FA_qr_code"), {
		    text: url_qr_code_values,
		    width: 200,
		    height: 200,
		    colorDark : "#000000",
		    colorLight : "#ffffff",
		    correctLevel : QRCode.CorrectLevel.M	// like charts.googleapis.com
		});

	$('#2FA_qr_code').prop('title', '');	// enjoy the silence (qrcode.js uses text to set title)
  });
}
