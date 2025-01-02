<?php
// Labels used for different portions of the plugin
$labels = array();
$labels['activate'] = 'Ενεργοποίηση';
$labels['twofactor_gauthenticator'] = 'Διπλή Πιστοποίηση <br>(2-Factor Authentication)';
$labels['code'] = 'πιστοποίηση TOTP';

$labels['two_step_verification_form'] = 'Δπλή πιστοποίηση (2FA):';

$labels['secret'] = 'Μυστικό Κλειδί (Secret)';
$labels['qr_code'] = 'QR Code';
$labels['msg_infor'] = 'Μπορείτε να σαρώσετε τον QR code που περιέχει της ρυθμίσεις διπλής πιστοποίησης (2FA) με μια εφαρμογή TOTP (TOTP app) όπως ο <a href="https://github.com/Skyost/OpenAuthenticator" target="_blank">OpenAuthenticator</a> (<a href="https://play.google.com/store/apps/details?id=app.openauthenticator" target="_blank">Play Store</a> | <a href="https://apps.apple.com/us/app/open-authenticator-by-skyost/id6479272927" target="_blank">Istore</a>) ή <a href="https://github.com/google/google-authenticator" target="_blank">google-authenticator</a>';

$labels['msg_help'] = '<a href="https://github.com/alexandregz/twofactor_gauthenticator/tree/master?tab=readme-ov-file#usage" target="_blank">Εγχειρίδιο χρήσης</a>';

$labels['show_secret'] = 'Εμφάνιση Κλειδιού';
$labels['hide_secret'] = 'Απόκρυψη Κλειδιού';
$labels['create_secret'] = 'Δημιουργία Κλειδιού';

$labels['show_qr_code'] = 'Εμφάνιση  QR Code';
$labels['hide_qr_code'] = 'Απόκρυψη QR Code';

$labels['recovery_codes'] = 'Κωδικοί ανάκτησης';
$labels['show_recovery_codes'] = 'Εμφάνιση Κωδικών Ανάκτησης';
$labels['hide_recovery_codes'] = 'Άπόκρυψη Κωδικών Ανάκτησης';

$labels['setup_all_fields'] = 'Συμπλήρωση όλων των πεδίων<br>(Θα πρέπει να πατήσετε Αποθήκευση για την καταχώρηση των ρυθμίσεων σας)';

$labels['enrollment_dialog_title'] = '2-Factor authentication enrollment';
$labels['enrollment_dialog_msg'] = '<strong>H διπλή πιστοποίηση (2-Factor authentication)</strong> απαιτείται για αυξημένη ασφάλεια. Μπορείτε να την ορίσετε τώρα.';

$labels['check_code'] = 'Έλεγχος Κωδικού';
$labels['code_ok'] = 'Ο Κωδικός επαληθέφτηκε';
$labels['code_ko'] = 'Λάθος Κωδικός';

$labels['dont_ask_me_30days'] = 'Να μην ρωτηθώ πάλι για κωδικούς για τις επόμενες τριάντα (30) ημέρες';

$labels['check_code_to_activate'] = ' Για την αποθήκευση, παρακαλούμε να σαρώσετε τον κωδικό QR και να εισάγετε τον τρέχοντα κωδικό (2-Factor code) πιο κάτω.';

// Messages used for the different portions of the plugin
$messages = array();
$messages['successfully_saved'] = 'Οι ρυθμίσεις διπλής πιστοποίσης καταχωρήθηκαν επιτυχώς.';
