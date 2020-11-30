<?php

namespace Generator\Variable;

class CovenantHandler extends Handler
{
	public $handledPrefixes = ['covenant'];

	public function handle($lexer, $variableParts, &$output)
	{
		$covenantId = 0;
		switch ($variableParts[1]) {
			case 'kyrian': $covenantId = 'Enum.CovenantType.Kyrian'; break;
			case 'venthyr': $covenantId = 'Enum.CovenantType.Venthyr'; break;
			case 'night_fae': $covenantId = 'Enum.CovenantType.NightFae'; break;
			case 'necrolord': $covenantId = 'Enum.CovenantType.Necrolord'; break;
		}

		$output[] = 'covenant.covenantId == ' . $covenantId;

	}
}