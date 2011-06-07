<?php
/**
 * CLI class
 *
 * @package             Framework
 */

/**
 * Command Line Interface (CLI) utility class.
 *
 * @author              Patrick Fisher <patrick@pwfisher.com>
 * @since               August 21, 2009
 * @package             Framework
 * @subpackage          Env
 */
class CLI
{
	public static $args;

	/**
	 * PARSE ARGUMENTS
	 * 
	 * This command line option parser supports any combination of three types
	 * of options (switches, flags and arguments) and returns a simple array.
	 *
	 * @param   array $argv
	 * @usage   $args = CommandLine::parseArgs($_SERVER['argv']);
	 * @return  array
	 */
	public static function parseArguments($argv = NULL)
	{
		if( is_null($argv) )
		{
			$argv = $_SERVER['argv'];
		}
		
		array_shift($argv);
		$out = array();

		foreach ($argv as $arg)
		{
			if (substr($arg,0,2) == '--') // --foo --bar=baz
			{
				$eqPos                  = strpos($arg,'=');

				if ($eqPos === false) // --foo
				{
					$key                = substr($arg,2);
					$value              = isset($out[$key]) ? $out[$key] : true;
					$out[$key]          = $value;
				}
				else // --bar=baz
				{
					$key                = substr($arg,2,$eqPos-2);
					$value              = substr($arg,$eqPos+1);
					$out[$key]          = $value;
				}
			}
			else if (substr($arg,0,1) == '-') // -k=value -abc
			{
				if (substr($arg,2,1) == '=') // -k=value
				{
					$key                = substr($arg,1,1);
					$value              = substr($arg,3);
					$out[$key]          = $value;
				}
				else // -abc
				{
					$chars              = str_split(substr($arg,1));
					foreach ($chars as $char)
					{
						$key            = $char;
						$value          = isset($out[$key]) ? $out[$key] : true;
						$out[$key]      = $value;
					}
				}
			}
			else // plain-arg
			{
				$value                  = $arg;
				$out[]                  = $value;
			}
		}
		self::$args                     = $out;
		return $out;
	}

	/**
	 * GET BOOLEAN
	 */
	public static function getBoolean($key, $default = false)
	{
		if (!isset(self::$args[$key]))
		{
			return $default;
		}
		
		$value = self::$args[$key];
		
		if (is_bool($value))
		{
			return $value;
		}
		
		if (is_int($value))
		{
			return (bool)$value;
		}
		
		if (is_string($value))
		{
			$map = array
			(
				'y'                     => true,
				'n'                     => false,
				'yes'                   => true,
				'no'                    => false,
				'true'                  => true,
				'false'                 => false,
				'1'                     => true,
				'0'                     => false,
				'on'                    => true,
				'off'                   => false,
			);
			if (isset($map[strtolower($value)]))
			{
				return $map[$value];
			}
			else
			{
				return $value;
			}
		}
		return $default;
	}
}
