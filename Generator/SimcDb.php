<?php

namespace Generator;

class SimcDb
{
	const EXEC_PATH = __DIR__ . '/../simc/simc.exe';
	const EXEC_PATH_OUT = __DIR__ . '/../simc/r.xml';

	public function findByName($name)
	{
		$name = str_replace(' ', '_', $name);
		$o = shell_exec(self::EXEC_PATH . ' spell_query="spell.name=' . $name. '" spell_query_xml_output_file=r.xml');
		file_put_contents('x.txt', $o);
		usleep(10);

		$simpleR = simplexml_load_file(self::EXEC_PATH_OUT);
		$array = json_decode(json_encode($simpleR), true)['spell'];

		$output = [];
		foreach ($array as $spell) {
			$attrs = $spell['@attributes'];
			$spellId = (int)$attrs['id'];
			$s = [
				'id' => $spellId,
				'name' => $attrs['name'],
				'cooldown' => (int)$attrs['cooldown'] ?? null,
				'passive' => (bool)$attrs['passive'] ?? false,
				'range' => (int)$attrs['range'] ?? null,
				'class' => $spell['class']['@attributes']['name'],
				'spec' => $spell['spec']['@attributes']['name'] ?? null,
			];
			$output[$spellId] = $s;
		}

		return $output;
	}
}