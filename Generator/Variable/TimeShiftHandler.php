<?php

namespace Generator\Variable;

use Generator\Helper;

class TimeShiftHandler extends Handler
{
	public $handledPrefixes = ['execute_time', 'cast_time'];

	public function handle($lexer, $variableParts, &$output)
	{
		$this->action->actionList->resourceUsage->timeShift = true;
		$output[] = "timeShift";
	}
}