<?php

namespace Generator\Variable;

use Generator\Helper;

class ResourceHandler extends Handler
{
	public $handledPrefixes = [
		'runic_power', 'chi', 'focus', 'cast_regen', 'combo_points', 'soul_shard', 'rune', 'energy', 'rage', 'holy_power', 'pain'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		if ($variableParts[0] == 'soul_shard') {
			$variableParts[0] = 'soul_shards';
		}

		$this->action->actionList->resourceUsage->resources[$variableParts[0]] = true;
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}