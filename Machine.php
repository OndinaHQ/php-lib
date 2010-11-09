<?php
/**
 * Machine class
 *
 * @package             Framework
 */

/**
 * System access management class.
 *
 * @author              Marco Ceppi <marco.ceppi@seacrow.org>
 * @since               November 8, 2010
 * @package             Framework
 * @subpackage          System
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
}
?>
