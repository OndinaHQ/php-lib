<?php
/**
 * Accounts class
 *
 * @package             Framework
 */

/**
 * User Account management class.
 *
 * @author              Marco Ceppi <marco.ceppi@seacrow.org>
 * @since               November 8, 2010
 * @package             Framework
 * @subpackage          Users
 */
class Accounts
{
	private $data = array();
	
	function Accounts()
	{
		$this->userdomains;
	}
	
	function __get( $name )
	{
		if( !array_key_exists($name, $this->data) )
		{
			if( method_exists(__CLASS__, "_get_$name") )
			{
				$method = "_get_$name";
				
				$this->data[$name] = $this->$$method();
			}
			else
			{
				return false;
			}
		}

		return $this->data[$name];
	}
	
	function __set( $name, $val )
	{
		$this->data[$name] = $val;
	}
	
	private function _get_userdomains()
	{
		$userdomains = array();
		
		if( !$data_array = file('/etc/userdomains') )
		{
			return false;
		}
		
		foreach( $data_array as $line )
		{
			list($domain, $user) = explode(" ", $line);
			
			$userdomains[$user][$domain] = NULL;
		}
		
		return $userdomains;
	}
	
	private function _set_userdomains( $val )
	{
		
	}
	
	function addUserdomain( $user, $domain )
	{

	}
}
?>
