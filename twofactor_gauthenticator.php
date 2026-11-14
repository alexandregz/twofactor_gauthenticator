<?php

/**
 * Two-factor Google Authenticator for RoundCube
 *
 * This RoundCube plugin adds the 2-step verification(OTP) to the login proccess
 *
 * @version 2.0.0
 * @author Alexandre Espinosa <aemenor@gmail.com>
 *
 * Some ideas and code: Ricardo Signes <rjbs@cpan.org>, Ricardo Iván Vieitez Parra (https://github.com/corrideat), Justin Buchanan (https://github.com/jusbuc2k)
 * 	, https://github.com/pokrface, Peter Tobias, Víctor R. Rodríguez Domínguez (https://github.com/vrdominguez), etc.
 * Date: 2013-11-30
 */
require_once 'PHPGangsta/GoogleAuthenticator.php';

require_once 'CIDR.php';

class twofactor_gauthenticator extends rcube_plugin
{
    private $_number_recovery_codes = 4;

    // relative to $config['log_dir']
    private $_logs_file = 'log_errors_2FA.txt';

    public function init()
    {
        $rcmail = rcmail::get_instance();

        // Completely block AJAX requests for unauthenticated users (by Stephen K. Gielda <security@codamail.com>)
        if (!$rcmail->user->ID && !isset($_SESSION['twofactor_gauthenticator_login']) && isset($_REQUEST['_remote'])) {

            // Direct JSON response to prevent leakage
            header('Content-Type: application/json');
            echo json_encode(array(
                'error' => 'Session expired or invalid',
                'redirect' => '?_task=login&_err=session'
            ));
            exit;
        }

        // hooks
        $this->add_hook('login_after', array($this, 'login_after'));
        $this->add_hook('send_page', array($this, 'check_2FAlogin'));
        $this->add_hook('ready', array($this, 'enforce_2FA'));
        $this->add_hook('render_page', array($this, 'popup_msg_enrollment'));

        $this->load_config();
        $this->add_texts('localization/', true);
        // Earlier than index.php login/logout branches and all action dispatch.
        $this->__enforcePendingRequest($rcmail->task, $rcmail->action, false);

        $allowedPlugin = $this->__pluginAllowedByConfig();

        // skipping all logic and plugin not appears
        if (!$allowedPlugin) {
            return false;
        }

        $this->add_texts('localization/', true);

        // check code with ajax
        $this->register_action('plugin.twofactor_gauthenticator-checkcode', array($this, 'checkCode'));

        // config
        $this->register_action('twofactor_gauthenticator', array($this, 'twofactor_gauthenticator_init'));
        $this->register_action('plugin.twofactor_gauthenticator-save', array($this, 'twofactor_gauthenticator_save'));
        $this->include_script('twofactor_gauthenticator.js');
        $this->include_script('qrcode.min.js');

        // settings we will export to the form javascript
        //$this_output = $this->api->output;
        //if ($this_output) {
        //	$this->api->output->set_env('allow_save_device_30days',$rcmail->config->get('allow_save_device_30days',true));
        //	$this->api->output->set_env('twofactor_formfield_as_password',$rcmail->config->get('twofactor_formfield_as_password',false));
        //}
    }

    // check if user are valid from config.inc.php or true (by default) if config.inc.php not exists
    public function __pluginAllowedByConfig()
    {
        $rcmail = rcmail::get_instance();

        $this->load_config();

        // users allowed to use plugin (not showed for others!).
        //	-- From config.inc.php file.
        //  -- You can use regexp: admin.*@domain.com
        $users = $rcmail->config->get('users_allowed_2FA');
        if (is_array($users)) {		// exists "users" from config.inc.php
            foreach ($users as $u) {
                if (isset($rcmail->user->data['username'])) {
                    preg_match("/$u/", $rcmail->user->data['username'], $matches);

                    if (isset($matches[0])) {
                        return true;
                    }
                }
            }

            // not allowed for all, except explicit
            return false;
        }

        // by default, all users have plugin activated
        return true;
    }

    /** Scope is server-side user identity/configuration, never request parameters. */
    private function __is2FAInScope()
    {
        $rcmail = rcmail::get_instance();
        return $rcmail->user->ID && $this->__pluginAllowedByConfig();
    }

    // Use the form login, but removing inputs with jquery and action (see twofactor_gauthenticator_form.js)
    public function login_after($args)
    {
        $rcmail = rcmail::get_instance();
        if (!$this->__is2FAInScope()) {
            return; // Excluded users keep native Roundcube login/session behavior.
        }
        $this->__remove2FASession();
        $_SESSION['twofactor_gauthenticator_login'] = time();


        $config_2FA = self::__get2FAconfig();
        if (!($config_2FA['activate'] ?? false)) {
            if ($rcmail->config->get('force_enrollment_users')) {
                $this->__goingRoundcubeTask('settings', 'plugin.twofactor_gauthenticator');
            }
            return;
        }

        if ($this->__apply2FAException()) {
            $this->__goingRoundcubeTask('mail');
        }
        $this->__show2FAChallenge();
    }

    /** Final pre-dispatch check, including task/action changes by earlier hooks. */
    public function enforce_2FA($args)
    {
        $this->__enforcePendingRequest($args['task'], $args['action'], true);
        return $args;
    }

    private $_rendering2FAChallenge = false;

    private function __currentLogin()
    {
        $rcmail = rcmail::get_instance();
        return $rcmail->user->ID && isset($_SESSION['user_id'])
            && (string) $_SESSION['user_id'] === (string) $rcmail->user->ID
            && isset($_SESSION['twofactor_gauthenticator_login'])
            && is_int($_SESSION['twofactor_gauthenticator_login'])
            && $_SESSION['twofactor_gauthenticator_login'] > 0
            && $rcmail->session->check_auth();
    }

