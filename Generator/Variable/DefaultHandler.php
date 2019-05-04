<?php

namespace Generator\Variable;

use Generator\Helper;

class DefaultHandler extends Handler
{
	public $handledPrefixes = [
		'cooldown_react', 'contagion', 'spell_haste', 'time_to_shard', 'firestarter', 'soul_fragments', 'ca_execute',
		'imps_spawned_during', 'time_to_imps', 'floor', 'ceil', 'feral_spirit', 'ap_check', 'solar_wrath', 'rtb_buffs',
		'stealthed', 'cp_max_spend', 'health', 'incoming_damage_1500ms', 'incoming_damage_1999ms', 'stagger',
		'desired_targets'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		if (in_array($variableParts[0], ['floor', 'ceil'])) {
			$output[] = 'math.' . $variableParts[0];
		} else {
			if ($variableParts[0] == 'spell_haste') {
				$this->action->actionList->resourceUsage->spellHaste = true;
			}
			$output[] = Helper::camelCase(implode('_', $variableParts));
		}
	}
}