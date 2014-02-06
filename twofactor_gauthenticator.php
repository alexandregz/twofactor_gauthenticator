<?php
/**
 * Two-factor Google Authenticator for RoundCube
 * 
 * Uses https://github.com/PHPGangsta/GoogleAuthenticator/ library
 * form js from dynalogin plugin (https://github.com/amaramrahul/dynalogin/)
 * 
 * Also thx	 to Victor R. Rodriguez Dominguez for some ideas and support (https://github.com/vrdominguez) 
 *
 * @version 1.1
 *
 * Author(s): Alexandre Espinosa <aemenor@gmail.com>, Ricardo Signes <rjbs@cpan.org>
 * Date: 2013-11-30
 */
require_once 'PHPGangsta/GoogleAuthenticator.php';

class twofactor_gauthenticator extends rcube_plugin 
{
	private $_number_recovery_codes = 4;
	
    function init() 
    {
		$rcmail = rcmail::get_instance();
		
		// hooks
    	$this->add_hook('login_after', array($this, 'login_after'));
    	$this->add_hook('send_page', array($this, 'check_2FAlogin'));
    	$this->add_hook('render_page', array($this, 'popup_msg_enrollment'));
    	    	 
    	$this->load_config();
    	 
		$this->add_texts('localization/', true);
		
		// check code with ajax
		$this->register_action('plugin.twofactor_gauthenticator-checkcode', array($this, 'checkCode'));
		
		// config
		$this->register_action('twofactor_gauthenticator', array($this, 'twofactor_gauthenticator_init'));
		$this->register_action('plugin.twofactor_gauthenticator-save', array($this, 'twofactor_gauthenticator_save'));
		$this->include_script('twofactor_gauthenticator.js');
    }
    
    
    // Use the form login, but removing inputs with jquery and action (see twofactor_gauthenticator_form.js)
    function login_after($args)
    {
		$_SESSION['twofactor_gauthenticator_login'] = time;
		
		$rcmail = rcmail::get_instance();
		
		$config_2FA = self::__get2FAconfig();
		if(!$config_2FA['activate'])
		{
			if($rcmail->config->get('force_enrollment_users'))
			{
				$this->__goingRoundcubeTask('settings', 'plugin.twofactor_gauthenticator');				
			}
			return;
		}
    	
    	$rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));

    	$this->add_texts('localization', true);
    	$this->include_script('twofactor_gauthenticator_form.js');
    	
    	$rcmail->output->send('login');
    }
    
	// capture webpage if someone try to use ?_task=mail|addressbook|settings|... and check auth code
	function check_2FAlogin($p)
	{
		$rcmail = rcmail::get_instance();
		$config_2FA = self::__get2FAconfig();
		
		if($config_2FA['activate'])
		{
			$code = get_input_value('_code_2FA', RCUBE_INPUT_POST);
			if($code)
			{
				if(self::__checkCode($code) || self::__isRecoveryCode($code))
				{
					if(self::__isRecoveryCode($code))
					{
						self::__consumeRecoveryCode($code);
					}
					$this->__goingRoundcubeTask('mail');
				}
				else
				{
					$this->__exitSession();
				}
			}
			// we're into some task but marked with login...
			elseif($rcmail->task !== 'login' && ! $_SESSION['twofactor_gauthenticator_2FA_login'] >= $_SESSION['twofactor_gauthenticator_2FA_login'])
			{
				$this->__exitSession();
			}
			
		}
		elseif($rcmail->config->get('force_enrollment_users') && ($rcmail->task !== 'settings' || $rcmail->action !== 'plugin.twofactor_gauthenticator'))	
		{
			if($rcmail->task !== 'login')	// resolve some redirection loop with logout
			{
				$this->__goingRoundcubeTask('settings', 'plugin.twofactor_gauthenticator');
			}
		}

		return $p;
	}
	
	
	// ripped from new_user_dialog plugin
	function popup_msg_enrollment()
	{
		$rcmail = rcmail::get_instance();
		$config_2FA = self::__get2FAconfig();
		
		if(!$config_2FA['activate'] 
			&& $rcmail->config->get('force_enrollment_users') && $rcmail->task == 'settings' && $rcmail->action == 'plugin.twofactor_gauthenticator')
		{
			// add overlay input box to html page
			$rcmail->output->add_footer(html::tag('form', array(
					'id' => 'enrollment_dialog',
					'method' => 'post'),
					html::tag('h3', null, $this->gettext('enrollment_dialog_title')) .
					$this->gettext('enrollment_dialog_msg')
			));

			$rcmail->output->add_script(
					"$('#enrollment_dialog').show().dialog({ modal:true, resizable:false, closeOnEscape: true, width:420 });", 'docready'
			);
		}		
	}
	

	// show config
    function twofactor_gauthenticator_init() 
    {
        $rcmail = rcmail::get_instance();
       
        $this->add_texts('localization/', true);
        $this->register_handler('plugin.body', array($this, 'twofactor_gauthenticator_form'));
        
        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));
        $rcmail->output->send('plugin');
    }

    // save config
    function twofactor_gauthenticator_save() 
    {
        $rcmail = rcmail::get_instance();
        
        $this->add_texts('localization/', true);
        $this->register_handler('plugin.body', array($this, 'twofactor_gauthenticator_form'));
        $rcmail->output->set_pagetitle($this->gettext('twofactor_gauthenticator'));
        
        // POST variables
        $activar = get_input_value('2FA_activate', RCUBE_INPUT_POST);
        $secret = get_input_value('2FA_secret', RCUBE_INPUT_POST);
        $recovery_codes = get_input_value('2FA_recovery_codes', RCUBE_INPUT_POST);
        
        // remove recovery codes without value
        $recovery_codes = array_values(array_diff($recovery_codes, array('')));        
        
		$data = self::__get2FAconfig();
       	$data['secret'] = $secret;
       	$data['activate'] = $activar ? true : false;
       	$data['recovery_codes'] = $recovery_codes;
        self::__set2FAconfig($data);

        // if we can't save time into SESSION, the plugin logouts
        $_SESSION['twofactor_gauthenticator_2FA_login'] = time;        
        
		$rcmail->output->show_message($this->gettext('successfully_saved'), 'confirmation');
         
        rcmail_overwrite_action('plugin.twofactor_gauthenticator');
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
        $table->add('title', html::label($field_id, Q($this->gettext('activate'))));
		$checked = $data['activate'] ? null: 1; // :-?
        $table->add(null, $checkbox_activate->show( $checked )); 

        
        // secret
        $field_id = '2FA_secret';
        $input_descsecret = new html_inputfield(array('name' => $field_id, 'id' => $field_id, 'size' => 60, 'type' => 'password', 'value' => $data['secret']));
        $table->add('title', html::label($field_id, Q($this->gettext('secret'))));
        $html_secret = $input_descsecret->show();
        if($data['secret'])
        {
        	$html_secret .= '<input type="button" class="button mainaction" id="2FA_change_secret" value="'.$this->gettext('show_secret').'">';
        }
        else
        {
        	$html_secret .= '<input type="button" class="button mainaction" id="2FA_create_secret" value="'.$this->gettext('create_secret').'">';
        }
        $table->add(null, $html_secret);
        
        
        // recovery codes
       	$table->add('title', $this->gettext('recovery_codes'));
        	
       	$html_recovery_codes = '';
       	$i=0;
       	for($i = 0; $i < $this->_number_recovery_codes; $i++)
       	{
       		$value = isset($data['recovery_codes'][$i]) ? $data['recovery_codes'][$i] : '';
       		$html_recovery_codes .= ' <input type="password" name="2FA_recovery_codes[]" value="'.$value.'" maxlength="10"> &nbsp; ';
       	}
       	$html_recovery_codes .= '<input type="button" class="button mainaction" id="2FA_show_recovery_codes" value="'.$this->gettext('show_recovery_codes').'">';
       	$table->add(null, $html_recovery_codes);
        
        
        // qr-code
        if($data['secret']) {
			$table->add('title', $this->gettext('qr_code'));
        	$table->add(null, '<input type="button" class="button mainaction" id="2FA_change_qr_code" value="'.$this->gettext('show_qr_code').'"> 
        						<div id="2FA_qr_code" style="display: none;"><img src="'.self::__getQRCodeGoogle().'" /></div>');
        }
        
        // infor
        $table->add(null, '<td><br>'.$this->gettext('msg_infor').'</td>');

        // button to setup all fields if doesn't exists secret
        $html_setup_all_fields = '';
        if(!$data['secret']) {
        	$html_setup_all_fields = '<input type="button" class="button mainaction" id="2FA_setup_fields" value="'.$this->gettext('setup_all_fields').'">';
        }
        
        $html_check_code = '<br /><br /><input type="button" class="button mainaction" id="2FA_check_code" value="'.$this->gettext('check_code').'"> <input type="text" id="2FA_code_to_check" maxlength="10">';
        
        
        
        // Build the table with the divs around it
        $out = html::div(array('class' => 'settingsbox', 'style' => 'margin: 0;'),
        html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('twofactor_gauthenticator') . ' - ' . $rcmail->user->data['username']) .  
        html::div(array('class' => 'boxcontent'), $table->show() . 
            html::p(null, 
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
        
        return $out;
    }
    
    
    // used with ajax
    function checkCode() {
    	$code = get_input_value('code', RCUBE_INPUT_GET);
    	$secret = get_input_value('secret', RCUBE_INPUT_GET);
    	
    	if(self::__checkCode($code, $secret))
    	{
    		echo $this->gettext('code_ok');
    	}
    	else
    	{
    		echo $this->gettext('code_ko');
    	}
    	exit;
    }    
    
	
	//------------- private methods
	
	// redirect to some RC task and remove 'login' user pref
    private function __goingRoundcubeTask($task='mail', $action=null) {
    		
        $_SESSION['twofactor_gauthenticator_2FA_login'] = time;
    	header('Location: ?_task='.$task . ($action ? '&_action='.$action : '') );
    	exit;
    }

    private function __exitSession() {
        unset($_SESSION['twofactor_gauthenticator_login']);
        unset($_SESSION['twofactor_gauthenticator_2FA_login']);
    
    	header('Location: ?_task=logout');
    	exit;
    }
    
	private function __get2FAconfig()
	{
		$rcmail = rcmail::get_instance();
		$user = $rcmail->user;

		$arr_prefs = $user->get_prefs();
		return $arr_prefs['twofactor_gauthenticator'];
	}
	
	// we can set array to NULL to remove
	private function __set2FAconfig($data)
	{
		$rcmail = rcmail::get_instance();
		$user = $rcmail->user;
	
		$arr_prefs = $user->get_prefs();
		$arr_prefs['twofactor_gauthenticator'] = $data;
		
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

	// returns string (url to img)
	private function __getQRCodeGoogle()
	{
		$rcmail = rcmail::get_instance(); 
		
		$ga = new PHPGangsta_GoogleAuthenticator();
		return $ga->getQRCodeGoogleUrl($rcmail->user->data['username'], self::__getSecret(), 'RoundCube2FA');
	}
	
	// returns boolean
	private function __checkCode($code, $secret=null)
	{
		$ga = new PHPGangsta_GoogleAuthenticator();
		return $ga->verifyCode( ($secret ? $secret : self::__getSecret()), $code, 2);    // 2 = 2*30sec clock tolerance
	} 
}
