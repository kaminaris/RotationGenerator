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
}