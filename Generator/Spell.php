<?php

namespace Generator;

class Spell
{
	public $spellSimcName;

	public $spellName;
	public $spellId;

	public $spellCooldown = 0;
	public $spellCost = 0;
	public $isTalent;

	public function __construct($name)
	{
		$this->spellSimcName = $name;
		$this->spellName = Helper::properCase($name);
	}
}