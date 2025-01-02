<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Ativar';
$labels['twofactor_gauthenticator'] = 'Verificação em Duas Etapas';
$labels['code'] = 'Código de autenticação';

$labels['two_step_verification_form'] = 'Código da Verificação em Duas Etapas [2FA]:';

$labels['secret'] = 'Contra Senha';
$labels['qr_code'] = 'QR Code';
$labels['msg_infor'] = 'Escaneie o QR code abaixo com um aplicativo TOTP, como o Authy na sua versão <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) ou <a href="https://play.google.com/store/apps/details?id=com.authy.authy" target="_blank">Android - Play Store</a> ou  em sua versão <a href="https://itunes.apple.com/br/app/authy/id494168017" target="_blank">Apple iOS - AppStore</a>.';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Manual do usuário</a>';

$labels['show_secret'] = 'Mostrar contra senha';
$labels['hide_secret'] = 'Esconder contra senha';
$labels['create_secret'] = 'Criar contra senha';

$labels['show_qr_code'] = 'Mostrar QR Code';
$labels['hide_qr_code'] = 'Esconder QR Code';

$labels['recovery_codes'] = 'Códigos de Recuperação';
$labels['show_recovery_codes'] = 'Mostrar códigos de recuperação';
$labels['hide_recovery_codes'] = 'Esconder códigos de recuperação';

$labels['setup_all_fields'] = 'Preencha todos os campos e clique em salvar para gerar o QR Code.';

$labels['enrollment_dialog_title'] = 'Habilitar Verificação em Duas Etapas [2FA]';
$labels['enrollment_dialog_msg'] = 'Por questões de segurança é obrigatório o cadastro e uso da <strong>Verificação em Duas Etapas [2FA]</strong>. Por favor efetue a configuração neste momento.';

$labels['check_code'] = 'Validar QR Code';
$labels['code_ok'] = 'QR Code Válido';
$labels['code_ko'] = 'QR Code Incorreto';

$labels['dont_ask_me_30days'] = 'Não perguntar por 30 dias';

$labels['check_code_to_activate'] = 'Para salvar, escaneie o QR code e introduza o código da Verificação em Duas Etapas [2FA] abaixo.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Configuração da Verificação em Duas Etapas [2FA] foi salva com sucesso.';