    private function __has2FAAuthorization()
    {
        return isset($_SESSION['twofactor_gauthenticator_2FA_login'])
            && is_int($_SESSION['twofactor_gauthenticator_2FA_login'])
            && in_array($_SESSION['twofactor_gauthenticator_2FA_method'] ?? null,
                array('totp', 'recovery', 'remembered_device', 'policy_whitelist'), true)
            && $this->__is2FAFresh();
    }

    private function __apply2FAException()
    {
        $rcmail = rcmail::get_instance();
        foreach ((array) $rcmail->config->get('whitelist', array()) as $ip) {
            if (CIDR::match($this->__getClientIP(), $ip)) {
                $this->__complete2FA('policy_whitelist');
                return true;
            }
        }
        if ($this->__cookie(false)) {
            $this->__complete2FA('remembered_device');
            return true;
        }
        return false;
    }

    private function __enforcePendingRequest($task, $action, $dispatch)
    {
        $rcmail = rcmail::get_instance();
        if (!$this->__is2FAInScope()) {
            // Excluded sessions need no plugin markers; core auth/CSRF still run.
            return;
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $current = $this->__currentLogin();
        if ($task === 'logout' && $action === '') {
            if (!$rcmail->check_request(rcube_utils::INPUT_GET | rcube_utils::INPUT_POST)) {
                $this->__deny2FAAccess();
            }
            if (!$current || !$this->__has2FAAuthorization()) {
                // Pending logout must not run core logout_actions (purge/expunge).
                $this->__exitSession();
            }
            return;
        }
        if (!$current) {
            $this->__deny2FAAccess();
        }
        $data = $this->__get2FAconfig(); // Includes strict decryption/error handling.
        if (empty($data['activate'])) {
            if ($rcmail->config->get('force_enrollment_users') && $this->__pluginAllowedByConfig()) {
                $view = $task === 'settings' && $action === 'plugin.twofactor_gauthenticator' && $method === 'GET';
                $save = $task === 'settings' && $action === 'plugin.twofactor_gauthenticator-save' && $method === 'POST';
                $test = $task === 'settings' && $action === 'plugin.twofactor_gauthenticator-checkcode' && $method === 'POST';
                if (!$view && !$save && !$test) {
                    if ($method === 'GET' && !$rcmail->output->ajax_call) {
                        $this->__goingRoundcubeTask('settings', 'plugin.twofactor_gauthenticator');
                    }
                    $this->__deny2FAAccess();
                }
            }
            return;
        }
        if ($this->__has2FAAuthorization() || $this->__apply2FAException()) {
            if ($task === 'login' && $action === '' && $method === 'GET') {
                $this->__goingRoundcubeTask('mail');
            }
            return;
        }
        $challenge = $task === 'mail' && $action === '' && $method === 'POST'
            && array_key_exists('_code_2FA', $_POST);
        $display = $task === 'login' && $action === '' && $method === 'GET'
            && !$rcmail->output->ajax_call;
        if ($challenge) {
            if (!$rcmail->check_request(rcube_utils::INPUT_POST)) {
                $this->__deny2FAAccess();
            }
            if ($dispatch) {
                $this->__verify2FAChallenge(); // Exits before task index/handler.
            }
            return; // Only this exact POST may reach ready; it never dispatches pending.
        }
        if ($display) {
            if ($dispatch) {
                $this->__show2FAChallenge(); // Server-owned template, no action dispatch.
            }
            return;
        }
        $this->__deny2FAAccess();
    }

    private function __show2FAChallenge()
    {
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));
        $rcmail->output->set_env('allow_save_device_30days', $rcmail->config->get('allow_save_device_30days', true));
        $rcmail->output->set_env('twofactor_formfield_as_password', $rcmail->config->get('twofactor_formfield_as_password', false));
        $this->add_texts('localization/', true);
        $this->include_script('twofactor_gauthenticator_form.js');
        $this->_rendering2FAChallenge = true;
        $rcmail->output->send('login');
        exit;
    }

    private function __verify2FAChallenge()
    {
        $rcmail = rcmail::get_instance();
        $code = rcube_utils::get_input_value('_code_2FA', rcube_utils::INPUT_POST);
        if ($this->__isLockedOut()) {
            $this->__waitSeconds($this->__blockedRemaining());
            $this->__exitSession();
        }
        $totp = is_string($code) && self::__checkCode($code);
        $recovery = !$totp && is_string($code) && self::__isRecoveryCode($code);
        if ($totp || $recovery) {
            if ($recovery) {
                self::__consumeRecoveryCode($code);
            }
            $this->__clearFailedAttempts(); // Real stored factor only (repair C).
            $this->__complete2FA($totp ? 'totp' : 'recovery');
            if (rcube_utils::get_input_value('_remember_2FA', rcube_utils::INPUT_POST) === 'yes') {
                $this->__cookie(true);
            }
            $this->__goingRoundcubeTask('mail');
        }
        if ($rcmail->config->get('enable_fail_logs')) {
            $this->__logError();
        }
        $this->__waitSeconds($this->__registerFailedAttempt());
        $this->__exitSession();
    }

    private function __deny2FAAccess()
    {
        $rcmail = rcmail::get_instance();
        http_response_code(403);
        header('Cache-Control: no-store');
        if ($rcmail->output->ajax_call) {
            header('Content-Type: application/json');
            echo json_encode(array('error' => '2FA authentication required', 'redirect' => '?_task=login'));
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo '2FA authentication required.';
        }
        exit;
    }

    /** Response-time defense only; never verifies a factor after executing an action. */
    public function check_2FAlogin($p)
    {
        $rcmail = rcmail::get_instance();
        if ($this->__is2FAInScope() && !empty($this->__get2FAconfig()['activate'])
            && (!$this->__currentLogin() || !$this->__has2FAAuthorization())
            && !$this->_rendering2FAChallenge) {
            $this->__deny2FAAccess();
        }
        return $p;
    }

    // ripped from new_user_dialog plugin
    public function popup_msg_enrollment()
    {
        $rcmail = rcmail::get_instance();
        if (!$this->__is2FAInScope()) {
            return;
        }
        $config_2FA = self::__get2FAconfig();

        if (!($config_2FA['activate'] ?? false)
            && $rcmail->config->get('force_enrollment_users') && $rcmail->task == 'settings' && $rcmail->action == 'plugin.twofactor_gauthenticator') {
            // add overlay input box to html page
            $rcmail->output->add_footer(html::tag(
                'form',
                array(
                    'id' => 'enrollment_dialog',
                    'method' => 'post'),
                html::tag('h3', null, $this->gettext('enrollment_dialog_title')) .
                    $this->gettext('enrollment_dialog_msg')
            ));

            $rcmail->output->add_script(
                "$('#enrollment_dialog').show().dialog({ modal:true, resizable:false, closeOnEscape: true, width:420 });",
                'docready'
            );
        }
    }

    // show config
    public function twofactor_gauthenticator_init()
    {
        $rcmail = rcmail::get_instance();

        $this->__authorizeConfiguration('view');

        $this->add_texts('localization/', true);
        $this->register_handler('plugin.body', array($this, 'twofactor_gauthenticator_form'));

        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));
        $rcmail->output->send('plugin');
    }

    // save config
    public function twofactor_gauthenticator_save()
    {
        $rcmail = rcmail::get_instance();

        $previous = $this->__authorizeConfiguration('save');

        // Validate the entire candidate before encryption, writes or UI callbacks.
        $activate = rcube_utils::get_input_value('2FA_activate', rcube_utils::INPUT_POST);
        $secret = rcube_utils::get_input_value('2FA_secret', rcube_utils::INPUT_POST);
        $recovery_codes = rcube_utils::get_input_value('2FA_recovery_codes', rcube_utils::INPUT_POST);
        try {
            if (!in_array($activate, array(null, '', '0', '1', 'on'), true)
                || !is_string($secret) || !is_array($recovery_codes)
                || count($recovery_codes) > $this->_number_recovery_codes) {
                throw new \UnexpectedValueException('Invalid configuration request');
            }
            foreach ($recovery_codes as $code) {
                if (!is_string($code)) {
                    throw new \UnexpectedValueException('Invalid recovery code');
                }
            }
            $recovery_codes = array_values(array_filter($recovery_codes, function ($code) {
                return $code !== '';
            }));
            $enabled = $activate === '1' || $activate === 'on';
            // Even discarded form fields must have their documented types/format.
            if (($secret !== '' && !$this->__validConfigurationSecret($secret))
                || !$this->__validConfigurationRecoveryCodes($recovery_codes)) {
                throw new \UnexpectedValueException('Invalid configuration fields');
            }
            $data = $this->__validateConfigurationData($enabled
                ? array('activate' => true, 'secret' => $secret, 'recovery_codes' => $recovery_codes)
                : array());
        } catch (\Throwable $error) {
            $this->__denyConfiguration(400);
        }

        // A new or replaced secret needs proof of possession of this exact candidate.
        // The AJAX autotest is advisory and supplies no authorization to this POST.
        $candidate_required = !empty($data['activate'])
            && (empty($previous['activate']) || $previous['secret'] !== $data['secret']);
        if ($candidate_required) {
            if ($this->__isLockedOut()) {
                $this->__denyConfiguration(429);
            }
            $candidate_code = rcube_utils::get_input_value('2FA_enrollment_code', rcube_utils::INPUT_POST);
            if (!is_string($candidate_code) || !preg_match('/^[0-9]{6}$/D', $candidate_code)
                || !self::__checkCode($candidate_code, $data['secret'])) {
                $wait = $this->__registerFailedAttempt();
                $this->__waitSeconds($wait);
                $this->__denyConfiguration(400);
            }
        }

        $this->__persistConfiguration($data);
        if ($candidate_required) {
            // Storage alone is never proof: the candidate TOTP was verified above.
            $this->__complete2FA('totp');
        } elseif (empty($data['activate'])) {
            $this->__remove2FASession();
        }

        // A fresh GET uses the ordinary view/renderer authorization boundary.
        header('Cache-Control: no-store');
        header('Location: ?_task=settings&_action=plugin.twofactor_gauthenticator', true, 303);
        exit;
    }

    // form config
    public function twofactor_gauthenticator_form()
    {
        $rcmail = rcmail::get_instance();

        $data = $this->__authorizeConfiguration('render');

        $this->add_texts('localization/', true);
        $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));

        // Fields will be positioned inside of a table
        $table = new html_table(array('cols' => 2));

        // Activate/deactivate
        $field_id = '2FA_activate';
        $checkbox_activate = new html_checkbox(array('name' => $field_id, 'id' => $field_id, 'type' => 'checkbox'));
        $table->add('title', html::label($field_id, rcube::Q($this->gettext('activate'))));
        $checked = (isset($data['activate']) && $data['activate']) ? null : 1; // :-?
        $table->add(null, $checkbox_activate->show($checked));


        // secret
        $field_id = '2FA_secret';
        $input_descsecret = new html_inputfield(array('name' => $field_id, 'id' => $field_id, 'size' => 60, 'type' => 'password', 'value' => $data['secret'] ?? '', 'autocomplete' => 'new-password'));
        $table->add('title', html::label($field_id, rcube::Q($this->gettext('secret'))));
        $html_secret = $input_descsecret->show();
        if ($data['secret'] ?? '') {
            $html_secret .= ' &nbsp; <input type="button" class="button mainaction" id="2FA_change_secret" value="'.$this->gettext('show_secret').'">';
        } else {
            $html_secret .= ' &nbsp; <input type="button" class="button mainaction" id="2FA_create_secret" disabled="disabled" value="'.$this->gettext('create_secret').'">';
        }
        $table->add(null, $html_secret);


        // recovery codes
        $table->add('title', $this->gettext('recovery_codes'));

        $html_recovery_codes = '';
        $i = 0;
        for ($i = 0; $i < $this->_number_recovery_codes; $i++) {
            $value = isset($data['recovery_codes'][$i]) ? $data['recovery_codes'][$i] : '';
            $html_recovery_codes .= ' <input type="password" name="2FA_recovery_codes[]" value="'.$value.'" maxlength="10"> &nbsp; ';
        }
        if ($data['secret'] ?? '') {
            $html_recovery_codes .= '<input type="button" class="button mainaction" id="2FA_show_recovery_codes" value="'.$this->gettext('show_recovery_codes').'">';
        } else {
            $html_recovery_codes .= '<input type="button" class="button mainaction" id="2FA_show_recovery_codes" disabled="disabled" value="'.$this->gettext('show_recovery_codes').'">';
        }
        $table->add(null, $html_recovery_codes);


        // qr-code
        if ($data['secret'] ?? '') {
            $table->add('title', $this->gettext('qr_code'));
            $table->add(null, '<input type="button" class="button mainaction" id="2FA_change_qr_code" value="'.$this->gettext('show_qr_code').'"> 
        						<div id="2FA_qr_code" style="display: none; margin-top: 10px;"></div>');

            // new JS qr-code, without call to Google
            $this->include_script('2FA_qr_code.js');
        }

        // infor
        $table->add(null, '<td><br>'.$this->gettext('msg_infor').'</td>');

        // button to setup all fields if doesn't exists secret
        $html_setup_all_fields = '';
        if (empty($data['secret'])) {
            $html_setup_all_fields = '<input type="button" class="button mainaction" id="2FA_setup_fields" value="'.$this->gettext('setup_all_fields').'">';
        }

        $html_check_code = '<br /><br /><input type="button" class="button mainaction" id="2FA_check_code" value="'.$this->gettext('check_code').'"> &nbsp;&nbsp; <input type="text" id="2FA_code_to_check" name="2FA_enrollment_code" maxlength="6" inputmode="numeric" autocomplete="one-time-code">';

        $html_help_code = '<br /><br /> &#9432; '.$this->gettext('candidate_code_required')
            . '<br />'.$this->gettext('msg_help');

        // Build the table with the divs around it
        $out = html::div(
            array('class' => 'settingsbox'),
            html::tag('h3', array('id' => 'prefs-title', 'class' => ''), $this->gettext('twofactor_gauthenticator') . ' - ' . $rcmail->user->data['username']) .
            html::div(
                array('class' => 'boxcontent'),
                $table->show() .
                html::p(
                    null,
                    $rcmail->output->button(array(
                        'command' => 'plugin.twofactor_gauthenticator-save',
                        'type' => 'input',
                        'class' => 'button mainaction',
                        'label' => 'save'
                    ))

                    // button show/hide secret
                    //.'<input type="button" class="button mainaction" id="2FA_change_secret" value="'.$this->gettext('show_secret').'">'

                    // button to setup all fields
                    .$html_setup_all_fields
                    .$html_check_code
                    .$html_help_code
                )
            )
        );

        // Construct the form
        $rcmail->output->add_gui_object('twofactor_gauthenticatorform', 'twofactor_gauthenticator-form');

        $out = $rcmail->output->form_tag(array(
            'id' => 'twofactor_gauthenticator-form',
            'name' => 'twofactor_gauthenticator-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.twofactor_gauthenticator-save',
        ), $out);

        $out = "<div class='formcontainer'><div class='formcontent'>".$out."</div></div>";

        return $out;
    }

    // used with ajax
    public function checkCode()
    {
        $this->__authorizeConfiguration('test');
        $code   = rcube_utils::get_input_value('code', rcube_utils::INPUT_POST);
        $secret = rcube_utils::get_input_value('secret', rcube_utils::INPUT_POST);

        // EMENDA 4: an explicit secret is required. Never verify against the real stored
        // secret (that would turn this endpoint into a verification oracle for tests).
        if (!$secret) {
            echo $this->gettext('code_ko');
            exit;
        }

        // EMENDA 4: while blocked, never return a verdict — wait the remaining block, then ko.
        if ($this->__isLockedOut()) {
            $this->__waitSeconds($this->__blockedRemaining());
            echo $this->gettext('code_ko');
            exit;
        }

        if (self::__checkCode($code, $secret)) {
            // A caller-supplied candidate does not authenticate the stored factor.
            echo $this->gettext('code_ok');
        } else {
            $wait = $this->__registerFailedAttempt();
            $this->__waitSeconds($wait);
            echo $this->gettext('code_ko');
        }
        exit;
    }

    //------------- private methods

    /**
     * Returns true if $ip matches any entry in the trusted-proxy list.
     * Malformed entries never match (CIDR::match returns false for them) => fail closed.
     */
    private function __isTrustedProxy($ip, array $trusted): bool
    {
        foreach ($trusted as $entry) {
            if (CIDR::match($ip, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve the client IP for the whitelist decision and the rate-limit key.
     * Default: REMOTE_ADDR (the real TCP peer) only. X-Forwarded-For is parsed ONLY
     * when the direct peer REMOTE_ADDR is itself a configured trusted proxy, and then
     * only the first untrusted hop (scanning from the proxy-nearest side) is used.
     */
    private function __getClientIP(): string
    {
        $rcmail  = rcmail::get_instance();
        $remote  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $trusted = $rcmail->config->get('twofactor_trusted_proxies', array());

        if (is_array($trusted) && count($trusted) > 0 && $this->__isTrustedProxy($remote, $trusted)) {
            $xff = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR');
            if (is_string($xff) && $xff !== '') {
                $hops = array_map('trim', explode(',', $xff));
                // scan RIGHT->LEFT: the rightmost hop is the trusted proxy's immediate peer.
                // The first hop that is NOT itself a trusted proxy is the originating client.
                // A client-supplied leading (left) value is therefore never used unless every
                // hop to its right is a proxy we explicitly trust.
                for ($i = count($hops) - 1; $i >= 0; $i--) {
                    if ($hops[$i] === '') {
                        continue;
                    }
                    if (!$this->__isTrustedProxy($hops[$i], $trusted)) {
                        // Never trust an unvalidated/malformed forwarded value; CIDR::match
                        // is the final arbiter, and it returns false for a malformed IP (default deny).
                        return $hops[$i];
                    }
                }
            }
        }
        return $remote;
    }

    /**
     * Shared freshness invariant: 2FA is complete only when the 2FA login marker is set
     * and >= the password-auth login marker. Isset-guarded for PHP 8 (no undefined-key warnings).
     */
    private function __is2FAFresh(): bool
    {
        if (!isset($_SESSION['twofactor_gauthenticator_login'])) {
            return false;
        }
        if (!isset($_SESSION['twofactor_gauthenticator_2FA_login'])) {
            return false;
        }
        return $_SESSION['twofactor_gauthenticator_2FA_login']
            >= $_SESSION['twofactor_gauthenticator_login'];
    }

    /**
     * Composite rate-limit key: the canonical resolver IP (no attacker-controllable header)
     * plus the authenticated username.
     */
    private function __clientFailureKey(): string
    {
        $rcmail   = rcmail::get_instance();
        $ip       = $this->__getClientIP();
        $username = $rcmail->user->data['username'] ?? '';
        return md5($ip . '|' . $username);
    }

    /**
     * Incremental wait (seconds) applied after the N-th accumulated failure (level N).
     * Reads `twofactor_lockout_delays` (default [1,2,5,20,60,300,600]); non-array or empty
     * -> default. Each value is clamped to >= 1; levels beyond the array length use the LAST
     * value.
     */
    private function __failureDelay(int $level): int
    {
        $rcmail = rcmail::get_instance();
        $delays = $rcmail->config->get('twofactor_lockout_delays', array(1, 2, 5, 20, 60, 300, 600));
        if (!is_array($delays) || empty($delays)) {
            $delays = array(1, 2, 5, 20, 60, 300, 600);
        }
        $idx = min($level, count($delays)) - 1;
        return max(1, (int) $delays[$idx]);
    }

    /**
     * Path to the per-key JSON fail-state file at {log_dir}/twofactor_gauth_fail_{md5key}.json.
     * Returns null (silent degrade: logout-on-failure still enforced by callers) when log_dir
     * is unset, missing, or not writable.
     */
    private function __failStatePath(string $key): ?string
    {
        $rcmail  = rcmail::get_instance();
        $log_dir = (string) $rcmail->config->get('log_dir');
        if ($log_dir === '' || !is_dir($log_dir) || !is_writable($log_dir)) {
            return null;   // cannot persist: logout-on-failure still enforced by callers
        }
        return rtrim($log_dir, '/') . '/twofactor_gauth_fail_' . $key . '.json';
    }

    /**
     * Read the per-key JSON fail state. Returns null if the file is missing, unreadable,
     * or does not decode to an array.
     */
    private function __readFailState(string $key): ?array
    {
        $path = $this->__failStatePath($key);
        if (!$path || !is_file($path)) {
            return null;
        }
        $data  = @file_get_contents($path);
        $state = $data !== false ? json_decode($data, true) : null;
        return is_array($state) ? $state : null;
    }

    /**
     * Write the per-key JSON fail state (locked exclusive write). Silent if log_dir unusable.
     */
    private function __writeFailState(string $key, array $state): void
    {
        $path = $this->__failStatePath($key);
        if ($path) {
            @file_put_contents($path, json_encode($state), LOCK_EX);
        }
    }

    /**
     * Remove the per-key JSON fail state file, if present.
     */
    private function __removeFailState(string $key): void
    {
        $path = $this->__failStatePath($key);
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Record a failed 2FA attempt in the persistent per-key JSON state file keyed by
     * (IP, username). State: ['level' => int, 'last' => int, 'blocked_until' => int].
     * Resets the level to 0 if the last failure is older than `twofactor_lockout` (default
     * 900), increments the level, and sets the blocked window to now + __failureDelay(level).
     * NO eviction here: the caller owns teardown. Degrades silently if log_dir is unusable.
     */
    private function __registerFailedAttempt(): int   // returns the applied wait (seconds)
    {
        $rcmail  = rcmail::get_instance();
        $key     = $this->__clientFailureKey();
        $lockout = (int) $rcmail->config->get('twofactor_lockout', 900);
        $now     = time();

        $state = $this->__readFailState($key);
        $level = (int) ($state['level'] ?? 0);
        if (($state['last'] ?? 0) > 0 && ($now - $state['last']) > $lockout) {
            $level = 0;                                // stale counter -> reset before counting
        }

        $level++;
        $blocked_until = $now + $this->__failureDelay($level);
        $this->__writeFailState($key, array(
            'level'         => $level,
            'last'          => $now,
            'blocked_until' => $blocked_until,
        ));
        return max(0, $blocked_until - $now);
    }

    /**
     * True only while the (IP, username) is blocked: a non-stale state array exists and
     * `blocked_until` is in the future. Returns false if there is no state file, no state
     * array, or the state is stale (last failure older than `twofactor_lockout`).
     */
    private function __isLockedOut(): bool
    {
        return $this->__blockedRemaining() > 0;
    }

    /**
     * Seconds still blocked for the current (IP, username); 0 when no state, stale, or expired.
     */
    private function __blockedRemaining(): int
    {
        $rcmail = rcmail::get_instance();
        $state  = $this->__readFailState($this->__clientFailureKey());
        if (!is_array($state)) {
            return 0;
        }
        if (($state['last'] ?? 0) > 0 && (time() - $state['last']) > (int) $rcmail->config->get('twofactor_lockout', 900)) {
            return 0;                                  // stale -> not blocked
        }
        return max(0, (int) ($state['blocked_until'] ?? 0) - time());
    }

    /**
     * Physical (felt) wait: block the HTTP response for $seconds. Capped at 600s (the scale
     * maximum) so a single request can never hang longer than the longest configured delay.
     */
    private function __waitSeconds(int $seconds): void
    {
        $seconds = min(max(0, $seconds), 600);
        if ($seconds > 0) {
            usleep($seconds * 1000000);
        }
    }

    /**
     * Reset the failed-attempt counter for the current (IP, username) composite on success
     * by removing the per-key JSON fail-state file.
     */
    private function __clearFailedAttempts(): void
    {
        $this->__removeFailState($this->__clientFailureKey());
    }

    /**
/**
     * Clear the plugin 30-day "remember me" cookie (name derived identically to __cookie()).
     */
    private function __clearRememberMeCookie(): void
    {
        $rcmail   = rcmail::get_instance();
        $username = $rcmail->user->data['username'] ?? null;
        if (!$username) {
            return;
        }
        $name = hash_hmac('md5', $username, $rcmail->config->get('des_key'));
        rcube_utils::setcookie($name, '-del-', time() - 60);
    }

    /** Register removals so Roundcube cannot merge old markers back into storage. */
    private function __remove2FASession($includeLogin = false, $includePreferences = false)
    {
        $keys = array('twofactor_gauthenticator_2FA_login', 'twofactor_gauthenticator_2FA_method');
        if ($includeLogin) {
            $keys[] = 'twofactor_gauthenticator_login';
        }
        if ($includePreferences) {
            $keys[] = 'preferences';
            $keys[] = 'preferences_time';
        }
        $session = rcmail::get_instance()->session;
        foreach ($keys as $key) {
            $session->remove($key);
        }
    }

    /** Record an explicit authorization decision, never a navigation side effect. */
    private function __complete2FA($method)
    {
        if (!in_array($method, array('totp', 'recovery', 'remembered_device',
            'policy_whitelist'), true)) {
            throw new \InvalidArgumentException('Unknown 2FA authorization method');
        }
        $_SESSION['twofactor_gauthenticator_2FA_login'] = time();
        $_SESSION['twofactor_gauthenticator_2FA_method'] = $method;
    }

    // Navigation alone must never attest a second factor.
    private function __goingRoundcubeTask($task = 'mail', $action = null)
    {

        header('Location: ?_task='.$task . ($action ? '&_action='.$action : ''));
        exit;
    }

    private function __exitSession()
    {
        $rcmail = rcmail::get_instance();

        // 1. Clear the plugin remember-me cookie (needs username/secret-derived name) first.
        $this->__clearRememberMeCookie();

        // Register marker removal even if a session-destroy hook later fails.
        // Failure counters are files and intentionally survive this cleanup.
        $this->__remove2FASession(true);
        $rcmail->kill_session();

        // The redirect follows server-side teardown; it is not the invalidation.
        header('Location: ?_task=login');
        exit;
    }

    /**
     * Configuration-only boundary. Do not rely on send_page, _remote or a task
     * supplied by the client to authorize access to stored factors.
     */
    private function __authorizeConfiguration($operation)
    {
        $rcmail = rcmail::get_instance();
        if (!$this->__is2FAInScope()) {
            $this->__denyConfiguration(403);
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $view = $rcmail->task === 'settings'
            && $rcmail->action === 'plugin.twofactor_gauthenticator';
        $save = $rcmail->task === 'settings'
            && $rcmail->action === 'plugin.twofactor_gauthenticator-save';

        $test = $rcmail->task === 'settings'
            && $rcmail->action === 'plugin.twofactor_gauthenticator-checkcode';
        if (($operation === 'save' && !$save)
            || ($operation === 'test' && !$test)
            || (!in_array($operation, array('save', 'test'), true) && !$view)) {
            $this->__denyConfiguration(403);
        }

        $post = in_array($operation, array('save', 'test'), true);
        if (($post && $method !== 'POST') || (!$post && $method !== 'GET')) {
            $this->__denyConfiguration(405);
        }

        if (!$rcmail->user->ID
            || !isset($_SESSION['user_id'])
            || (string) $_SESSION['user_id'] !== (string) $rcmail->user->ID
            || !isset($_SESSION['twofactor_gauthenticator_login'])
            || !is_int($_SESSION['twofactor_gauthenticator_login'])
            || $_SESSION['twofactor_gauthenticator_login'] <= 0
            || !$rcmail->session->check_auth()) {
            $this->__denyConfiguration(403);
        }

        if ($post && !$rcmail->check_request(rcube_utils::INPUT_POST)) {
            $this->__denyConfiguration(403);
        }

        try {
            $data = $this->__readConfigurationPreferences();
        } catch (\Throwable $error) {
            // Never log the ciphertext, plaintext, submitted fields or exception.
            rcube::write_log('twofactor_gauthenticator', 'ERROR: Unable to read 2FA configuration');
            $this->__denyConfiguration(503);
        }

        if (!empty($data['activate']) && !$this->__has2FAAuthorization()) {
            $this->__denyConfiguration(403);
        }

        return $data;
    }

    /** Shared strict reader: unreadable factors must not become an empty account. */
    private function __readConfigurationPreferences()
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;

        // get_prefs() casts corrupt unserialized values to an empty array. Do not
        // interpret that as an account without a factor at this boundary.
        $raw = $_SESSION['preferences'] ?? ($user->data['preferences'] ?? null);
        if ($raw !== null && $raw !== '') {
            if (!is_string($raw) || !is_array(@unserialize($raw, array('allowed_classes' => false)))) {
                throw new \UnexpectedValueException('Invalid preferences');
            }
        }

        $prefs = $user->get_prefs();
        if (!is_array($prefs) || $rcmail->db->is_error()) {
            throw new \UnexpectedValueException('Unavailable preferences');
        }
        if (!array_key_exists('twofactor_gauthenticator', $prefs)) {
            return array();
        }

        $data = $prefs['twofactor_gauthenticator'];
        if (!is_array($data)) {
            if (!is_string($data) || $data === '' || !$rcmail->config->get('twofactor_pref_encrypt')) {
                throw new \UnexpectedValueException('Invalid factor configuration');
            }
            $plain = $rcmail->decrypt($data);
            if (!is_string($plain) || substr(ltrim($plain), 0, 1) !== '{') {
                throw new \UnexpectedValueException('Unreadable factor configuration');
            }
            $data = json_decode($plain, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || $data === array()) {
                throw new \UnexpectedValueException('Invalid factor configuration');
            }
        }

        return $this->__validateConfigurationData($data);
    }

    private function __validateConfigurationData($data)
    {
        if (!is_array($data)) {
            throw new \UnexpectedValueException('Invalid factor configuration');
        }
        if ($data === array()) {
            return $data;
        }
        if (array_diff(array_keys($data), array('activate', 'secret', 'recovery_codes'))
            || !array_key_exists('activate', $data) || !is_bool($data['activate'])) {
            throw new \UnexpectedValueException('Invalid factor configuration');
        }
        if (!$data['activate']) {
            // A disabled flag with residual factors is inconsistent, not enrollment.
            if ((array_key_exists('secret', $data) && $data['secret'] !== '')
                || (array_key_exists('recovery_codes', $data) && $data['recovery_codes'] !== array())) {
                throw new \UnexpectedValueException('Residual disabled factors');
            }
            return array();
        }
        if (!isset($data['secret']) || !$this->__validConfigurationSecret($data['secret'])) {
            throw new \UnexpectedValueException('Invalid factor secret');
        }
        $codes = array_key_exists('recovery_codes', $data) ? $data['recovery_codes'] : array();
        if (!$this->__validConfigurationRecoveryCodes($codes)) {
            throw new \UnexpectedValueException('Invalid recovery codes');
        }
        return array('activate' => true, 'secret' => $data['secret'], 'recovery_codes' => array_values($codes));
    }

    private function __validConfigurationSecret($secret)
    {
        if (!is_string($secret) || !preg_match('/\A[A-Z2-7]+={0,6}\z/D', $secret)) {
            return false;
        }
        $plain = rtrim($secret, '=');
        $length = strlen($plain);
        if ($length < 16 || $length > 128) {
            return false;
        }
        // Canonical RFC 4648 Base32, padded or unpadded (80..640 encoded bits).
        $padding = array(0 => 0, 2 => 6, 4 => 4, 5 => 3, 7 => 1);
        $remainder = $length % 8;
        if (!array_key_exists($remainder, $padding)
            || (strlen($secret) !== $length && strlen($secret) - $length !== $padding[$remainder])) {
            return false;
        }
        $unused = array(0 => 0, 2 => 2, 4 => 4, 5 => 1, 7 => 3);
        $last = strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', substr($plain, -1));
        return ($last & ((1 << $unused[$remainder]) - 1)) === 0;
    }

    private function __validConfigurationRecoveryCodes($codes)
    {
        if (!is_array($codes) || count($codes) > $this->_number_recovery_codes) {
            return false;
        }
        foreach ($codes as $code) {
            // Matches the current form's maximum; excludes markup/control bytes.
            if (!is_string($code) || !preg_match('/\A[A-Za-z0-9]{6,10}\z/D', $code)) {
                return false;
            }
        }
        return count(array_unique($codes, SORT_STRING)) === count($codes);
    }

    /** Shared write path: never fall back to plaintext or deferred success. */
    private function __persistConfiguration($data)
    {
        $rcmail = rcmail::get_instance();
        try {
            $prefs = $rcmail->user->get_prefs();
            if (!is_array($prefs) || $rcmail->db->is_error()) {
                throw new \UnexpectedValueException('Unavailable preferences');
            }
            $stored = $this->__validateConfigurationData($data);
            if ($stored && $rcmail->config->get('twofactor_pref_encrypt')) {
                $plain = json_encode($stored);
                if (!is_string($plain)) {
                    throw new \UnexpectedValueException('Unencodable configuration');
                }
                $stored = $rcmail->encrypt($plain);
                if (!is_string($stored) || $stored === '' || $rcmail->decrypt($stored) !== $plain) {
                    throw new \UnexpectedValueException('Unusable encryption');
                }
            }
            $prefs['twofactor_gauthenticator'] = $stored;
        } catch (\Throwable $error) {
            rcube::write_log('twofactor_gauthenticator', 'ERROR: Unable to prepare 2FA configuration');
            $this->__denyConfiguration(503);
        }

        try {
            $saved = $rcmail->user->save_prefs($prefs);
            if ($saved === true && !$rcmail->db->is_error()) {
                rcube::write_log('twofactor_gauthenticator', 'WARN: 2FA configuration saved');
                return;
            }
        } catch (\Throwable $error) {
            // The API may have cached/queued data before reporting an error.
        }
        rcube::write_log('twofactor_gauthenticator', 'ERROR: 2FA configuration write failed');
        try {
            $rcmail->kill_session();
        } finally {
            // Also prevent a failed destroy from retaining this request's queue.
            $this->__remove2FASession(true, true);
            $this->__denyConfiguration(503);
        }
    }

    private function __denyConfiguration($status)
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        echo 'Two-factor configuration is unavailable.';
        exit;
    }

    private function __get2FAconfig()
    {
        try {
            return $this->__readConfigurationPreferences();
        } catch (\Throwable $error) {
            rcube::write_log('twofactor_gauthenticator', 'ERROR: Unable to read 2FA configuration');
            $this->__denyConfiguration(503);
        }
    }

    // Disabled configuration is represented by an empty array, never a scalar.
    private function __set2FAconfig($data)
    {
        $this->__persistConfiguration($data);
        return true;
    }

    private function __isRecoveryCode($code)
    {
        $prefs = self::__get2FAconfig();
        return in_array($code, $prefs['recovery_codes'], true);
    }

    private function __consumeRecoveryCode($code)
    {
        $prefs = self::__get2FAconfig();
        $prefs['recovery_codes'] = array_values(array_diff($prefs['recovery_codes'], array($code)));

        self::__set2FAconfig($prefs);
    }


    // GoogleAuthenticator class methods (see PHPGangsta/GoogleAuthenticator.php for more infor)
    // returns string
    private function __createSecret()
    {
        $ga = new PHPGangsta_GoogleAuthenticator();
        return $ga->createSecret();
    }

    // returns string
    private function __getSecret()
    {
        $prefs = self::__get2FAconfig();
        return $prefs['secret'];
    }

    // Commented. If you have problems with qr-code.js, you can uncomment and use this
    //
    // 	// returns string (url to img)
    // 	private function __getQRCodeGoogle()
    // 	{
    // 		$rcmail = rcmail::get_instance();

    // 		$ga = new PHPGangsta_GoogleAuthenticator();
    // 		return $ga->getQRCodeGoogleUrl($rcmail->user->data['username'], self::__getSecret(), 'RoundCube2FA');
    // 	}

    // returns boolean
    private function __checkCode($code, $secret = null)
    {
        $ga = new PHPGangsta_GoogleAuthenticator();
        return $ga->verifyCode(($secret ? $secret : self::__getSecret()), $code, 2);    // 2 = 2*30sec clock tolerance
    }


    // remember option by https://github.com/corrideat/
    private function __cookie($set = true)
    {
        $rcmail = rcmail::get_instance();
        $user_agent = hash_hmac('md5', filter_input(INPUT_SERVER, 'USER_AGENT') ?: "\0\0\0\0\0", $rcmail->config->get('des_key'));
        $key = hash_hmac('sha256', implode("\2\1\2", array($rcmail->user->data['username'], $this->__getSecret())), $rcmail->config->get('des_key'), true);
        $iv = hash_hmac('md5', implode("\3\2\3", array($rcmail->user->data['username'], $this->__getSecret())), $rcmail->config->get('des_key'), true);
        $name = hash_hmac('md5', $rcmail->user->data['username'], $rcmail->config->get('des_key'));

        if ($set) {
            $expires = time() + 2592000; // 30 days from now
            $rand = mt_rand();
            $signature = hash_hmac('sha512', implode("\1\0\1", array($rcmail->user->data['username'], $this->__getSecret(), $user_agent, $rand, $expires)), $rcmail->config->get('des_key'), true);
            $plain_content = sprintf("%d:%d:%s", $expires, $rand, $signature);
            $encrypted_content = openssl_encrypt($plain_content, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($encrypted_content !== false) {
                $b64_encrypted_content = strtr(base64_encode($encrypted_content), '+/=', '-_,');
                rcube_utils::setcookie($name, $b64_encrypted_content, $expires);
                return true;
            }
            return false;
        } else {
            $b64_encrypted_content = filter_input(INPUT_COOKIE, $name, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/[a-zA-Z0-9_-]+,{0,3}/')));
            if (is_string($b64_encrypted_content) && !empty($b64_encrypted_content) && strlen($b64_encrypted_content) % 4 === 0) {
                $encrypted_content = base64_decode(strtr($b64_encrypted_content, '-_,', '+/='), true);
                if ($encrypted_content !== false) {
                    $plain_content = openssl_decrypt($encrypted_content, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
                    if ($plain_content !== false) {
                        $now = time();
                        list($expires, $rand, $signature) = explode(':', $plain_content, 3);
                        if ($expires > $now && ($expires - $now) <= 2592000) {
                            $signature_verification = hash_hmac('sha512', implode("\1\0\1", array($rcmail->user->data['username'], $this->__getSecret(), $user_agent, $rand, $expires)), $rcmail->config->get('des_key'), true);
                            // constant time
                            $cmp = strlen($signature) ^ strlen($signature_verification);
                            $signature = $signature ^ $signature_verification;
                            for ($i = 0; $i < strlen($signature); $i++) {
                                $cmp += ord($signature [$i]);
                            }
                            return ($cmp === 0);
                        }
                    }
                }
            }
            return false;
        }
    }
    // END remember


    // log error into $_logs_file directory
    private function __logError()
    {
        rcube::write_log('twofactor_gauthenticator', "ERROR: 2FA fail - rip:". $_SERVER['HTTP_X_FORWARDED_FOR']." lip:".$_SERVER['REMOTE_ADDR']);
    }
}
