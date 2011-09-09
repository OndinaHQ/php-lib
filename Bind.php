<?php
/**
 * Bind class
 *
 * @package Framework
 */

define('BIND_TEMPLATE_NOTFOUND', 'The template file could not be loaded.');

require_once('Meta.php');
require_once('API.php');

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
	
	public static function validate( $raw, $type )
	{
		switch(strtoupper($type))
		{
			case 'CNAME':
			case 'MX':
				
			break;
			case 'A':
				return (filter_var($raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) ? false : true;
			break;
			case 'AAAA':
				return (filter_var($raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) ? false : true;
			case 'TXT':
				return true;
			break;
		}
		
		return false;
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
				'header'  => 'Authorization: Basic ' . API::digest(),
			)
		));
		
		return file_get_contents('https://api:9001/meta/' . str_replace('/etc/meta/', '', $path), false, $context);
	}
	
	protected static function set_raw( $path, $data )
	{
		$context = stream_context_create(array( 
			'http' => array(
				'method'  => 'POST', 
				'header'  => sprintf("Authorization: Basic %s\r\n", API::digest()). "Content-type: application/json\r\n", 
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
	
	public static function generate( $user, $domain )
	{
		// This will pull the META data from disk and return in an assoc array
		$meta = Bind_Client::get($user, $domain, true);
		
		$template_path = OWN_PATH . '/templates';
		$parsed = array();
		
		foreach( $meta['lines'] as $key => $entries )
		{
			$is_opt = $is_ns = false;
			
			switch( $key )
			{
				case 'SOA':
					$zone = str_replace('[USER]', $user, file_get_contents("$template_path/zone.tpl"));
					$zone = str_replace('[DOMAIN]', $domain, $zone);
					$zone = str_replace('[TTL]', $meta['zone']['ttl'], $zone);
					
					foreach( $entries as $el => $val )
					{
						$el = strtoupper($el);
						$zone = str_replace("[$el]", $val, $zone);
					}
					
				break;
				case 'MX':
				case 'TXT':
					$is_opt = true;
				case 'NS':
				case 'A':
				case 'CNAME':
				default:
					if( !array_key_exists($key, $parsed) )
					{
						$parsed[$key] = array();
					}
					
					foreach( $entries as $entry )
					{
						$line = ($is_opt) ? file_get_contents("$template_path/opt-line.tpl") : file_get_contents("$template_path/line.tpl");
						$line = str_replace('[CLASS]', $key, $line);
						
						foreach( $entry as $el => $val )
						{
							$el = strtoupper($el);
							$line = str_replace("[$el]", $val, $line);
						}
						
						$parsed[$key][] = $line;
						unset($line);
					}
				break;
			}
		}
		
		$zone .= implode('', $parsed['NS']);
		unset($parsed['NS']);
		
		foreach( $parsed as $lines )
		{
			$zone .= implode('', $lines);
		}
		
		file_put_contents(NAMED_DIR . "/zones/$user/$domain.db", $zone);
	}
}
