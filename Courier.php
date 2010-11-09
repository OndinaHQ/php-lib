<?php
/**
 * Couier class
 *
 * @package             Framework
 */

require_once('Machine.php');

/**
 * Courier management class.
 *
 * @author              Marco Ceppi <marco.ceppi@seacrow.org>
 * @since               November 9, 2010
 * @package             Framework
 * @subpackage          Users
 */
class Courier
{
	function Courier()
	{
		// Not sure if we're going to construct yet...
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
			list($user, $opts) = explode("\t", $line);
			
			$tmp = array();
			$opts = explode('|', $opts);
			
			foreach( $opts as $opt ) 
			{
				list($key, $val) = explode('=', $opt);
				
				$tmp[$key] = $val;
			}
			
			$users[$user] = $tmp;
		}
		
		return $users;
	}
	
	public static function user( $username )
	{
		$users = self::users();
		
		if( array_key_exists($username, $users) )
		{
			return $users[$username];
		}
		else
		{
			return false;
		}
	}
	
	public static function makeUserdb()
	{
		system('makeuserdb', $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	public static function makeMailDir( $dir )
	{
		system("maildirmake $dir", $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	public static function removeAccount( $username )
	{
		if( !$userdata = self::user($username) )
		{
			return false;
		}
		
		Machine::rm($userdata['home'], true);
		
		system("userdb $email del", $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	public static function addAccount( $email, $home, $mail, $mail_uid = NULL, $mail_gid = NULL )
	{
		if( is_null($mail_uid) || is_null($mail_gid) )
		{
			$userdata = Machine::users('mail');
			
			$mail_uid = (is_null($mail_uid)) ? $userdata['uid'] : $mail_uid;
			$mail_gid = (is_null($mail_gid)) ? $userdata['gid'] : $mail_gid;
		}
		
		if( !is_dir($home) )
		{
			if( !mkdir( $home ) )
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
		self::makeMailDir($mail);
		Machine::chown(MAIL_DIR . $domain, MAIL_USER, MAIL_USER, true);

		system("userdb $email set uid=$mail_uid gid=$mail_gid home=$home mail=$mail", $status);
		
		if( $status > 0 )
		{
			//Whoops. We've got an issue. Backup slowly.
			self::removeAccount($email);
			
			
		self::makeUserdb();
	}
}
?>
