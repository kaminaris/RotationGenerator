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