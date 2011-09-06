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
	public static function digest()
	{
		return base64_encode(API_USERNAME . ':' . API_PASSWORD);
	}
}
