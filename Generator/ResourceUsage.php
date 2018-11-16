<?php

namespace Generator;

class ResourceUsage extends \stdClass
{
	public $cooldown = false;
	public $azerite = false;
	public $buff = false;
	public $debuff = false;
	public $currentSpell = false;
	public $spellHistory = false;
	public $talents = false;
	public $timeShift = false;
	public $targets = false;
	public $gcd = false;
	public $gcdRemains = false;

	public $resources = [];
}