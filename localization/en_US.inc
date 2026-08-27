<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Activate';
$labels['twofactor_gauthenticator'] = '2-Factor Authentication';
$labels['code'] = 'Authentication Code';

$labels['two_step_verification_form'] = '2-Factor Authentication Code:';

$labels['secret'] = 'Secret';
$labels['qr_code'] = 'QR Code';
$labels['msg_infor'] = 'You can scan this QR code containing the 2-Factor settings using a TOTP compatible app such as <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) or <a href="https://github.com/google/google-authenticator" target="_blank">google-authenticator</a>';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">User manual</a>';

$labels['show_secret'] = 'Show secret';
$labels['hide_secret'] = 'Hide secret';
$labels['create_secret'] = 'Create secret';

$labels['show_qr_code'] = 'Show QR Code';
$labels['hide_qr_code'] = 'Hide QR Code';

$labels['recovery_codes'] = 'Recovery codes';
$labels['show_recovery_codes'] = 'Show recovery codes';
$labels['hide_recovery_codes'] = 'Hide recovery codes';

$labels['setup_all_fields'] = 'Fill all fields (make sure you click save to store your settings)';

$labels['enrollment_dialog_title'] = '2-Factor authentication enrollment';
$labels['enrollment_dialog_msg'] = '<strong>2-Factor authentication codes</strong> are required for increased security, please configure them now.';

$labels['check_code'] = 'Check code';
$labels['code_ok'] = 'Code OK';
$labels['code_ko'] = 'Incorrect code';

$labels['dont_ask_me_30days'] = 'Don&#39;t ask me codes again on this computer for 30 days';
$labels['managed_secret_enabled'] = 'Use externally managed TOTP secret';
$labels['managed_secret_provider'] = 'Secret provider';
$labels['managed_secret_reference'] = 'Secret reference';
$labels['managed_secret_status'] = 'Secret status';
$labels['managed_secret_available'] = 'Available and valid';
$labels['managed_secret_unavailable'] = 'Unavailable or invalid (authentication fails closed)';
$labels['managed_remember_device'] = 'Remember this device for 30 days';
$labels['managed_rate_limit'] = 'Application rate limit';
$labels['managed_rate_limit_value'] = '%d attempts in %d seconds; %d-second lockout';
$labels['managed_secret_notice'] = 'This configuration is managed by the server. The secret, QR code, recovery codes, and save controls are intentionally unavailable.';
$labels['yes'] = 'Yes';
$labels['no'] = 'No';

$labels['check_code_to_activate'] = 'To save, please scan the QR Code and enter the current 2-Factor code below.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = '2-Factor authentication settings saved successfully.';

