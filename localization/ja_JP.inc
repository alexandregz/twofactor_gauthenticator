<?php

// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = '有効';
$labels['twofactor_gauthenticator'] = 'Google二段階認証';
$labels['code'] = '認証コード';

$labels['two_step_verification_form'] = '二段階認証';

$labels['secret'] = '秘密鍵';
$labels['qr_code'] = 'QRコード';
$labels['msg_infor'] = 'ここに <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) または <a href="https://github.com/google/google-authenticator" target="_blank">google-authenticator</a> で作成した<i>秘密鍵</i>を追加して使用できます';
$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">ユーザーマニュアル</a>';
$labels['show_secret'] = '秘密鍵表示';
$labels['hide_secret'] = '秘密鍵非表示';
$labels['create_secret'] = '秘密鍵作成';

$labels['show_qr_code'] = 'QRコード表示';
$labels['hide_qr_code'] = 'QRコード非表示';

$labels['recovery_codes'] = 'リカバリーコード';
$labels['show_recovery_codes'] = 'リカバリーコード表示';
$labels['hide_recovery_codes'] = 'リカバリーコード非表示';

$labels['setup_all_fields'] = '全フィールド設定(保存必要)';

$labels['enrollment_dialog_title'] = 'Google二段階認証';
$labels['enrollment_dialog_msg'] = '<strong>二段階認証</strong>の設定が必要です。設定を行ってください';

$labels['check_code'] = 'コードチェック';
$labels['code_ok'] = 'コードOK';
$labels['code_ko'] = 'コードが違ってます';

$labels['dont_ask_me_30days'] = '子の端末で以後30日間コードを求めるな';

$labels['check_code_to_activate'] = '保存する前にコードを確認してください';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = '設定が正しく保存されました';
