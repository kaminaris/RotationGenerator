<?php

namespace Generator\Variable;

class RaidEventHandler extends Handler
{
	public $handledPrefixes = ['raid_event'];

	public function handle($lexer, $variableParts, &$output)
	{
		#raid_event.adds.exists
		$value = null;
		switch ($variableParts[1]) {
			case 'adds':
				if ($variableParts[2] == 'exists') {
					if (isset($output[0])){
						if ($output[0] == "not"){
							unset($output[0]);
							$output[] = "targets <= 1";
							break;
						}
					}
					$output[] = "targets > 1";
					break;
				}
				if ($variableParts[2] == 'in') {
					$output[] = implode(".",$variableParts);
					break;
				}
			default:
			    $output[] = implode(".",$variableParts);
				#throw new \Exception('Unrecognized essence part:' . implode(".",$variableParts));
		}
	}
}