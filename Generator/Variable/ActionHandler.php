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
			default:
				throw new \Exception(
					'Unrecognized action part: ' . $variableParts[2] . ' expression ' . implode('.', $variableParts)
				);
		}

	}
}