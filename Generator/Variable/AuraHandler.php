<?php

namespace Generator\Variable;

class AuraHandler extends Handler
{
	public $handledPrefixes = ['dot', 'buff', 'debuff', 'ticking', 'refreshable', 'remains'];

	public function handle($lexer, $variableParts, &$output)
	{
		switch ($variableParts[0]) {
			case 'ticking':
			case 'refreshable':
			case 'remains': $variableParts = ['debuff', $this->action->spellName, $variableParts[0]]; break;
		}

		$spell = $this->profile->SpellName($variableParts[1]);

		$prefix = $variableParts[0];
		if ($prefix == 'dot') {
			$prefix = 'debuff';
		}

		if ($prefix == 'buff') {
			$this->action->actionList->resourceUsage->buff = true;
		} else {
			$this->action->actionList->resourceUsage->debuff = true;
		}

		$suffix = $variableParts[2];

		$value = null;
		switch($suffix) {
			case 'ticking':
			case 'up':
				$value = "{$prefix}[{$spell}].up";
				break;
			case 'down':
				$value = "not {$prefix}[{$spell}].up";
				break;
			case 'charges':
			case 'stack':
			case 'react':
				$value = "{$prefix}[{$spell}].count";
				break;
			case 'duration':
				$value = "{$prefix}[{$spell}].duration";
				break;
			case 'remains':
				$value = "{$prefix}[{$spell}].remains";
				break;
			case 'refreshable':
				$value = "{$prefix}[{$spell}].refreshable";
				break;
			case 'pmultiplier':
				$previousElement = end($output);
				$glimpse = $lexer->glimpse();

				if ($glimpse) {
					$nextVal = $glimpse->getValue();
					$blacklist = ['*', '/', '+', '-', '>', '<', '<=', '>='];

					if (in_array($previousElement, $blacklist)) {
						array_pop($output);
						array_pop($output);
					}

					if (in_array($nextVal, $blacklist)) {
						// skip next
						$lexer->moveNext();
						$lexer->moveNext();
					}
				}

				return;
				break;
			default:
				throw new \Exception(
					'Unrecognized spell/aura suffix type: ' . $suffix . ' expression: ' . implode('.', $variableParts)
				);
				break;
		}


		$output[] = $value;
	}
}