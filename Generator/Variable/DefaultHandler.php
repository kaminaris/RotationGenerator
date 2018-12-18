<?php

namespace Generator\Variable;

use Generator\Helper;

class DefaultHandler extends Handler
{
	public $handledPrefixes = ['cooldown_react', 'contagion', 'spell_haste', 'time_to_shard', 'firestarter'];

	public function handle($lexer, $variableParts, &$output)
	{
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}