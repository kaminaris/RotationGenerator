<?php

namespace Generator\Variable;

class ActiveDotHandler extends Handler
{
	public $handledPrefixes = ['active_dot'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spell = $this->profile->SpellName($variableParts[1]);
		$output[] = "activeDot[{$spell}]";
	}
}