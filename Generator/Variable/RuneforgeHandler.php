<?php

namespace Generator\Variable;

class RuneforgeHandler extends Handler
{
	public $handledPrefixes = ['runeforge'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spell = $this->profile->SpellName($variableParts[1]);


		$output[] = 'runeforge[' . $spell . ']';

	}
}