<?php

namespace Generator\Variable;

class SpellHandler extends Handler
{
	public $handledPrefixes = ['improved_scorch'];

	public function handle($lexer, $variableParts, &$output)
	{
		$spell = $this->profile->SpellName($variableParts[0]);
        if (isset($variableParts[1])){
            if ($variableParts[1] == 'active'){
                $value = "debuff[{$spell}].up";
                $output[] = $value;
            }
        }

		#$value = "";

		
	}
}