<?php
/**
 * Meta class
 *
 * @package Framework
 */

require_once('API.php');

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
			if( empty($domain) )
			{
				$meta = static::$path . "/$user/*";
				$metadata = array();
				$meta_files = glob($meta);
				
				foreach( $meta_files as $file )
				{
					$metadata[basename($file)] = json_decode(static::get_raw($file), $assoc);
				}
				
				return $metadata;
			}
			else
			{
				$meta = static::$path . "/$user/$domain";
				
				return json_decode(static::get_raw($meta), $assoc);
			}
		}
	}
	
	protected static function get_raw( $path )
	{
		return @file_get_contents($path);
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
			
			return static::set_raw($meta, $data);
		}
		
		return false;
	}
	
	protected static function set_raw( $path, $data )
	{
		return (!file_put_contents($path, json_encode($data))) ? false : true;
	}
	
	public static function delete( $user, $domain )
	{
		return static::del_raw(static::$path . "/$user/$domain");
	}
	
	public static function del_raw( $path )
	{
		return @unlink($path);
	}
}
