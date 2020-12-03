<?php

namespace Generator\Variable;

use Generator\Helper;

class PetHandler extends Handler
{
	public $handledPrefixes = ['pet'];

	public function handle($lexer, $variableParts, &$output)
	{
		switch ($variableParts[1]) {
			case 'infernal':
				$output[] = 'petInfernal';
				break;
			case 'xuen_the_white_tiger':
				$output[] = 'petXuen';
				break;
			case 'gargoyle':
				$output[] = 'petGargoyle';
				break;
			case 'turtle':
			case 'main':
			case 'cat':
				if ($variableParts[2] == 'buff') {
					$output[] = 'buff[' . Helper::pascalCase($variableParts[3]) . '].' . $variableParts[4];
				}
				break;
			default:
				throw new \Exception(
					'Unrecognized pet part: ' . $variableParts[1] . ' expression ' . implode('.', $variableParts)
				);
		}
	}
}