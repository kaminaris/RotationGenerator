<?php

namespace Generator\Variable;

use Generator\Helper;

class TargetHandler extends Handler {
	public $handledPrefixes = ['target'];

	public function handle($lexer, $variableParts, &$output) {
		
		if (!isset($variableParts[1])){
			switch ($variableParts[0]) {
				case 'target': $variableParts = ['target', 'targetonly']; break;
			}
		}
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
				$output[] = 'timeTo35';
				break;
			case '':
				$output[] = $variableParts[0];
				break;
			case 'debuff':
				$info[] = implode('.', $variableParts);
				if ($variableParts[2] === 'casting'){
					$output[] = 'select(9,UnitCastingInfo("target")) == false';
				} else {
					$output[] = implode('.', $variableParts);
				}
				break;
			case 'cooldown':
				$info[] = implode('.', $variableParts);
				if ($variableParts[2] === 'pause_action'){
					$output[] = 'timeToDie';
				} else {
					$output[] = implode('.', $variableParts);
				}
				break;
			case 'is_boss':
				break;
			case 'distance':
				break;
			case 'level':
				break;
			case 'targetonly':
				$output[] = implode('.', $variableParts);
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