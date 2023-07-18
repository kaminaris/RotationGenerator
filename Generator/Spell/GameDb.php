<?php

namespace Generator\Spell;

class GameDb implements SpellDb
{
	const DB_DIR = __DIR__ . '/../../data/';

	/** @var SpellInfo[] */
	public $db;

	public function __construct($class, $spec)
	{
		$dbFile = self::DB_DIR . strtolower($class) . '_' . strtolower($spec) . '.csv';
		$this->openDb($dbFile);
	}

	public function openDb($fileName)
	{
		$csv = array_map('str_getcsv', file($fileName, FILE_SKIP_EMPTY_LINES));
		$keys = array_shift($csv);

		$this->db = [];
		foreach ($csv as $i => $row) {
			if(count($keys) == count($row)){
			    $data = array_combine($keys, $row);
			} else {
			    continue;
			}

			$spell = new SpellInfo();
			$spell->setName($data['name']);
			$spell->id = intval($data['id']);
			$spell->castTime = floatval($data['castTime']);
			$spell->minRange = intval($data['minRange']);
			$spell->maxRange = intval($data['maxRange']);
			$spell->isPassive = boolval($data['isPassive']);
			$spell->isTalent = boolval($data['isTalent']);
			$spell->cooldown = floatval($data['cooldown']);

			$costs = [];
			$exploded = explode(';', $data['costs']);
			foreach ($exploded as $cost) {
				if (empty($cost)) {
					continue;
				}

				list($resource, $value) = explode(':', $cost);
				$resource = strtolower($resource);
				switch ($resource) {
//					case 'runic_power': $resource = 'runic'; break;
//					case 'combo_points': $resource = 'combo'; break;
				}

				$costs[$resource] = $value;
			}
			$spell->costs = $costs;

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
	 * @return bool|void
	 */
	public function hasCooldown($name)
	{
		// TODO: Implement hasCooldown() method.
	}

	public function hasCost($name)
	{
		// TODO: Implement hasCost() method.
	}
}