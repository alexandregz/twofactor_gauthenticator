<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'הפעל';
$labels['twofactor_gauthenticator'] = 'אימות דו-שלבי';
$labels['code'] = 'קוד מאמת גוגל';
$labels['two_step_verification_form'] = 'קוד אימות דו-שלבי:';
$labels['secret'] = 'קוד סודי';
$labels['qr_code'] = 'QR קוד';
$labels['msg_infor'] = 'תוכל לסרוד קוד QR זה המכיל הגדרות אימות דו-שלבי על ידי אפליקצייה התומכת ב-TOTP כמו <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) or <a href="https://github.com/google/google-authenticator" target="_blank">google-authenticator</a>';
$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">מדריך למשתמש</a>';
$labels['show_secret'] = 'הראה קוד סודי';
$labels['hide_secret'] = 'הסתר קוד סודי';
$labels['create_secret'] = 'צור קודי סודי';
$labels['show_qr_code'] = 'הראה קוד QR';
$labels['hide_qr_code'] = 'הסתר קוד QR';
$labels['recovery_codes'] = 'קודים לשחזור';
$labels['show_recovery_codes'] = 'הראה קודים לשחזור';
$labels['hide_recovery_codes'] = 'הסתר קודים לשחזור';
$labels['setup_all_fields'] = 'מלא את כל השדות (וודא שאתה לוחץ שמור כדי לאחסן את ההגדרות)';
$labels['enrollment_dialog_title'] = 'הרשמה לאימות דו-שלבי';
$labels['enrollment_dialog_msg'] = '<strong>קודים לאימות דו-שלבי</strong> נדרשים לאבטחה מירבית, אנא הגדר אותם כעת.';
$labels['check_code'] = 'בדוק קוד';
$labels['code_ok'] = 'הקוד תקין';
$labels['code_ko'] = 'הקוד לא תקין';
$labels['dont_ask_me_30days'] = 'אל תבקש ממני קודים אלו שוב במחשב זה למשך 30 יום';
$labels['check_code_to_activate'] = 'כדי לשמור, אנא סרוק את קוד ה-QR והכנס למטה את קוד האימות הדו-שלבי הנוכחי.';
// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'הגדרות האימות הדו-שלבי נשמרו בהצלחה.';
