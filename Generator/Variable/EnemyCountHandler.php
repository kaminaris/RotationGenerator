<?php

namespace Generator\Variable;

class EnemyCountHandler extends Handler
{
	public $handledPrefixes = ['spell_targets', 'active_enemies'];

	public function handle($lexer, $variableParts, &$output)
	{
		$this->action->actionList->resourceUsage->targets = true;

		$output[] = 'targets';
	}
}