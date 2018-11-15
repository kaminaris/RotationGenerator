<?php

namespace Generator\Variable;

use Generator\Helper;

class ResourceHandler extends Handler
{
	public static $handledPrefixes = ['runic_power', 'chi', 'focus', 'combo_points', 'soul_shard', 'rune', 'energy'];

	public function handle($lexer, $variableParts, &$output)
	{
		switch ($variableParts[0]) {
			case 'runic_power': $variableParts[0] = 'runic'; break;
			case 'combo_points': $variableParts[0] = 'combo'; break;
			case 'soul_shard': $variableParts[0] = 'shard'; break;
		}

		$this->action->actionList->resourceUsage->{$variableParts[0]} = true;

		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}