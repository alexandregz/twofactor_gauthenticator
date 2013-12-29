<?php
/**
 * Values to enrollment process.
 * 
 * Rename (and config!) this file to enrollment.inc.php and enrollment process is REQUIRED for ALL users (new users and old without activation)
 * If user config not exists, plugin uses default values
 * 
 * IMPORTANT: secret must be a valid secret! (= 16 chars)
 */
$rcmail_config['enrollment'] = array();

$users = array(
		'default' => array(
				'activate' => true,
				'secret' => null,
				'recovery_codes' => array('recovery1')
		),

		'user1@domain.tld' => array(
				'activate' => true,
				'secret' => 'aaaaaaaaaa',
				'recovery_codes' => array('test1', 'test2')
		),

		'user2@domain.tld' => array(
				'activate' => true,
				'secret' => 'ZZZZZZZZZ',
				'recovery_codes' => array('test3')
		),

);

$rcmail_config['enrollment'] = $users;