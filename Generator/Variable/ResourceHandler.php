<?php

namespace Generator\Variable;

use Generator\Helper;

class ResourceHandler extends Handler
{
	public $handledPrefixes = [
		'runic_power', 'chi', 'focus', 'cast_regen', 'combo_points', 'soul_shard', 'rune', 'energy', 'rage',
		'holy_power', 'pain', 'maelstrom', 'astral_power'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		if ($variableParts[0] == 'soul_shard') {
			$variableParts[0] = 'soul_shards';
		} elseif ($variableParts[0] == 'astral_power') {
			$variableParts[0] = 'lunar_power';
		}

		$this->action->actionList->resourceUsage->addResource($variableParts[0]);
		$output[] = Helper::camelCase(implode('_', $variableParts));
	}
}