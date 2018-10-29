<?php

namespace Generator\Spell;

interface SpellDb
{
	public function findByName($name);

	public function hasCooldown($name);

	public function hasCost($name);
}