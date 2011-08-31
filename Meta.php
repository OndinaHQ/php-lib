<?php
/**
 * Meta class
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
 * @subpackage Meta
 */
class Meta
{
	public static $path = false;
	
	public static function get( $user, $domain, $assoc = false )
	{
		if( $path )
		{
			$meta = "$path/$user/$domain";
			
			return ( is_file($meta) ) ? json_decode(self::get_raw($meta), $assoc) : false;
		}
	}
	
	public static function save( $user, $domain, $data )
	{
		if( is_array($data) )
		{
			$meta = "$path/$user/$domain";
			
			return (!file_put_contents($meta, json_encode($data))) ? false : true;
		}
		
		return false;
	}		
}
