<?php

namespace Generator\Variable;

use Generator\Helper;

class ResourceHandler extends Handler
{
	public $handledPrefixes = [
		'runic_power', 'chi', 'focus', 'combo_points', 'soul_shard', 'rune', 'energy', 'rage', 'holy_power'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		$this->action->actionList->resourceUsage->resources[$variableParts[0]] = true;
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}