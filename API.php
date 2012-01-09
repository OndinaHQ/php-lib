<?php
/**
 * API class
 *
 * @package Framework
 */

require_once('/opt/tools/.passwd');

/**
 * API class.
 *
 * @author Marco Ceppi <marco@ceppi.net>
 * @package Framework
 * @subpackage API
 */
class API
{
	static $server;
	
	public static function digest()
	{
		return base64_encode(API_USERNAME . ':' . API_PASSWORD);
	}
	
	// This needs to be a call to the API!
	public static function get( $server, $path )
	{
		$context = stream_context_create(array
		(
			'http' => array
			(
				'method'  => 'GET',
				'header'  => 'Authorization: Basic ' . static::digest()
			)
		));
		
		return @file_get_contents('https://' . $server . ':9001/' . $path, false, $context);
	}
	
	public static function set( $server, $path, $data )
	{
		$context = stream_context_create(array
		(
			'http' => array(
				'method'  => 'POST', 
				'header'  => sprintf("Authorization: Basic %s\r\n", static::digest()). "Content-type: application/json\r\n", 
				'content' => json_encode($data)
			)
		));
		
		return (!@file_get_contents('https://' . $server . ':9001/'. $path, false, $context)) ? false : true;
	}
	
	public static function del( $server, $path )
	{
		return static::delete($server, $path);
	}
	
	public static function delete( $server, $path )
	{
		$context = stream_context_create(array
		( 
			'http' => array
			(
				'method'  => 'DELETE', 
				'header'  => 'Authorization: Basic ' . static::digest()
			)
		));
		
		return (!@file_get_contents('https://' . $server . ':9001/'. $path, false, $context)) ? false : true;
	}
}
