<?php

namespace Generator\Variable;

class BlacklistHandler extends Handler
{
	public $handledPrefixes = [
		'min', 'max', 'movement', 'raid_event', 'time', 'sim', 'travel_time', 'trinket', 'expected_combat_length',
		'in_flight', 'incoming_imps', 'self'
	];

	public function handle($lexer, $variableParts, &$output)
	{
		$previousElement = end($output);
		$blacklist = ['*', '/', '+', '-', '>', '<', '<=', '>=', '&', '|'];

		while (in_array($previousElement, $blacklist)) {
			array_pop($output);
			$previousElement = end($output);
		}

		while ($glimpse = $lexer->glimpse()) {
			$nextVal = $glimpse->getValue();

			if (in_array($nextVal, $blacklist)) {
				// skip next
				$lexer->moveNext();
			} else {
				break;
			}
		}

		if (count($output) == 1 && in_array($output[0], ['and', 'or'])) {
			array_pop($output);
		}
	}
}