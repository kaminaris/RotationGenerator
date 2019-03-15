<?php

namespace Generator\Variable;

use Generator\Helper;

class DefaultHandler extends Handler
{
	public $handledPrefixes = [
		'cooldown_react', 'contagion', 'spell_haste', 'time_to_shard', 'firestarter', 'soul_fragments', 'ca_execute',
		'imps_spawned_during', 'time_to_imps', 'floor'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}