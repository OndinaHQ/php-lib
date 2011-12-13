<?php
/**
 * Couier class
 *
 * @package             Framework
 */

require_once('Machine.php');
require_once('API.php');

define('MAIL_DIR',   '/var/mail/virtual/');
define('MAIL_ALIAS', '/etc/valiases/');
define('MAIL_UID',  8);
define('MAIL_USER', 'mail');
define('MAIL_GROUP', 'mail');

/**
 * Courier management class.
 *
 * @author              Marco Ceppi <marco.ceppi@seacrow.org>
 * @since               November 9, 2010
 * @package             Framework
 * @subpackage          Courier
 */
class Courier
{
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
			
			$tmp['size'] = trim(shell_exec("du -sh " . $tmp['mail'] . " | awk '{ print $1 }'"));
			
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
					$alias = explode(', ', $forwarder);
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
	
	public static function alias( $domain, $data )
	{
		if( Machine::is_domain($domain) )
		{
			$out = array();
			
			if( is_array($data) )
			{
				foreach( $data as $user => $alias )
				{
					if( is_array($alias) )
					{
						$alias = @implode(', ', $alias);
					}
					
					$out[] = "$user: $alias";
				}
				
				$out = @implode("\n", $out);
			}

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
	public static function userdb()
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
	public static function maildir( $dir )
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
	public static function add_account( $email, $home, $mail, $mail_uid = MAIL_UID, $mail_gid = MAIL_UID )
	{
		if( is_null($mail_uid) || is_null($mail_gid) )
		{
			$userdata = Machine::user(MAIL_USER);
			
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
		
		self::maildir($mail);
		Machine::chown($home, 'mail', 'mail', true);

		system("userdb $email set uid=$mail_uid gid=$mail_gid home=$home mail=$mail", $status);
		
		if( $status > 0 )
		{
			//Whoops. We've got an issue. Backup slowly.
			self::del_account($email);
			
			return false;
		}
		else
		{
			self::userdb();
			
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
	public static function del_account( $username )
	{
		if( !$userdata = self::account($username) )
		{
			return false;
		}
		
		Machine::rm($userdata['home'], true);
		Machine::rm($userdata['home']);
		
		system("userdb $username del", $status);
		
		self::userdb();
		
		return ( $status > 0 ) ? false : true;
	}
	
	/**
	 * Remove Courier mail forwarder
	 * 
	 * @param string $address Email user to remove from Courier Database
	 * 
	 * @return bool
	 */
	public static function del_forwarder( $address )
	{
		list($username, $domain) = explode('@', $address);
		
		$aliases = self::aliases($domain);
		
		if( array_key_exists($username, $aliases[$domain]) )
		{
			unset($aliases[$domain][$username]);
			
			if( !self::alias($domain, $aliases[$domain]) )
			{
				return false;
			}
		}
		
		return true;
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
			self::userdb();
		}
		
		return true;
	}
	
	public static function forward($from, $to, $overwrite = false)
	{
		list($username, $domain) = explode('@', $from);
		
		if( Machine::is_domain($domain) )
		{
			$aliases = self::aliases($domain);
			
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
				
				if( !self::alias($domain, $aliases[$domain]) )
				{
					return false;
				}
			}
			else
			{
				if( $overwrite )
				{
					$to = explode(',', str_replace(' ', '', $to));
					
					$aliases[$domain][$username] = $to;
					if( !self::alias($domain, $aliases[$domain]) )
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
						if( !self::alias($domain, $aliases[$domain]) )
						{
							return false;
						}
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
	
	public static function setup($domain)
	{
		if( !is_dir(MAIL_DIR . $domain) )
		{
			if( !mkdir(MAIL_DIR . $domain) )
			{
				return false;
			}
			
			Machine::chown(MAIL_DIR . $domain, MAIL_USER, MAIL_GROUP);
		}
		
		if( !is_file(MAIL_ALIAS . $domain) )
		{
			if( !file_put_contents(MAIL_ALIAS . $domain, '*: :fail: No user at this address.') )
			{
				return false;
			}
		}
		
		return true;
	}
}

// Move Courier over
class Courier_Server extends Courier
{
	
}

class Courier_Client extends API
{
	public static function accounts()
	{
		return json_decode(API::get(static::$server, 'email/account'), true);
	}
	
	public static function aliases( $domain )
	{
		return json_decode(API::get(static::$server, 'email/alias/' . $domain), true);
	}
	
	public static function password( $account, $password )
	{
		return API::set(static::$server, 'email/account/password', array('account' => $account, 'password' => $password));
	}
	
	public static function add_account( $email )
	{
		return API::set(static::$server, 'email/account/create', array('email' => $email));
	}
	
	public static function forward( $email, $to )
	{
		return API::set(static::$server, 'email/alias/' . $email, array('to' => $to));
	}
	
	public static function del_account( $email )
	{
		return API::del(static::$server, 'email/account/' . $email, array('to' => $to));
	}
	
	public static function del_alias( $email )
	{
		return API::del(static::$server, 'email/alias/' . $email, array('to' => $to));
	}
}
