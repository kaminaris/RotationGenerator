<?php

namespace Generator;

class Helper
{
	public static function pascalCase($s)
	{
		return str_replace('_', '', ucwords($s, '_'));
	}

	public static function properCase($s)
	{
		return str_replace('_', '', ucwords($s, '_'));
	}

	public static function camelCase($s)
	{
		return lcfirst(str_replace('_', '', ucwords($s, '_')));
	}

	public static function properCaseWithSpaces($s)
	{
		return str_replace('_', ' ', ucwords($s, '_'));
	}

	public static function splitString($s)
	{
		return preg_split("/\r\n|\n|\r/", $s);
	}
}