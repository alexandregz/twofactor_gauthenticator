2Steps Google verification
==========================

This RoundCube plugin adds the Google 2-step verification to the login proccess.

You can use [google-authenticator](https://code.google.com/p/google-authenticator/) and insert the secret generated into the config  if you want recovery codes.

Uses Michael Kliewe's [GoogleAuthenticator class](https://github.com/PHPGangsta/GoogleAuthenticator/)

form js from [dynalogin plugin](https://github.com/amaramrahul/dynalogin/)

Also thx to [Victor R. Rodriguez Dominguez](https://github.com/vrdominguez) for some ideas and support  


![Login](https://raw.github.com/AlexandreGZ/twofactor_gauthenticator/master/screenshots/001-login.png)

![2Steps](https://raw.github.com/AlexandreGZ/twofactor_gauthenticator/master/screenshots/002-2steps.png)


Installation
------------
- Clone from github:
    HOME_RC/plugins$ git clone [https://github.com/AlexandreGZ/twofactor_gauthenticator.git](https://github.com/AlexandreGZ/twofactor_gauthenticator.git)

- Activate the plugin into HOME_RC/config/main.inc.php:
    $rcmail_config['plugins'] = array('twofactor_gauthenticator');


Configuration
-------------
Go to Settings task and activate (and save) into "2steps Google verification" menu.

The plugin creates automatically the secret if you doesn't this.
	
To add accounts to the app, you can use the QR-Code (easy-way) or type the secret.

![Settings by default](https://raw.github.com/AlexandreGZ/twofactor_gauthenticator/master/screenshots/003-settings_default.png)

![Settings OK](https://raw.github.com/AlexandreGZ/twofactor_gauthenticator/master/screenshots/004-settings_ok.png)

![QR-Code example](https://raw.github.com/AlexandreGZ/twofactor_gauthenticator/master/screenshots/005-settings_qr_code.png)


Codes
-----
Codes have a 2*30 seconds clock tolerance, like by default (Maybe editable in future versions)


License
-------
GPLv2, see License

Notes
-----
Tested with RoundCube 0.9.5 and Google app

Author
------
Alexandre Espinosa Menor <aemenor@gmail.com>
=======
twofactor_gauthenticator

This RoundCube plugin adds the Google 2-step verification to the login proccess.
