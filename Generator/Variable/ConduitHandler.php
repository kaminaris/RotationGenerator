<?php

namespace Generator\Variable;

class ConduitHandler extends Handler
{
	public $handledPrefixes = ['conduit'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spell = $this->profile->SpellName($variableParts[1]);

		$output[] = 'conduit[' . $spell . ']';

	}
}