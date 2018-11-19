<?php

namespace Generator\Variable;

use Generator\Helper;

class TotemHandler extends Handler
{
	public $handledPrefixes = ['consecration'];

	public function handle($lexer, $variableParts, &$output)
	{
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}