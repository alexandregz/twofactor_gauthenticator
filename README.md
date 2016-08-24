2Steps verification
==========================

This RoundCube plugin adds the 2-step verification(OTP) to the login proccess.

It works with all TOTP applications [RFC 6238](https://www.rfc-editor.org/info/rfc6238)

Some code by:
[Ricardo Signes](https://github.com/rjbs)
[Justin Buchanan](https://github.com/jusbuc2k)
[Ricardo Iván Vieitez Parra](https://github.com/corrideat)


[GoogleAuthenticator class](https://github.com/PHPGangsta/GoogleAuthenticator/) by Michael Kliewe (to *see* secrets)

[qrcode.js](https://github.com/davidshimjs/qrcodejs) by ShimSangmin

Also thx to [Victor R. Rodriguez Dominguez](https://github.com/vrdominguez) for some ideas and support  



![Login](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/001-login.png)

![2Steps](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/002-2steps.png)


Installation
------------
- Clone from github:
    HOME_RC/plugins$ git clone [https://github.com/alexandregz/twofactor_gauthenticator.git](https://github.com/alexandregz/twofactor_gauthenticator.git)

- Activate the plugin into HOME_RC/config/config.inc.php:
    $config['plugins'] = array('twofactor_gauthenticator');


Configuration
-------------
Go to the Settings task and in the "2steps Google verification" menu, click 'Setup all fields (needs Save)'.

The plugin automatically creates the secret for you.
	
To add accounts to the app, you can use the QR-Code (easy-way) or type the secret.
After checking the first code click 'Save'.

![Settings by default](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/003-settings_default.png)

![Settings OK](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/004-settings_ok.png)

![QR-Code example](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/005-settings_qr_code.png)


Also, you can add "Recovery codes" for use one time (they delete when are used). Recovery codes are OPTIONAL, so they can be left blank.

![Recovery codes](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/006-recovery_codes.png) 


![Check codes](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/007-check_code.png) 



![Recovery codes](https://raw.github.com/alexandregz/twofactor_gauthenticator/master/screenshots/008-msg_infor_about_enrollment.png) 



Enrollment Users
----------------
If config value *force_enrollment_users* is true, **ALL** users needs to login with 2-step method. They receive alert message about that, and they can't skip without save configuration


Samefield
---------
If config value *2step_codes_on_login_form* is true, 2-step codes (and recovery) must be sended with password value, append to this, from the login screen: "Normal" codes just following password (passswordCODE), recovery codes after two pipes (passsword||RECOVERYCODE)

Actually only into samefield branch


Codes
-----
Codes have a 2*30 seconds clock tolerance, like by default with Google app (Maybe editable in future versions)


License
-------
MIT, see License

Notes
-----
Tested with RoundCube 0.9.5 and Google app. Also with Roundcube 1.0.4

Remember, sync time it's essential for TOTP: "For this to work, the clocks of the user's device and the server need to be roughly synchronized (the server will typically accept one-time passwords generated from timestamps that differ by ±1 from the client's timestamp)" (from http://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm)

Author
------
Alexandre Espinosa Menor <aemenor@gmail.com>

Issues
------
Open issues using github, don't send me emails about that, please -usually Gmail marks messages like SPAM

Testing
-------
You can use https://github.com/alexandregz/vagrant-twofactor_gauthenticator

Using with Kolab
----------------
Add a symlink into the public_html/assets directory

Show explained https://github.com/alexandregz/twofactor_gauthenticator/issues/29#issuecomment-156838186 by https://github.com/d7415

Client implementations
----------------------

You can use various [TOTP clients](https://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm#Client_implementations) -link by https://github.com/helmo


Logs
----

Suggested by simon@magrin.com

To log errors with bad codes, change the $_enable_logs variable to true.

The logs are stored to the file HOME_RC/logs/log_errors_2FA.txt -directory must be created



Whitelist
---------

You can define whitelist IPs into config file (see config.inc.php.dist) to automatic login -the plugin don't ask you for code


Uninstall
---------

To deactivate the plugin, you can use two methods:

- To only one user: restore the user prefs from DB to null (rouncubeDB.users.prefs) -the user plugin options stored there.

- To all: remove the plugin from config.inc.php/remove the plugin itself
