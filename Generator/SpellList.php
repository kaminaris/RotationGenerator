<?php

namespace Generator;

class SpellList
{
	/** @var Spell[] */
	public $spellList = [];

	public function addSpell($spellSimcName)
	{
		foreach ($this->spellList as $spell) {
			if ($spell->spellSimcName == $spellSimcName) {
				return; // Already added
			}
		}

		$this->spellList[] = new Spell($spellSimcName);
	}

	public function toArray()
	{
		$output = [];
		foreach ($this->spellList as $spell) {
			$output[$spell->spellName] = $spell->spellId;
		}

		return $output;
	}
}