<?php

namespace Generator;

use Generator\Spell\AzeriteDb;

class SpellListAzerite
{
	/** @var Spell[] */
	public $spellList = [];

	/** @var AzeriteDb */
	public $spellDb;

	public function __construct(AzeriteDb $spellDb)
	{
		$this->spellDb = $spellDb;
	}

	/**
	 * @param $spellSimcName
	 * @throws \Exception
	 */
	public function addSpell($spellSimcName)
	{
		foreach ($this->spellList as $spell) {
			if ($spell->spellSimcName == $spellSimcName) {
				return; // Already added
			}
		}
		$info = $this->spellDb->findByName($spellSimcName);

		if ($info) {
			$spell = new Spell($spellSimcName);
			$spell->info = $info;
			$this->spellList[] = $spell;
		} else {
			echo 'Unknown azerite: ' . $spellSimcName . PHP_EOL;
		}

	}

	public function toArray()
	{
		$output = [];
		foreach ($this->spellList as $spell) {
			$output[$spell->spellName] = $spell->info->id;
		}

		return $output;
	}
}