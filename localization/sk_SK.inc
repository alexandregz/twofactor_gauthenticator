<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Zapnúť';
$labels['twofactor_gauthenticator'] = 'Dvojstupňové overenie';
$labels['code'] = 'Autentifikačný kód';

$labels['two_step_verification_form'] = 'Kód dvojstupňového overenia:';

$labels['secret'] = 'Tajný kód';
$labels['qr_code'] = 'QR kód';
$labels['msg_infor'] = 'Zobrazený QR kód, obsahujúcí nastavenia pro dvojstupňové overenie, môžete naskenovať v TOTP kompatibilnej aplikácii jako je napr. <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) alebo <a href="https://en.wikipedia.org/wiki/Google_Authenticator" target="_blank">Google Authenticator</a> alebo <a href="https://authy.com/" target="_blank">Authy</a>';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Používateľská príručka</a>';

$labels['show_secret'] = 'Zobraziť tajný kód';
$labels['hide_secret'] = 'Skryť tajný kód';
$labels['create_secret'] = 'Vytvoriť tajný kód';

$labels['show_qr_code'] = 'Zobraziť QR kód';
$labels['hide_qr_code'] = 'Skryť QR kód';

$labels['recovery_codes'] = 'Kódy pre obnovenie';
$labels['show_recovery_codes'] = 'Zobraziť kódy pro obnovenie';
$labels['hide_recovery_codes'] = 'Skryť kódy pro obnovenie';

$labels['setup_all_fields'] = 'Vyplňte všetky polia (a uistite sa, že ste klikli na tlačidlo Uložiť)';

$labels['enrollment_dialog_title'] = 'Zapnutie dvojstupňového overenia';
$labels['enrollment_dialog_msg'] = '<strong>Kódy dvojstupňového overenia</strong> sú potrebné pre vyššie zabezpečenie. Teraz ich prosím nastavte.';

$labels['check_code'] = 'Skontrolovať kód';
$labels['code_ok'] = 'Správny kód';
$labels['code_ko'] = 'Neplatný kód';

$labels['dont_ask_me_30days'] = 'Nepýtať si na tomto zariadení kódy 30 dní ';

$labels['check_code_to_activate'] = 'Pre aktiváciu naskenujte QR kód v TOTP aplikácii (napr. Google Authenticator alebo Authy) a následne zadajte vygenerovaný kód.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Dvojstupňové overenie bolo úspešne zapnuté.';
