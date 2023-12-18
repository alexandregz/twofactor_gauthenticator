if (window.rcmail) {
  rcmail.addEventListener('init', function() {
	  
	  var url_qr_code_values = 'otpauth://totp/Roundcube:' +$('#prefs-title').html().split(/ - /)[0]+ '?secret=' +$('#2FA_secret').get(0).value +'&issuer=%20'+window.location.hostname;
	  
	  var qrcode = new QRCode(document.getElementById("2FA_qr_code"), {
		    text: url_qr_code_values,
		    width: 200,
		    height: 200,
		    colorDark : rcmail.env.qr_image_colour,
		    colorLight : "#ffffff",
		    correctLevel : QRCode.CorrectLevel.M	//like charts.googleapis.com
		});

	$('#2FA_qr_code').prop('title', '');	// enjoy the silence (qrcode.js uses text to set title)
  });
}
