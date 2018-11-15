<?php

namespace Generator\Variable;

use Generator\Helper;

class WiBombHandler extends Handler
{
	public static $handledPrefixes = ['next_wi_bomb'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spellPrefix = $this->profile->spellPrefix;
		$bombName = Helper::properCase($variableParts[1]) . 'Bomb';
		$output[] = "nextWiBomb == {$spellPrefix}.{$bombName}";
	}
}

