<?php

namespace Generator\Variable;

use Generator\Helper;

class ResourceHandler extends Handler
{
	public $handledPrefixes = [
		'runic_power', 'chi', 'focus', 'combo_points', 'soul_shard', 'rune', 'energy', 'rage'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		switch ($variableParts[0]) {
			case 'runic_power': $variableParts[0] = 'runic'; break;
			case 'combo_points': $variableParts[0] = 'combo'; break;
			case 'soul_shard': $variableParts[0] = 'shard'; break;
		}


		$this->action->actionList->resourceUsage->resources[$variableParts[0]] = true;
var_dump($this->action->actionList->resourceUsage->resources);
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}