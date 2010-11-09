<?php
/**
 * Systems class
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
class System
{
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
