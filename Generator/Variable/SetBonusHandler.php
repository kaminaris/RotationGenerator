<?php

namespace Generator\Variable;

class SetBonusHandler extends Handler
{
	public $handledPrefixes = ['set_bonus'];

	public function handle($lexer, $variableParts, &$output)
	{

		$value = null;
		$suffix = $variableParts[1];
		$spell = $this->profile->SpellName($variableParts[1]);
		$tierparts = explode('_', $variableParts[1]);
		if (isset($tierparts[0])){
			preg_match_all('!\d+!', $tierparts[0], $tiernumber);
			$tiernumber = $tiernumber[0][0];
		}
		if (isset($tierparts[1])){
			preg_match_all('!\d+!', $tierparts[1], $tiercount);
			$tiercount = $tiercount[0][0];
		}
		if (isset($tiernumber) and isset($tiercount)){
			#self.tier[tier].count
		    $value = "MaxDps.tier[{$tiernumber}] and MaxDps.tier[{$tiernumber}].count and (MaxDps.tier[{$tiernumber}].count == {$tiercount})";
		}
		if (!isset($value)){
			throw new \Exception(
				'Unrecognized set_bonus suffix: ' . $suffix . ' expression: ' . implode('.', $variableParts)
			);
		}
		$output[] = $value;
	}
}