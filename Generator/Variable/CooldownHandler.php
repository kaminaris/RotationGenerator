<?php

namespace Generator\Variable;

class CooldownHandler extends Handler
{
	public $handledPrefixes = [
		'cooldown', 'duration', 'charges', 'charges_fractional', 'full_recharge_time', 'cost'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		// shortcut handler
		switch ($variableParts[0]) {
			case 'duration_guess':
			case 'duration':
			case 'full_recharge_time':
			case 'cost': $variableParts = ['cooldown', $this->action->spellName, $variableParts[0]]; break;

			case 'charges_fractional':
			case 'charges': $variableParts = ['cooldown', $this->action->spellName, 'charges']; break;
		}

		$this->action->actionList->resourceUsage->cooldown = true;

		$spell = $this->profile->SpellName($variableParts[1]);

		$prefix = $variableParts[0];
		$suffix = $variableParts[2];

		$value = null;
		switch($suffix) {
			case 'duration_guess':
				$suffix = 'duration';
			case 'up':
			case 'ready':
			case 'cost':
			case 'duration': $value = "{$prefix}[{$spell}].{$suffix}"; break;
			case 'full_recharge_time': $value = "{$prefix}[{$spell}].fullRecharge"; break;
			case 'charges':
			case 'charges_fractional':
			case 'stack': $value = "{$prefix}[{$spell}].charges"; break;
			case 'remains': $value = "{$prefix}[{$spell}].remains"; break;
			default:
				throw new \Exception(
					'Unrecognized cooldown suffix type: ' . $suffix . ' expression: ' . implode('.', $variableParts)
				);
				break;
		}


		$output[] = $value;
	}
}