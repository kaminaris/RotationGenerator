<?php

namespace Generator\Variable;

use Generator\Helper;

class GcdHandler extends Handler
{
	public static $handledPrefixes = ['gcd'];

	public function handle($lexer, $variableParts, &$output)
	{
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}