<?php

namespace Generator\Variable;

class EssenceHandler extends Handler
{
	public $handledPrefixes = ['essence'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spell = $this->profile->SpellName($variableParts[1]);
		switch ($variableParts[2]) {
			case 'major':
				$output[] = "MaxDps.AzeriteEssences.major == {$spell}";
				break;
			case 'minor':
				$output[] = "MaxDps.AzeriteEssences.minor[{$spell}]";
				break;
			default:
				throw new \Exception('Unrecognized essence part:' . implode(".",$variableParts));
		}

	}
}