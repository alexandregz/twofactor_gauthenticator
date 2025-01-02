<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Aktivieren';
$labels['twofactor_gauthenticator'] = 'Zwei-Faktor-Authentifizierung';
$labels['code'] = 'Authentifizierung-Code';

$labels['two_step_verification_form'] = 'Zwei-Faktor-Best&auml;tigungscode';

$labels['secret'] = 'Secret';
$labels['qr_code'] = 'QR-Code';
$labels['msg_infor'] = 'Sie k&ouml;nnen mit jeder TOTP kompatiblen App wie z.B. <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) oder <a href="https://github.com/google/google-authenticator" target="_blank">Google-Authenticator</a> ein Secret erstellen und dieses verwenden.';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Benutzerhandbuch</a>';

$labels['show_secret'] = 'Zeige Secret';
$labels['hide_secret'] = 'Verstecke Secret';
$labels['create_secret'] = 'Erstelle Secret';

$labels['show_qr_code'] = 'Zeige QR-Code';
$labels['hide_qr_code'] = 'Verstecke QR-Code';

$labels['recovery_codes'] = 'Wiederherstellungscodes';
$labels['show_recovery_codes'] = 'Zeige Wiederherstellungscodes';
$labels['hide_recovery_codes'] = 'Verstecke Wiederherstellungscodes';

$labels['setup_all_fields'] = 'Erstelle fehlende Werte (Speichern klicken, um Ihre Einstellungen zu sichern)';

$labels['enrollment_dialog_title'] = 'Zwei-Faktor-Authentifizierung';
$labels['enrollment_dialog_msg'] = '<strong>Best&auml;tigungscodes f&uuml;r Zwei-Faktor-Authentifizierung</strong> werden aus Sicherheitsgr&uuml;nden ben&ouml;tigt, bitte konfigurieren!';

$labels['check_code'] = '&Uuml;berpr&uuml;fe Code';
$labels['code_ok'] = 'Code OK';
$labels['code_ko'] = 'Falscher Code';

$labels['dont_ask_me_30days'] = 'Nicht erneut nach dem Code fragen f&uuml;r die n&auml;chsten 30 Tage';

$labels['check_code_to_activate'] = 'Um zu speichern, muss mindestens 1 Code zuvor gepr&uuml;pft werden';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Konfiguration erfolgreich gespeichert.';
