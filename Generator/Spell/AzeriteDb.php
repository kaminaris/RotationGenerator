<?php

namespace Generator\Spell;

class AzeriteDb implements SpellDb
{
	const DB_DIR = __DIR__ . '/../../data/';

	/** @var SpellInfo[] */
	public $db;

	public function __construct($class, $spec)
	{
		$dbFile = self::DB_DIR . 'azerite.json';
		$this->openDb($dbFile);
	}

	public function openDb($fileName)
	{
		$data = json_decode(file_get_contents($fileName));

		$this->db = [];

		foreach ($data as $trait) {

			$spell = new SpellInfo();
			$spell->setName($trait->spellName);
			$spell->id = intval($trait->spellId);
			$spell->castTime = 0;
			$spell->minRange = 0;
			$spell->maxRange = 0;
			$spell->isPassive = true;
			$spell->isTalent = false;
			$spell->cooldown = 0;
			$spell->isAzerite = true;
			$spell->costs = [];

			$this->db[$spell->id] = $spell;
		}
	}

	public function findByName($name)
	{
		foreach ($this->db as $id => $spell) {
			if ($spell->isSpell($name)) {
				return $spell;
			}
		}

		return null;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function hasCooldown($name)
	{
		return false;
	}

	public function hasCost($name)
	{
		return false;
	}
}