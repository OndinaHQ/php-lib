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
define('APACHE_TEMPLATE_MISMATCH', 'Not all parameters were satisfied in the template.');

require_once('Meta.php');

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
	
	private static function _cmd( $action )
	{
		system("service apache2 $action", $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	public static function defaults( $template )
	{
		if( !is_file($template) )
		{
			$template_file = OWN_PATH . '/templates/' . $template . '.tpl';
		}
		else
		{
			$template_file = $template;
		}
		
		if( is_file($template_file) )
		{
			if( preg_match_all("/\[([A-Z_-]*?)\]/", file_get_contents($template_file), $matches) > 0 )
			{
				$defaults = array_unique($matches[1]);
				
				return $defaults;
			}
			else
			{
				return false;
			}
		}
		else
		{
			throw new Exception(APACHE_TEMPLATE_NOTFOUND);
		}
	}
	
	public static function generate( $user, $domain )
	{
		$meta = self::get($user, $domain, true);
		
		$template_file = OWN_PATH . '/templates/' . $meta['global']['template'] . '.tpl';
		$params = $meta['params'];
		
		if( !is_file($template_file) )
		{
			throw new Exception(APACHE_TEMPLATE_NOTFOUND);
		}
		
		$defaults = self::defaults($template_file);
		$config = file_get_contents($template_file);
		
		foreach( $defaults as $arr_key )
		{
			if( !array_key_exists($arr_key, $params) )
			{
				throw new Exception(APACHE_TEMPLATE_MISMATCH);
			}
			
			$config = str_replace("[$arr_key]", $params[$arr_key], $config);
		}
		
		// This really should never occur based on our previous check. Though you can never be too sure
		if( preg_match("/\[(A-Z)*?\]/", $config) )
		{
			throw new Exception(APACHE_TEMPLATE_MISMATCH);
		}
		
		if( !is_dir(APACHE_DIR . "/sites-available/$user") )
		{
			mkdir(APACHE_DIR . "/sites-available/$user");
		}
		
		return (file_put_contents(APACHE_DIR . "/sites-available/$user/$domain", $config) === false) ? false : true;
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
		$msg = system('apache2ctl configtest 2>&1', $status);
		
		return ( $status == 0 && $msg == 'Syntax OK' ) ? true : false;
	}
	
	public static function domains( $username )
	{
		return static::get($username, '', true);
	}
	
	/** Not sure about this.
	public static function save( $user, $domain, $data )
	{
		// After we save the meta data we need to rebuild the configuration file
		if( parent::save($user, $domain, $data) )
		{
			return self::generate($user, $domain);
		}
		else
		{
			return false;
		}
	}
	*/
}

class Apache_Client extends API
{
	public static function add($template, $data)
	{
		//data = user, domain, serveradmin, [all-other-variables-from-tpl]
	}
	
	public static function domains( $username )
	{
		return json_decode(static::get(static::$server, 'apache/domains/' . $username), true);
	}
}
