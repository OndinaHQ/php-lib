<?php
/**
 * Meta class
 *
 * @package Framework
 */



/**
 * Meta
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
		if( static::$path )
		{
			$meta = static::$path . "/$user/$domain";
			
			return ( is_file($meta) ) ? json_decode(self::get_raw($meta), $assoc) : false;
		}
	}
	
	protected static function get_raw( $path )
	{
		return file_get_contents($path);
	}
	
	public static function save( $user, $domain, $data )
	{
		if( !is_dir(static::$path) )
		{
			mkdir(static::$path);
		}
		
		if( !is_dir(static::$path . "/$user") )
		{
			mkdir(static::$path . "/$user");
		}
		
		if( is_array($data) )
		{
			$meta = static::$path . "/$user/$domain";
			
			return (!file_put_contents($meta, json_encode($data))) ? false : true;
		}
		
		return false;
	}		
}
