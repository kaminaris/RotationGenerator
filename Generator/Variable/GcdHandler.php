<?php

namespace Generator\Variable;

class GcdHandler extends Handler
{
	public $handledPrefixes = ['gcd'];

	public function handle($lexer, $variableParts, &$output)
	{
		if (count($variableParts) == 1) {
			$variableParts[] = '';
		}

		switch ($variableParts[1]) {
			case '':
			case 'max':
				$this->action->actionList->resourceUsage->gcd = true;
				$output[] = 'gcd';
				break;
			case 'remains':
				$this->action->actionList->resourceUsage->gcdRemains = true;
				$output[] = 'gcdRemains';
				break;
			default:
				throw new \Exception(
					'Unrecognized gcd part: ' . $variableParts[1] . ' expression ' . implode('.', $variableParts)
				);
		}

	}
}