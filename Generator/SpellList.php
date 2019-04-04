<?php

namespace Generator;

use Generator\Spell\SpellDb;

class SpellList
{
	/** @var Spell[] */
	public $spellList = [];

	/** @var SpellDb */
	public $spellDb;

	public function __construct($spellDb)
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
			if (strpos($spellSimcName, 'BL.') !== false) {
				throw new \Exception('Spell prefix is invalid');
			}

			if (!Action::isSpellBlacklisted($spellSimcName)) {
				echo 'Unknown spell: ' . $spellSimcName . PHP_EOL;
			}
		}

	}

	public function toArray()
	{
		$output = [];
		foreach ($this->spellList as $spell) {
			$output[str_replace(' ', '', $spell->spellName)] = $spell->info->id;
		}

		return $output;
	}
}