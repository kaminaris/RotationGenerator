<?php

namespace Generator\Variable;

class TalentHandler extends Handler
{
	public $handledPrefixes = ['talent'];

	public function handle($lexer, $variableParts, &$output)
	{
		$this->action->actionList->resourceUsage->talents = true;

		$talentName = $this->profile->SpellName($variableParts[1]);
		$suffix = $variableParts[2];
		if (!$suffix || $suffix === '') {
			$suffix = 'enabled';
		}

		if ($suffix == 'enabled') {
			$value = 'talents[' . $talentName . ']';
		} elseif ($suffix == 'disabled') {
			$value = 'not talents[' . $talentName . ']';
		} else {
			throw new \Exception('Unrecognized talent switch type: ' . $suffix . ' Line: ' . json_encode($variableParts));
		}

		$previousElement = end($output);
		$glimpse = $lexer->glimpse();
		if ($glimpse) {
			$nextVal = $glimpse->getValue();
			if (
				in_array($previousElement, ['*', '/', '+', '-']) ||
				in_array($nextVal, ['*', '/', '+', '-'])
			) {
				$value = "({$value} and 1 or 0)";
			}
		}

		$output[] = $value;
	}
}