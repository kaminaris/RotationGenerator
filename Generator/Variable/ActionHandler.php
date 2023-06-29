<?php

namespace Generator\Variable;

class ActionHandler extends Handler
{
	public $handledPrefixes = ['action'];

	public function handle($lexer, $variableParts, &$output)
	{
		switch ($variableParts[2]) {
			case 'cast_time':
			case 'execute_time':
				$this->action->actionList->resourceUsage->timeShift = true;
				$output[] = 'timeShift';
				break;
			case 'gcd':
				$this->action->actionList->resourceUsage->gcd = true;
				$output[] = 'gcd';
				break;
			case 'full_recharge_time':
				$spellName = $this->profile->SpellName($variableParts[1]);
				$output[] = "cooldown[{$spellName}].fullRecharge";
				break;
			case 'charges':
			case 'executing':
			case 'damage':
			case 'in_flight_remains':
			case 'travel_time':
			case 'cost':
			case 'execute_remains':
				$spellName = $this->profile->SpellName($variableParts[1]);
				$output[] = "cooldown[{$spellName}].{$variableParts[2]}";
				break;
			case 'in_flight_to_target':
			case 'in_flight':
				$output[] = 'inFlight';
				break;
			case 'energize_amount':
				break;
			case 'ready':
				$spellName = $this->profile->SpellName($variableParts[1]);
				$output[] = "cooldown[{$spellName}].{$variableParts[2]}";
				break;
			case 'cast_regen':
				break;
			case 'channeling':
				break;
			case 'charges_fractional':
				break;
			case 'full_reduction':
				break;
			case 'tick_reduction':
				break;
			case 'cooldown':
				$spellName = $this->profile->SpellName($variableParts[1]);
				$output[] = "cooldown[{$spellName}].{$variableParts[2]}";
				break;
			case 'max_charges':
				$spellName = $this->profile->SpellName($variableParts[1]);
				$output[] = "cooldown[{$spellName}].maxCharges"; 
				break;
			case 'cooldown_react':
				break;
			case 'last_used':
				break;
			case 'usable':
				$spellName = $this->profile->SpellName($variableParts[1]);
				$output[] = "MaxDps:FindSpell({$spellName})"; 
				break;
			case 'crit_pct_current':
				break;
			default:
				throw new \Exception(
					'Unrecognized action part: ' . $variableParts[2] . ' expression ' . implode('.', $variableParts)
				);
		}

	}
}