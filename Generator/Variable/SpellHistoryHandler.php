<?php

namespace Generator\Variable;

class SpellHistoryHandler extends Handler
{
	public $handledPrefixes = ['prev_gcd'];

	public function handle($lexer, $variableParts, &$output)
	{
		$history = is_numeric($variableParts[1]) ? intval($variableParts[1]) : 1;
		$spellSimcName = is_numeric($variableParts[1]) ? $variableParts[2] : $variableParts[1];
		$spell = $this->profile->SpellName($spellSimcName);
		$this->profile->spellList[$spellSimcName] = true;

		$value = "spellHistory[{$history}] == {$spell}";

		$output[] = $value;
	}
}