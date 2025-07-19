<?php
// 插件不同部分使用的标签
$labels = array();
$labels['activate'] = '激活';
$labels['twofactor_gauthenticator'] = '双因素身份验证';
$labels['code'] = '验证码';

$labels['two_step_verification_form'] = '双因素验证码：';

$labels['secret'] = '密钥';
$labels['qr_code'] = '二维码';
$labels['msg_infor'] = '您可以使用兼容TOTP的应用（如 <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a>（<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play 商店</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>）或 <a href="https://github.com/google/google-authenticator" target="_blank">Google Authenticator</a>）扫描此包含双因素设置的二维码。';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">用户手册</a>';

$labels['show_secret'] = '显示密钥';
$labels['hide_secret'] = '隐藏密钥';
$labels['create_secret'] = '创建密钥';

$labels['show_qr_code'] = '显示二维码';
$labels['hide_qr_code'] = '隐藏二维码';

$labels['recovery_codes'] = '恢复代码';
$labels['show_recovery_codes'] = '显示恢复代码';
$labels['hide_recovery_codes'] = '隐藏恢复代码';

$labels['setup_all_fields'] = '填写所有字段（请确保点击保存以存储您的设置）';

$labels['enrollment_dialog_title'] = '双因素身份验证注册';
$labels['enrollment_dialog_msg'] = '为增强安全性，需要使用<strong>双因素身份验证码</strong>，请立即配置。';

$labels['check_code'] = '检查代码';
$labels['code_ok'] = '代码正确';
$labels['code_ko'] = '代码不正确';

$labels['dont_ask_me_30days'] = '30天内在这台电脑上不再询问验证码';

$labels['check_code_to_activate'] = '为了激活，请扫描二维码并在下方输入当前的双因素验证码。';

// 插件不同部分使用的消息
$messages = array();
$messages['successfully_saved'] = '双因素身份验证设置已成功保存。';