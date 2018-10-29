<?php

namespace Generator;

use Generator\Spell\SpellInfo;

class Spell
{
	public $spellSimcName;

	public $spellName;
	public $spellId = 0;

	/** @var SpellInfo */
	public $info;

	public $isTalent;

	public function __construct($name)
	{
		$this->spellSimcName = $name;
		$this->spellName = Helper::properCase($name);
	}
}