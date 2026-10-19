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

        // Block data access via AJAX for partially authenticated users who have 2FA enabled (by Stephen K. Gielda <security@codamail.com>)
	if (isset($_SESSION['twofactor_gauthenticator_login']) && 
	    !$this->__is2FAFresh() &&
	    isset($_REQUEST['_remote']) &&
	    $rcmail->action !== 'plugin.twofactor_gauthenticator-checkcode' &&
	    $rcmail->task !== 'login') {
	    
	    // Get user's 2FA config
	    $user_prefs = $rcmail->user->get_prefs();
	    $tfa_config = isset($user_prefs['twofactor_gauthenticator']) ? $user_prefs['twofactor_gauthenticator'] : null;
	    
	    // Only block if 2FA is enabled for this user
	    if ($tfa_config && isset($tfa_config['activate']) && $tfa_config['activate']) {
		// Direct JSON response to prevent leakage
		header('Content-Type: application/json');
		echo json_encode(array(
		    'error' => '2FA authentication required',
		    'redirect' => '?_task=login&_err=session'
		));
		exit;
	    }
	}

        // hooks
        $this->add_hook('login_after', array($this, 'login_after'));
        $this->add_hook('send_page', array($this, 'check_2FAlogin'));
        $this->add_hook('render_page', array($this, 'popup_msg_enrollment'));

        $this->load_config();

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

    // Use the form login, but removing inputs with jquery and action (see twofactor_gauthenticator_form.js)
    public function login_after($args)
    {
        $_SESSION['twofactor_gauthenticator_login'] = time();

        $rcmail = rcmail::get_instance();


        $config_2FA = self::__get2FAconfig();
        if (!($config_2FA['activate'] ?? false)) {
            // Allow mandatory password changes before forced 2FA enrollment.
            // Fix by Nikola Vasic <nikola.vasic01@gmail.com>
            $must_change_password = $rcmail->config->get('password_force_new_user')
                && $rcmail->config->get('newuserpassword');

            if ($must_change_password) {
                return $args;
            }

            if ($rcmail->config->get('force_enrollment_users')) {
                $this->__goingRoundcubeTask('settings', 'plugin.twofactor_gauthenticator');
            }

            return $args;
        }

        if ($this->__cookie($set = false) || !$this->__pluginAllowedByConfig()) {
            $_SESSION['twofactor_gauthenticator_login'] -= 1; // so that we may use ge to check for valid session
            $this->__goingRoundcubeTask('mail');
            return;
        }

        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));

        $rcmail->output->set_env('allow_save_device_30days', $rcmail->config->get('allow_save_device_30days', true));
        $rcmail->output->set_env('twofactor_formfield_as_password', $rcmail->config->get('twofactor_formfield_as_password', false));

        $this->add_texts('localization', true);
        $this->include_script('twofactor_gauthenticator_form.js');

        $rcmail->output->send('login');
    }

    // capture webpage if someone try to use ?_task=mail|addressbook|settings|... and check auth code
    public function check_2FAlogin($p)
    {
        $rcmail = rcmail::get_instance();
        $config_2FA = self::__get2FAconfig();

        if ($config_2FA['activate'] ?? false) {
            // with IP allowed, we don't need to check anything
            if ($rcmail->config->get('whitelist')) {
                $realip = $this->__getClientIP();
                foreach ($rcmail->config->get('whitelist') as $ip_to_check) {
                    if (CIDR::match($realip, $ip_to_check)) {
                        if (isset($_SESSION['twofactor_gauthenticator_login'])) {
                            if ($rcmail->task === 'login') {
                                $this->__goingRoundcubeTask('mail');
                            }
                            return $p;
                        }
                    }
                }
            }


            $code = rcube_utils::get_input_value('_code_2FA', rcube_utils::INPUT_POST);
            $remember = rcube_utils::get_input_value('_remember_2FA', rcube_utils::INPUT_POST);

            if ($code) {
                if ($this->__isLockedOut()) {
                    $this->__waitSeconds($this->__blockedRemaining());   // feel the remaining block
                    $this->__exitSession();                              // then full teardown
                }
                if (self::__checkCode($code) || self::__isRecoveryCode($code)) {
                    $this->__clearFailedAttempts();
                    if (self::__isRecoveryCode($code)) {
                        self::__consumeRecoveryCode($code);
                    }

                    if (rcube_utils::get_input_value('_remember_2FA', rcube_utils::INPUT_POST) === 'yes') {
                        $this->__cookie($set = true);
                    }

                    $this->__goingRoundcubeTask('mail');
                } else {
                    if ($rcmail->config->get('enable_fail_logs')) {
                        $this->__logError();
                    }
                    $wait = $this->__registerFailedAttempt();   // record BEFORE teardown (outlives it)
                    $this->__waitSeconds($wait);                // FELT delay before leaving
                    $this->__exitSession();                     // EVERY failed login code -> full teardown
                }
            }
            // we're into some task but marked with login...
            elseif ($rcmail->task !== 'login' && !$this->__is2FAFresh()) {
                $this->__exitSession();
            }

        } elseif ($rcmail->config->get('force_enrollment_users') && ($rcmail->task !== 'settings' || $rcmail->action !== 'plugin.twofactor_gauthenticator')) {
            // Allow mandatory password changes before forced 2FA enrollment.
            // Fix by Nikola Vasic <nikola.vasic01@gmail.com>
            $must_change_password = $rcmail->config->get('password_force_new_user')
                && $rcmail->config->get('newuserpassword');

            if ($must_change_password) {
                return $p;
            }

            if ($rcmail->task !== 'login') {
                $this->__goingRoundcubeTask('settings', 'plugin.twofactor_gauthenticator');
            }
        }

        return $p;
    }

    // ripped from new_user_dialog plugin
    public function popup_msg_enrollment()
    {
        $rcmail = rcmail::get_instance();
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

        $this->add_texts('localization/', true);
        $this->register_handler('plugin.body', array($this, 'twofactor_gauthenticator_form'));

        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));
        $rcmail->output->send('plugin');
    }

    // save config
    public function twofactor_gauthenticator_save()
    {
        $rcmail = rcmail::get_instance();

        // Verify user is authenticated before allowing changes (by Stephen K. Gielda <security@codamail.com>)
        if (!$rcmail->user->ID) {
            header('Location: ?_task=login');
            exit;
        }

        // 2022-04-03: Corrected security incidente reported by kototilt@haiiro.dev
        //					"2FA in twofactor_gauthenticator can be bypassed allowing an attacker to disable 2FA or change the TOTP secret."
        //
        // Solution: if user don't have session created by any rendered page, we kick out
        $config_2FA = self::__get2FAconfig();
        if ($config_2FA['activate'] && !$this->__is2FAFresh()) {
            $this->__exitSession();
        }

        $this->add_texts('localization/', true);
        $this->register_handler('plugin.body', array($this, 'twofactor_gauthenticator_form'));
        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));

        // POST variables
        $activate = rcube_utils::get_input_value('2FA_activate', rcube_utils::INPUT_POST);
        $secret = rcube_utils::get_input_value('2FA_secret', rcube_utils::INPUT_POST);
        $recovery_codes = rcube_utils::get_input_value('2FA_recovery_codes', rcube_utils::INPUT_POST);

        // remove recovery codes without value
        $recovery_codes = array_values(array_diff($recovery_codes, array('')));

        $data = self::__get2FAconfig();
        $data['secret'] = $secret;
        $data['activate'] = $activate ? true : false;
        $data['recovery_codes'] = $recovery_codes;
        self::__set2FAconfig($data);

        // if we can't save time into SESSION, the plugin logouts
        $_SESSION['twofactor_gauthenticator_2FA_login'] = time();

        $rcmail->output->show_message($this->gettext('successfully_saved'), 'confirmation');

        $rcmail->overwrite_action('plugin.twofactor_gauthenticator');
        $rcmail->output->send('plugin');
    }

    // form config
    public function twofactor_gauthenticator_form()
    {
        $rcmail = rcmail::get_instance();

        $this->add_texts('localization/', true);
        $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));

        $data = self::__get2FAconfig();

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

        $html_check_code = '<br /><br /><input type="button" class="button mainaction" id="2FA_check_code" value="'.$this->gettext('check_code').'"> &nbsp;&nbsp; <input type="text" id="2FA_code_to_check" maxlength="10">';

        $html_help_code = '<br /><br /> &#9432; '.$this->gettext('msg_help');

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
        $code   = rcube_utils::get_input_value('code', rcube_utils::INPUT_GET);
        $secret = rcube_utils::get_input_value('secret', rcube_utils::INPUT_GET);

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
            $this->__clearFailedAttempts();
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

    // redirect to some RC task and remove 'login' user pref
    private function __goingRoundcubeTask($task = 'mail', $action = null)
    {

        $_SESSION['twofactor_gauthenticator_2FA_login'] = time();
        header('Location: ?_task='.$task . ($action ? '&_action='.$action : ''));
        exit;
    }

    private function __exitSession()
    {
        $rcmail = rcmail::get_instance();

        // 1. Clear the plugin remember-me cookie (needs username/secret-derived name) first.
        $this->__clearRememberMeCookie();

        // 2. Full server-side teardown via the Roundcube API (rcmail.php ~1007):
        //    rcmail::kill_session() -> rcube_session::kill() (rcube_session.php ~410) which
        //    destroys the stored session, invalidates the session ID (cannot be resumed),
        //    clears the Roundcube session cookie, and resets $_SESSION to language+temp.
        $rcmail->kill_session();

        // 3. Belt-and-suspenders: drop the plugin markers explicitly. The V-1 failure
        //    counter lives in the per-key JSON state file in log_dir (NOT in $_SESSION)
        //    and MUST survive this teardown to keep rate-limiting across logins.
        unset($_SESSION['twofactor_gauthenticator_login']);
        unset($_SESSION['twofactor_gauthenticator_2FA_login']);

        // 4. UI courtesy redirect only. Teardown already happened server-side; the redirect is
        //    not the destruction mechanism and needs no request token here.
        header('Location: ?_task=login');
        exit;
    }

    private function __get2FAconfig()
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;

        $arr_prefs = $user->get_prefs();
        $data = $arr_prefs['twofactor_gauthenticator'] ?? array();
        //decrypt
        if (!is_array($data) && $rcmail->config->get('twofactor_pref_encrypt'))
        {
            $cdata = json_decode($rcmail->decrypt($data));
            if ($cdata == null)
            {
                rcube::write_log('twofactor_gauthenticator',"WARN: Broken 2FA!, clearing...");
                $cdata = array();
            }
            $data = (array)$cdata;
        }
        return $data;
    }

    // we can set array to NULL to remove
    private function __set2FAconfig($data)
    {
        $rcmail = rcmail::get_instance();
        $user = $rcmail->user;

        $arr_prefs = $user->get_prefs();
        if ($data['activate'] != true) {
            // if deactivated, remove all data (secret was still generated and couldn't be removed)
            $data = array();
        }
        //encrypt
        if ($data && $rcmail->config->get('twofactor_pref_encrypt'))
        {
            $edata = $rcmail->encrypt(json_encode($data));
            $data = $edata != null ? $edata: $data;
        }
        $arr_prefs['twofactor_gauthenticator'] = $data;
        rcube::write_log('twofactor_gauthenticator',"WARN: 2FA may have changed!");
        return $user->save_prefs($arr_prefs);
    }

    private function __isRecoveryCode($code)
    {
        $prefs = self::__get2FAconfig();
        return in_array($code, $prefs['recovery_codes']);
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
