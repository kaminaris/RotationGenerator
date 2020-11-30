<?php

namespace Generator\Variable;

class SoulbindHandler extends Handler
{
	public $handledPrefixes = ['soulbind'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spell = $this->profile->SpellName($variableParts[1]);

		$output[] = 'covenant.soulbindAbilities[' . $spell . ']';

	}
}