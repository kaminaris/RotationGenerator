<?php

namespace Generator\Variable;

use Generator\Helper;

class TargetHandler extends Handler
{
	public $handledPrefixes = ['target'];

	public function handle($lexer, $variableParts, &$output)
	{
		switch ($variableParts[1]) {
			case 'health':
				$output[] = 'targetHp'; break;
			case 'time_to_die':
				$output[] = 'timeToDie'; break;
			default:
				throw new \Exception(
					'Unrecognized target variable: ' . $variableParts[1] . ' expression: ' . implode('.', $variableParts)
				);
		}
	}
}