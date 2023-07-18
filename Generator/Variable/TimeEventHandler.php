<?php

namespace Generator\Variable;

class TimeEventHandler extends Handler
{
	public $handledPrefixes = ['time'];

	public function handle($lexer, $variableParts, &$output)
	{
		$value = null;
        if (isset($variableParts[1])) {
            throw new \Exception('Unrecognized essence part:' . implode(".",$variableParts));
        }
        $output[] = "GetTime()";
	}
}