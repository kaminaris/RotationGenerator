<?php

namespace Generator\Variable;

class AzeriteHandler extends Handler
{
	public $handledPrefixes = ['azerite'];

	public function handle($lexer, $variableParts, &$output)
	{
		$this->action->actionList->resourceUsage->azerite = true;

		$spell = $this->profile->SpellName($variableParts[1]);

		$suffix = $variableParts[2];

		$value = null;
		switch($suffix) {
			case 'rank':
				$value = "azerite[{$spell}]";
				break;
			case 'enabled':
				$value = "azerite[{$spell}] > 0";
				break;
			default:
				throw new \Exception('Unrecognized azerite suffix type: ' . $suffix);
				break;
		}

		$output[] = $value;
	}
}