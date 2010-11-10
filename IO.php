<?php
/**
 * Intput Output class
 *
 * @package             Framework
 */

define('SUCCESSFUL',             0);
define('COMMAND_FAILED',         1);
define('TOO_FEW_OPTIONS',        2);
define('NO_MODE_SPECIFIED',      3);
define('INSUFICENT_PRIVILEDGES', 4);
define('INVALID_SELECTION',      5);

// Write states
define('GENERAL', 0);
define('DEBUG',   1);

/**
 * Manage data going out, and coming in.
 *
 * @author              Marco Ceppi <marco.ceppi@seacrow.org>
 * @since               November 8, 2010
 * @package             Framework
 * @subpackage          System
 */
class IO
{
	public static function error($code, $msg = NULL)
	{
		if( !is_null($msg) )
		{
			fwrite(STDERR, "ERROR: $msg" . PHP_EOL);
		}
		
		exit($code);
	}

	public static function write($msg, $lvl = GENERAL)
	{
		if( $lvl >= OUTPUT_LEVEL )
		{
			echo $msg;
		}
	}
}
?>
