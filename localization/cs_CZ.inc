<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Zapnout';
$labels['twofactor_gauthenticator'] = 'Dvoufázové ověření';
$labels['code'] = 'Ověření kód';

$labels['two_step_verification_form'] = 'Kód dvoufázového ověření:';

$labels['secret'] = 'Tajný kód';
$labels['qr_code'] = 'QR kód';
$labels['msg_infor'] = 'Zobrazený QR kód, obsahující nastavení pro dvoufázové ověření, můžete použít v TOTP kompatibilní aplikaci jako je <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) nebo <a href="https://github.com/google/google-authenticator" target="_blank">google-authenticator</a>.';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Uživatelská příručka</a>';

$labels['show_secret'] = 'Zobrazit tajný kód';
$labels['hide_secret'] = 'Skrýt tajný kód';
$labels['create_secret'] = 'Vytvořit tajný kód';

$labels['show_qr_code'] = 'Zobrazit QR kód';
$labels['hide_qr_code'] = 'Skrýt QR kód';

$labels['recovery_codes'] = 'Kódy pro obnovení';
$labels['show_recovery_codes'] = 'Zobrazit kódy pro obnovení';
$labels['hide_recovery_codes'] = 'Skrýt kódy pro obnovení';

$labels['setup_all_fields'] = 'Vyplňte všechna pole (a ujistěte se, že jste klikli na tlačítko Uložit)';

$labels['enrollment_dialog_title'] = 'Registrace dvoufázového ověření';
$labels['enrollment_dialog_msg'] = '<strong>Kódy dvoufázového ověření</strong> jsou vyžadovány pro vyšší zabezpečení. Prosím, nastavte je nyní.';

$labels['check_code'] = 'Zkontrolovat kód';
$labels['code_ok'] = 'Správný kód';
$labels['code_ko'] = 'Špatný kód';

$labels['dont_ask_me_30days'] = 'Neptat se na tomto stroji znovu na kódy následujích 30 dnů';

$labels['check_code_to_activate'] = 'Pro uložení, naskenujte QR kód a vložte následně vygenerovaný dvoufázový kód níže.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Nastavení dvoufázového ověření bylo úspěšně uloženo.';

