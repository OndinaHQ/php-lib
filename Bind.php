<?php
/**
 * Bind class
 *
 * @package Framework
 */

define('BIND_TEMPLATE_NOTFOUND', 'The template file could not be loaded.');

require_once('Meta.php');

define('NAMED_DIR', '/etc/bind');

/**
 * Bind management class.
 *
 * @author Marco Ceppi <marco@ceppi.net>
 * @package Framework
 * @subpackage Bind
 */
class Bind extends Meta
{
	public static $path = '/etc/meta/dns';
	
	public static function defaults( $template )
	{
		$template_file = OWN_PATH . '/templates/' . $template . '.tpl';
		
		if( is_file( OWN_PATH . '/templates/' . $template . '.tpl') )
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
			throw new Exception(BIND_TEMPLATE_NOTFOUND);
		}
	}
}

class Bind_Client extends Bind
{
	// This needs to be a call to the API!
	protected static function get_raw( $path )
	{
		$context = stream_context_create(array(
			'http' => array(
				'method'  => 'GET',
				'header'  => 'Authorization: Basic ' . base64_encode('admin:hello'),
			)
		));
		
		return file_get_contents('https://api:9001/meta/' . str_replace('/etc/meta/', '', $path), false, $context);
	}
	
	protected static function set_raw( $path, $data )
	{
		$context = stream_context_create(array( 
			'http' => array(
				'method'  => 'POST', 
				'header'  => sprintf("Authorization: Basic %s\r\n", base64_encode('admin:hello')). "Content-type: application/json\r\n", 
				'content' => json_encode($data)
			), 
		));
		
		return (!@file_get_contents('https://api:9001/meta/' . str_replace('/etc/meta/', '', $path), false, $context)) ? false : true;
	}
}

class Bind_Server extends Bind
{
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
		system("/etc/init.d/bind9 $action", $status);
		
		return ( $status > 0 ) ? false : true;
	}
}
