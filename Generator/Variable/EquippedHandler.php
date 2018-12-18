<?php

namespace Generator\Variable;

use Generator\Helper;

class EquippedHandler extends Handler
{
	public $handledPrefixes = ['equipped'];

	public function handle($lexer, $variableParts, &$output)
	{
		$item = $variableParts[1];
		$itemName = is_numeric($item) ? $item : Helper::pascalCase($item);

		$output[] = "IsEquippedItem({$itemName})";
	}
}