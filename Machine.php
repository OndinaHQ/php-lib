<?php
/**
 * Machine class
 *
 * @package Framework
 */

require_once('API.php');

/**
 * Machine
 * 
 * System access management class.
 *
 * @author Marco Ceppi <marco@ceppi.net>
 * @package Framework
 * @subpackage Machine
 */
class Machine
{
	public static function chown( $path, $user, $group, $recursive = false )
	{
		if( $recursive )
		{
			$directory = opendir($path);
			
			while( ($file = readdir($directory)) !== false )
			{
				if( $file != '.' && $file != '..' )
				{
					$typepath = $path . '/' . $file;
					
					if( filetype($typepath) == 'dir' )
					{
						self::chown($typepath, $user, $group, true);
					}
					
					chown($typepath, $user);
					chgrp($typepath, $group);
				}
			}
		}
		else
		{
			chown($path, $user);
			chgrp($path, $group);
		}
	}
	
	/**
	 * Remove a file or path
	 * 
	 * Performs a recursive delete on directories, or simply deletes a file
	 * 
	 * @param mixed $paths Array of paths, or path to be removed
	 * @param boolean $recursive Whether or not directories should be recursively removed
	 * 
	 * @return boolean true|false
	 */
	public static function rm( $paths, $recursive = false )
	{
		if( !is_array($paths) )
		{
			$paths = array($paths);
		}
		
		if( $recursive )
		{
			foreach( $paths as $path )
			{
				$directory = opendir($path);
				
				while( ($file = readdir($directory)) !== false )
				{
					if( $file != '.' && $file != '..' )
					{
						$typepath = $path . '/' . $file;
						
						if( filetype($typepath) == 'dir' )
						{
							self::rm($typepath, true);
							
							if( !@rmdir($typepath) )
							{
								return false;
							}
						}
						else
						{
							if( !@unlink($typepath) )
							{
								return false;
							}
						}
					}
				}
				
				@rmdir($path);
			}
		}
		else
		{
			foreach( $paths as $path )
			{
				$func = (is_dir($file)) ? 'rmdir' : 'unlink';
				
				if( !@$func($file) )
				{
					return false;
				}
			}
		}
	}
	
	public static function users()
	{
		$users = array();
		
		if( !$data_array = file('/etc/passwd') )
		{
			return false;
		}
		
		foreach( $data_array as $line )
		{
			$data = explode(":", $line);
			$user = array_shift($data);
			
			list(, $uid, $gid, $info, $path, $terminal) = $data;
			$info = explode(',', $info);
			
			$tmp = array();
			$tmp['uid'] = $uid;
			$tmp['gid'] = $gid;
			$tmp['path'] = ( !empty($info[0]) && @is_dir($info[0]) ) ? $info[0] : $path;
			$tmp['terminal'] = $terminal;
			
			$users[$user] = $tmp;
			
			unset($tmp);
		}
		
		return $users;
	}
	
	public static function user( $user )
	{
		$users = self::users();
		
		if( array_key_exists($user, $users) )
		{
			return $users[$user];
		}
		else
		{
			return false;
		}
	}
	
	public static function domains()
	{
		$domains = file('/etc/localdomains', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		return $domains;
	}
	
	public static function is_domain( $domain )
	{
		$domains = self::domains();
		
		return ( in_array($domain, $domains) ) ? true : false;
	}
	
	public static function owner( $domain )
	{
		$userdomains = file('/etc/userdomains', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		$matches = preg_grep('/^' . $domain . ' /', $userdomains);
		
		if( count($matches) > 0 )
		{
			list( , $user) = explode(' ', array_shift($matches));
			
			return $user;
		}
		
		return false;
	}
	
	public static function user_owns( $user, $domain )
	{
		$userdomains = self::userdomains($user);
		
		return (in_array($domain, $userdomains)) ? true : false;
	}
	
	public static function userdomains( $user = false )
	{
		$userdomains_contents = file('/etc/userdomains', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$userdomains = array();
		
		foreach( $userdomains_contents as $line )
		{
			list($domain, $owner) = explode(' ', $line);
			
			if( !array_key_exists($owner, $userdomains) )
			{
				$userdomains[$owner] = array();
			}
			
			$userdomains[$owner][] = $domain;
			
			unset($owner, $domain);
		}
		
		if( !$user )
		{
			return $userdomains;
		}
		elseif( is_array($user) )
		{
			$output = array();
			
			foreach( $user as $el )
			{
				$output[$el] = $userdomains[$el];
			}
			
			return $output;
		}
		else
		{
			return $userdomains[$user];
		}
	}
	
	/**
	 * SSH Key Generation
	 * 
	 * Generate an SSH key
	 * 
	 * @param string $filepath Path for the new key
	 * @param array $options Optional list of additional options (comment, passphrase)
	 * 
	 * @return boolean
	 */
	public static function ssh_keygen( $filepath, $options = array() )
	{
		$default_options = array('comment' => 'autogend@' . date('Ymd.Hi'), 'passphrase' => '');
		$options = $options + $default_options;
		
		if( !is_dir(dirname($filepath)) )
		{
			mkdir(dirname($filepath), 0750, true);
		}
		
		if( !is_file($filepath) )
		{
			system(sprintf('/usr/bin/ssh-keygen -q -f %s -N "%s" -C "%s"', $filepath, $options['passphrase'], $options['comment']), $status);
			
			return ( $status > 0 ) ? false : true;
		}
		
		return false;
	}
}

class Machine_Client extends API
{
	public static function domains()
	{
		return json_decode(static::get(static::$server, 'machine/domains'), true);
	}
	
	public static function userdomains( $user = false )
	{
		$userdomains = json_decode(static::get(static::$server, 'machine/userdomains'), true);
		
		if( !$user )
		{
			return $userdomains;
		}
		elseif( is_array($user) )
		{
			$output = array();
			
			foreach( $user as $el )
			{
				$output[$el] = $userdomains[$el];
			}
			
			return $output;
		}
		else
		{
			return $userdomains[$user];
		}
	}
	
	public static function user_owns( $user, $domain )
	{
		$userdomains = static::userdomains($user);
		
		return (in_array($domain, $userdomains)) ? true : false;
	}
	
	public static function is_domain( $domain )
	{
		$domains = static::domains();
		
		return ( in_array($domain, $domains) ) ? true : false;
	}
	
	public static function ssh_keys( $account )
	{
		return json_decode(static::get(static::$server, 'machine/ssh_keys/' . $account), true);
	}
	
	public static function ssh_copy_id( $account, $key )
	{
		if( is_file($key) )
		{
			$key = file_get_contents($key);
		}
		
		return static::set(static::$server, 'machine/ssh_key/' . $account, $key);
	}
	
	public static function ssh_set_ids( $account, $keys )
	{
		if( is_array($keys) )
		{
			$keys = implode("\n", $keys);
		}
		
		return static::set(static::$server, 'machine/ssh_keys/' . $account, $keys);
	}
	
	public static function archive( $user, $data )
	{
		return static::set(static::$server, 'machine/archive/' . $user, $data);
	}
	
	public static function remove_domain($user, $domain)
	{
		return static::delete(static::$server, 'machine/userdomains/' . $user . '/' . $domain);
	}
	
	public static function create_account($account)
	{
		return static::put(static::$server, 'machine/account/create/', $account);
	}
}
