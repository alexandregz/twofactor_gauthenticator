<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Aktif Et';
$labels['twofactor_gauthenticator'] = 'İki Faktörlü Doğrulama (2FA)';
$labels['code'] = 'Kimlik Doğrulama Kodu';

$labels['two_step_verification_form'] = '2-Faktörlü Doğrulama Kodu:';

$labels['secret'] = 'Gizli Kod';
$labels['qr_code'] = 'QR Kodu';
$labels['msg_infor'] = 'İki faktörlü doğrulama için Android veya IOS İşletim sistemli telefonunuza Open Authenticator Uygulaması indirmeniz gereklidir.<br>
Android : <a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Uygulamaya git</a><br>
IOS : <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Uygulamaya git</a>';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Kullanıcı kılavuzu</a>';

$labels['show_secret'] = 'Gizli Kodu Göster';
$labels['hide_secret'] = 'Kodu Gizle';
$labels['create_secret'] = 'Giz kod oluştur';

$labels['show_qr_code'] = 'QR Kodu Göster';
$labels['hide_qr_code'] = 'QR Kodu Gizle';

$labels['recovery_codes'] = 'Kurtarma Kodu';
$labels['show_recovery_codes'] = 'Kurtarma Kodunu Göster';
$labels['hide_recovery_codes'] = 'Kurtarma Kodunu Gizle';

$labels['setup_all_fields'] = 'Tüm alanları doldurun (ayarlarınızı saklamak için kaydete tıkladığınızdan emin olun)';

$labels['enrollment_dialog_title'] = 'İki Faktörlü Doğrulama Kaydı';
$labels['enrollment_dialog_msg'] = '<strong>Daha fazla güvenlik için 2 Faktörlü kimlik doğrulama kodları</strong> gereklidir; lütfen bunları şimdi yapılandırın';

$labels['check_code'] = 'Kodu Kontrol Et';
$labels['code_ok'] = 'Kod Doğrulandı. Başarılı!';
$labels['code_ko'] = 'Kod Hatalı!';

$labels['dont_ask_me_30days'] = '30 gün boyunca bu bilgisayarda bana bir daha kod doğrulaması yapılmasın.';

$labels['check_code_to_activate'] = 'Kaydetmek için lütfen QR Kodunu tarayın ve geçerli 2 Faktörlü kodu aşağıya girin.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = '2 Faktörlü kimlik doğrulama ayarları başarıyla kaydedildi.';
