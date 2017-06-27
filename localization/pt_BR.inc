<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Ativar';
$labels['twofactor_gauthenticator'] = 'Autenticação de 2 Fatores';
$labels['code'] = 'Google Authenticator Code';

$labels['two_step_verification_form'] = 'Código da Autenticação de 2 Fatores:';

$labels['secret'] = 'Contra Senha';
$labels['qr_code'] = 'QR Code';
$labels['msg_infor'] = 'Você pode escanear este QR code contendo suas preferências utilizando um aplicativo TOTP, como: <a href="https://github.com/google/google-authenticator" target="_blank">google-authenticator</a>';

$labels['show_secret'] = 'Vizualisar contra senha';
$labels['hide_secret'] = 'Esconder contra senha';
$labels['create_secret'] = 'Criar contra senha';

$labels['show_qr_code'] = 'Exibir QR Code';
$labels['hide_qr_code'] = 'Esconder QR Code';

$labels['recovery_codes'] = 'Recuperar códigos';
$labels['show_recovery_codes'] = 'Exibir códigos recuperados';
$labels['hide_recovery_codes'] = 'Esconder códigos recuperados';

$labels['setup_all_fields'] = 'Preencha todos os campos (Clique em salvar para armazenar suas preferências)';

$labels['enrollment_dialog_title'] = 'Registro da Autenticação de 2 Fatores';
$labels['enrollment_dialog_msg'] = '<strong>Código da Autenticação de 2 Fatores.</strong> Para sua securança, por favor, configure neste momento.';

$labels['check_code'] = 'Validar QR code';
$labels['code_ok'] = 'QR code Válido';
$labels['code_ko'] = 'QR code Incorreto';

$labels['dont_ask_me_30days'] = 'Não perguntar por validação para este computador por 30 dias';

$labels['check_code_to_activate'] = 'Para salvar, escanei o QR code e introduza o código da Autenticação de 2 Fatores abaixo.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Configuração da autenticação de 2 fatores foi salva com sucesso.';
