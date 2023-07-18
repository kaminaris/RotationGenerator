<?php

namespace Generator\Variable;

class CooldownHandler extends Handler
{
	public $handledPrefixes = [
		'cooldown', 'duration', 'charges', 'charges_fractional', 'full_recharge_time', 'cost', 'ground_aoe',
		'max_charges', 'recharge_time'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		// shortcut handler
		switch ($variableParts[0]) {
			case 'duration_guess':
			case 'duration':
			case 'full_recharge_time':
			case 'max_charges':
			case 'recharge_time':
			case 'cost': $variableParts = ['cooldown', $this->action->spellNameCanonical, $variableParts[0]]; break;

			case 'charges_fractional': $variableParts = ['cooldown', $this->action->spellNameCanonical, 'charges_fractional']; break;
			case 'charges': $variableParts = ['cooldown', $this->action->spellNameCanonical, 'charges']; break;
			//case 'cooldown': $variableParts = ['cooldown', $this->action->spellNameCanonical, 'remains']; break;
		}
		switch ($variableParts[1]) {
			case 'pause_action': $variableParts = ['cooldown', $this->action->spellNameCanonical, 'pause_action']; break;
		}

		$this->action->actionList->resourceUsage->cooldown = true;

		$spell = $this->profile->SpellName($variableParts[1]);
		$spell = str_replace(' ', '', $spell);

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
			case 'recharge_time': $value = "{$prefix}[{$spell}].partialRecharge"; break;
			case 'charges':
			case 'charges_fractional': $value = "{$prefix}[{$spell}].charges"; break;
			case 'stack': $value = "{$prefix}[{$spell}].charges"; break;
			case 'max_charges': $value = "{$prefix}[{$spell}].maxCharges"; break;
			case '':
			case 'remains_guess':
			case 'remains_expected':
				$value = "{$prefix}[{$spell}].remains"; break;
			case 'remains': $value = "{$prefix}[{$spell}].remains"; break;
			case 'pause_action':
			    $info[] = implode('.', $variableParts);
			    if ($variableParts[2] === 'pause_action'){
			    	$value = "{$prefix}[{$spell}].remains>=1";
			    } else {
			    	$value[] = implode('.', $variableParts);
			    }
			    break;
			default:
				throw new \Exception(
					'Unrecognized cooldown suffix type: ' . $suffix . ' expression: ' . implode('.', $variableParts).
					"\n Line: " . $this->action->rawLine
				);
				break;
		}


		$output[] = $value;
	}
}