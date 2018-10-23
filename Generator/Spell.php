<?php

namespace Generator;

class Spell
{
	public $spellName;
	public $spellId;


	public function fromSimcName($name)
	{
		$this->spellName = Helper::properCase($name);
	}
}