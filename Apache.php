<?php
/**
 * Apache class
 *
 * @package Framework
 */

define('APACHE_DIR', '/etc/apache2');
define('APACHE_TEST_FAILED', 'Apache failed testing, changes reverted.');
define('APACHE_RELOAD_FAILED', 'Apache failed during reload, any changes have been reverted.');
define('APACHE_RESTART_FAILED', 'Apache failed during a restart.');
define('APACHE_DISABLE_FAILED', 'The domain name was not disabled.');
define('APACHE_ENABLE_FAILED', 'The domain name was not enabled.');
define('APACHE_TEMPLATE_NOTFOUND', 'The template file could not be loaded.');

/**
 * Apache
 * 
 * Apache management class.
 *
 * @author Marco Ceppi <marco@ceppi.net>
 * @package Framework
 * @subpackage Apache
 */
class Apache extends Meta
{
	public static $path = '/etc/meta/vhost';
	
	public static function reload()
	{
		return self::_cmd('reload');
	}
	
	public static function restart()
	{
		return self::_cmd('restart');
	}
	
	public static function stop()
	{
		return self::_cmd('stop');
	}
	
	public static function start()
	{
		return self::_cmd('start');
	}
	
	private static function _cmd($action)
	{
		system("service apache2 $action", $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	public static function config( $user, $domain )
	{
		// Load meta data
		
		// Load template
		
		// Perform a replace
		
		// Check for missing keys
		
		// Save configuration file
		
		// Exit
	}
	
	public static function enable( $user, $domain )
	{
		if( is_file(APACHE_DIR . "/sites-available/$user/$domain") )
		{
			if( !symlink("../sites-available/$user/$domain", APACHE_DIR . "/sites-enabled/$user--$domain") )
			{
				throw new Exception(APACHE_ENABLE_FAILED);
			}
			
			if( !self::test() )
			{
				if( !self::disable($user, $domain) )
				{
					throw new Exception(APACHE_DISABLE_FAILED);
				}
				
				throw new Exception(APACHE_TEST_FAILED);
			}
			
			if( !self::reload() )
			{
				if( !self::disable($user, $domain) )
				{
					throw new Exception(APACHE_DISABLE_FAILED);
				}
				
				system('service apache2 restart', $restart_status);
				
				if( !self::restart() )
				{
					throw new Exception(APACHE_RESTART_FAILED);
				}
				else
				{
					throw new Exception(APACHE_ENABLE_FAILED);
				}
			}
		}
		else
		{
			IO::error(INVALID_SELECTION, 'Unable to locate site configuration file');
		}
	}
	
	public static function disable( $user, $domain )
	{
		return unlink(APACHE_DIR . "/sites-enabled/$user--$domain");
	}
	
	public static function delete( $user, $domain )
	{
		
	}
	
	public static function test()
	{
		$msg = system('apachectl testconfig', $status);
		
		return ( $status == 0 && $msg == 'Syntax OK' ) ? true : false
	}
	
	public static function save( $user, $domain, $data )
	{
		// After we save the meta data we need to rebuild the configuration file
		if( parent::save($user, $domain, $data) )
		{
			return self::config($user, $domain);
		}
		else
		{
			return false;
		}
	}
}
