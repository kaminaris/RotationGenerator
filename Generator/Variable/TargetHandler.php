<?php

namespace Generator\Variable;

use Generator\Helper;

class TargetHandler extends Handler {
	public $handledPrefixes = ['target'];

	public function handle($lexer, $variableParts, &$output) {
		switch ($variableParts[1]) {
			case 'is_add': //ignore
				break;
			case 'time_to_pct_20':
				$output[] = 'timeTo20';
				break;
			case 'health':
				$output[] = 'targetHp';
				break;
			case '1':
				switch ($variableParts[2]) {
					case 'time_to_die':
						$this->action->actionList->resourceUsage->timeToDie = true;
						$output[] = 'timeToDie';
						break;
				}
				break;
			case 'time_to_die':
				$this->action->actionList->resourceUsage->timeToDie = true;
				$output[] = 'timeToDie';
				break;
			case 'time_to_pct_35':
				$output[] = implode('.', $variableParts);
				break;
			case '':
				$output[] = $variableParts[0];
				break;
			case 'debuff':
				$output[] = implode('.', $variableParts);
				break;
			case 'cooldown':
				break;
			case 'is_boss':
				break;
			case 'distance':
				break;
			case 'level':
				break;
			default:
				throw new \Exception(
					'Unrecognized target variable: ' . $variableParts[1] . ' expression: ' . implode(
						'.', $variableParts
					)
				);
		}
	}
}