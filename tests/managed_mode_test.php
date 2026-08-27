<?php

class rcube_plugin
{
    public $hooks = array();
    public function load_config() {}
    public function add_hook($name, $callback) { $this->hooks[] = $name; }
    public function gettext($name) { return $name; }
    public function add_texts($path, $merge = false) {}
    public function register_action($name, $callback) {}
    public function include_script($path) {}
}

class html_inputfield
{
    private $attributes;
    public function __construct($attributes) { $this->attributes = $attributes; }
    public function show() { return '<input name="' . $this->attributes['name'] . '">'; }
}

class html
{
    public static function label($id, $value) { return $value; }
    public static function quote($value) { return $value; }
}

class test_config
{
    public $values = array();

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
}

class test_user
{
    public $ID = 1;
    public $data = array('username' => 'test@example.test');

    public function get_prefs()
    {
        return array();
    }
}

class rcmail
{
    public $config;
    public $user;

    private static $instance;

    public function __construct()
    {
        $this->config = new test_config();
        $this->user = new test_user();
    }

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_user_name()
    {
        return $this->user->data['username'];
    }
}

class rcube
{
    public static $errors = array();
    public static $logs = array();

    public static function raise_error($message, $log = false, $terminate = false)
    {
        self::$errors[] = $message;
    }

    public static function write_log($name, $message)
    {
        self::$logs[] = array($name, $message);
    }
}

require dirname(__DIR__) . '/twofactor_gauthenticator.php';

function invoke_private($object, $method, array $arguments = array())
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs($object, $arguments);
}

function assert_true($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$runtime = sys_get_temp_dir() . '/roundcube-2fa-test-' . bin2hex(random_bytes(6));
assert_true(mkdir($runtime, 0700), 'create private test directory');
$secret = $runtime . '/secret';
$rate = $runtime . '/rate';
assert_true(file_put_contents($secret, "JBSWY3DPEHPK3PXP\n") !== false, 'write secret');
chmod($secret, 0640);
assert_true(mkdir($rate, 0700), 'create rate-limit directory');

$rcmail = rcmail::get_instance();
$rcmail->config->values = array(
    'twofactor_managed_secret_file' => $secret,
    'twofactor_bypass_env' => array('REMOTE_USER', 'KRB5CCNAME'),
    'twofactor_rate_limit_dir' => $rate,
    'twofactor_rate_limit_attempts' => 2,
    'twofactor_rate_limit_window' => 600,
    'twofactor_rate_limit_lockout' => 900,
    'enable_fail_logs' => true,
    'des_key' => 'test-only-key',
);
$_SERVER['REMOTE_ADDR'] = '192.0.2.10';

$plugin = new twofactor_gauthenticator();
$_SESSION = array();
assert_true(invoke_private($plugin, '__secondFactorPending') === false, 'no pending second factor without login marker');
$_SESSION['twofactor_gauthenticator_login'] = 100;
assert_true(invoke_private($plugin, '__secondFactorPending') === true, 'pending second factor after password login');
$_SESSION['twofactor_gauthenticator_2FA_login'] = 100;
assert_true(invoke_private($plugin, '__secondFactorPending') === false, 'second factor marker completes login');
$_SESSION = array();
assert_true(invoke_private($plugin, '__managedSecret') === 'JBSWY3DPEHPK3PXP', 'read valid managed secret');
assert_true(invoke_private($plugin, '__preAuthenticateEnabled') === false, 'pre-authentication is opt-in');
assert_true(invoke_private($plugin, '__bypassActive') === false, 'require every bypass variable');
$_SERVER['REMOTE_USER'] = 'user@example.test';
$_SERVER['KRB5CCNAME'] = 'FILE:/run/test';
assert_true(invoke_private($plugin, '__bypassActive') === true, 'activate trusted upstream bypass');
unset($_SERVER['REMOTE_USER'], $_SERVER['KRB5CCNAME']);

$_SERVER['REMOTE_USER'] = 'user@example.test';
$_SERVER['KRB5CCNAME'] = 'FILE:/run/test';
$bypassPlugin = new twofactor_gauthenticator();
$bypassPlugin->init();
assert_true(in_array('loginform_content', $bypassPlugin->hooks, true), 'Kerberos bypass keeps plugin UI hooks registered');
unset($_SERVER['REMOTE_USER'], $_SERVER['KRB5CCNAME']);

$first = invoke_private($plugin, '__rateState', array(true));
$second = invoke_private($plugin, '__rateState', array(true));
assert_true($first['blocked'] === false && $first['attempts'] === 1, 'allow first failure');
assert_true($second['blocked'] === true && $second['attempts'] === 2, 'lock at configured limit');

chmod($secret, 0666);
assert_true(invoke_private($plugin, '__managedSecret') === null, 'reject writable managed secret');
assert_true(count(rcube::$errors) === 1, 'report unsafe secret once');

$rcmail->config->values['twofactor_pre_authenticate'] = true;
$form = $plugin->loginform_content(array('inputs' => array(
    'user' => array('content' => '<input name="_user">'),
    'pass' => array('content' => '<input name="_pass">'),
)));
assert_true(isset($form['inputs']['user'], $form['inputs']['pass'], $form['inputs']['twofactor']), 'append OTP to the initial login form');

$_POST['_code_2FA'] = '';
$auth = $plugin->authenticate(array('user' => 'test@example.test', 'pass' => 'not-tested', 'valid' => true));
assert_true(($auth['abort'] ?? false) === true && ($auth['error'] ?? '') === 'loginfailed', 'missing OTP aborts before primary authentication');
assert_true($plugin->check_2FAlogin(array('template' => 'login')) === array('template' => 'login'), 'pre-auth mode skips the legacy second OTP processor');

foreach (glob($rate . '/*') ?: array() as $file) {
    unlink($file);
}
rmdir($rate);
unlink($secret);
rmdir($runtime);
echo "managed mode tests passed\n";
