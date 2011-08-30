<?php
/**
 * Machine class
 *
 * @package Framework
 */

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
			
			$tmp = array();
			$tmp['uid'] = $uid;
			$tmp['gid'] = $gid;
			$tmp['name'] = array_shift(explode(',', $info));
			$tmp['path'] = $path;
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
}
