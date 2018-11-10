<?php

namespace Generator\Spell;

class SpellInfo extends \stdClass
{
	public $id;
	public $name;
	public $nameNormalized;
	public $castTime;
	public $minRange;
	public $maxRange;
	public $isPassive;
	public $isTalent;
	public $cooldown;
	public $costs;

	public function setName($name)
	{
		$this->name = $name;
		$this->nameNormalized = $this->normalizeSpellName($name);
	}

	public function normalizeSpellName($name)
	{
		return str_replace(' ', '_', strtolower(preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
	}

	public function isSpell($name)
	{
		return strtolower($this->name) == strtolower($name) || $this->nameNormalized == $name;
	}

	public function hasCost()
	{
		return !empty($this->costs);
	}

	public function hasCooldown()
	{
		return $this->cooldown > 0;
	}
}