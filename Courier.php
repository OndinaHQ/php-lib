<?php
/**
 * Couier class
 *
 * @package             Framework
 */

require_once('Machine.php');
require_once('IO.php');

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
	
	/**
	 * Users Method
	 * 
	 * @return array|false List of users
	 */
	public static function accounts()
	{
		$users = array();
		
		if( !$data_array = file('/etc/courier/userdb') )
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
	
	/**
	 * User Method
	 * 
	 * @param string $username
	 * 
	 * @return array|false List of user details
	 */
	public static function account( $username )
	{
		$users = self::accounts();
		
		if( array_key_exists($username, $users) )
		{
			return $users[$username];
		}
		else
		{
			return false;
		}
	}
	
	public static function aliases( $domain = NULL )
	{
		if( !is_null($domain) )
		{
			if( !Machine::is_domain($domain) )
			{
				return false;
			}
		}
		else
		{
			$domain = '*';
		}
		
		$aliases = array();
		
		$alias_files = glob("/etc/valiases/$domain");
		
		foreach( $alias_files as $alias )
		{
			$domain = basename($alias);
			
			if( !$file = file($alias, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) )
			{
				return false;
			}
			
			foreach( $file as $line )
			{
				list($user, $forwarder) = explode(': ', $line, 2);
				if( $user != '*' )
				{
					$alias = explode(' ', $forwarder);
					$aliases[$domain][$user] = $alias;
				}
				else
				{
					$aliases[$domain][$user] = array($forwarder);
				}
			}
		}
		
		return $aliases;
	}
	
	public static function setAlias( $domain, $data )
	{
		if( Machine::is_domain($domain) )
		{
			$out = array();
			
			foreach( $data as $user => $alias )
			{
				$alias = @implode(' ', $alias);
				
				$line = "$user: $alias";
				$out[] = $line;
			}
			
			$out = @implode("\n", $out);
			
			file_put_contents("/etc/valiases/$domain", $out);
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Rebuild Courier User Databse
	 * 
	 * @return bool
	 */
	public static function makeUserdb()
	{
		system('makeuserdb', $status);
		
		return ( $status > 0 ) ? false : true;
	}

	/**
	 * Create maildir Directory
	 * 
	 * @param string $dir Directory to create as maildir
	 * 
	 * @return bool
	 */
	public static function makeMailDir( $dir )
	{
		system("maildirmake $dir", $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	/**
	 * Add Courier mail account
	 * 
	 * @param string $email Email account (username) to add
	 * @param string $home Path for home
	 * @param string $mail Maildir directory
	 * @param int $mail_uid System uid for Mail user
	 * @param int $mail_gid System gid for Mail user
	 * 
	 * @return bool
	 */
	public static function addAccount( $email, $home, $mail, $mail_uid = NULL, $mail_gid = NULL )
	{
		if( is_null($mail_uid) || is_null($mail_gid) )
		{
			$userdata = Machine::user('mail');
			
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
		Machine::chown($home, 'mail', 'mail', true);

		system("userdb $email set uid=$mail_uid gid=$mail_gid home=$home mail=$mail", $status);
		
		if( $status > 0 )
		{
			//Whoops. We've got an issue. Backup slowly.
			self::removeAccount($email);
			
			return false;
		}
		else
		{
			self::makeUserdb();
			
			return true;
		}
	}
	
	/**
	 * Remove Courier mail account
	 * 
	 * @param string $username Email user to remove from Courier Database
	 * 
	 * @return bool
	 */
	public static function removeAccount( $username )
	{
		if( !$userdata = self::account($username) )
		{
			return false;
		}
		
		Machine::rm($userdata['home'], true);
		Machine::rm($userdata['home']);
		
		system("userdb $username del", $status);
		
		self::makeUserdb();
		
		return ( $status > 0 ) ? false : true;
	}
	
	public static function password($account, $password)
	{
		if( !self::account($account) )
		{
			return false;
		}
		
		system('echo "' . $password . '" | userdbpw -md5 | userdb ' . $account . ' set systempw', $status);
		
		if( $status > 0 )
		{
			return false;
		}
		else
		{
			self::makeUserdb();
		}
		
		return true;
	}
	
	public static function addForwarder($from, $to)
	{
		$tmp = explode('@', $from);
		$username = $tmp[0];
		$domain = $tmp[1];
		
		IO::write('Testing if a domain', DEBUG);
		
		if( Machine::is_domain($domain) )
		{
			$aliases = self::aliases($domain);
			IO::write('Gather list of aliases', DEBUG);
			IO::write($aliases, DEBUG);
			
			if( !array_key_exists($username, $aliases[$domain]) )
			{
				$useraliases = array_reverse($aliases[$domain], true);
				$useraliases[$username] = array();
				$useraliases = array_reverse($useraliases, true);
				
				$aliases[$domain] = $useraliases;
			}
			
			if( $username == '*' )
			{
				$aliases[$domain][$username][0] = $to;
				
				if( !self::setAlias($domain, $aliases[$domain]) )
				{
					return false;
				}
			}
			else
			{
				if( !array_unshift($aliases[$domain][$username], $to) > 1 )
				{
					return false;
				}
				else
				{
					if( !self::setAlias($domain, $aliases[$domain]) )
					{
						return false;
					}
				}
			}
			
			return true;
		}
		else
		{
			return false;
		}
	}
}
?>
