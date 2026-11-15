<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Käytössä';
$labels['twofactor_gauthenticator'] = 'Kaksivaiheinen tunnistautuminen';
$labels['code'] = 'Tunnistuskoodi';

$labels['two_step_verification_form'] = 'Kaksivaiheisen tunnistautumisen koodi:';

$labels['secret'] = 'Suojausavain';
$labels['qr_code'] = 'QR-koodi';
$labels['msg_infor'] = 'Voit lukea tämän QR-koodin, joka sisältää kaksivaiheisen tunnistautumisen asetukset, TOTP-yhteensopivalla sovelluksella, kuten <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Google Play</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">App Store</a>) tai <a href="https://github.com/google/google-authenticator" target="_blank">Google Authenticator</a>';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Käyttöohje (englanniksi)</a>';

$labels['show_secret'] = 'Näytä suojausavain';
$labels['hide_secret'] = 'Piilota suojausavain';
$labels['create_secret'] = 'Luo suojausavain';

$labels['show_qr_code'] = 'Näytä QR-koodi';
$labels['hide_qr_code'] = 'Piilota QR-koodi';

$labels['recovery_codes'] = 'Varakoodit';
$labels['show_recovery_codes'] = 'Näytä varakoodit';
$labels['hide_recovery_codes'] = 'Piilota varakoodit';

$labels['setup_all_fields'] = 'Täytä kaikki kentät (muista tallentaa asetukset)';

$labels['enrollment_dialog_title'] = 'Kaksivaiheisen tunnistautumisen käyttöönotto';
$labels['enrollment_dialog_msg'] = '<strong>Kaksivaiheisen tunnistautumisen koodit</strong> vaaditaan tietoturvan parantamiseksi. Määritä ne nyt.';

$labels['check_code'] = 'Tarkista koodi';
$labels['code_ok'] = 'Koodi on oikein';
$labels['code_ko'] = 'Virheellinen koodi';

$labels['dont_ask_me_30days'] = 'Älä kysy koodia tällä tietokoneella 30 päivään';

$labels['check_code_to_activate'] = 'Tallentaaksesi lue QR-koodi ja syötä alle sovelluksen tämänhetkinen koodi.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Kaksivaiheisen tunnistautumisen asetukset tallennettu.';
