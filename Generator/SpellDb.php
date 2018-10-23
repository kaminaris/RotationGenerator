<?php

namespace Generator;

class SpellDb
{
	const SPELL_DB_FILE = __DIR__ . '/../spellDb.json';

	protected $db;
	protected $api;

	public function __construct()
	{
		if (!file_exists(self::SPELL_DB_FILE)) {
			file_put_contents(self::SPELL_DB_FILE, '{}');
		}

		$this->db = json_decode(file_get_contents(self::SPELL_DB_FILE));
		$this->api = new WowDb();
	}

	public function saveDb()
	{
		file_put_contents(self::SPELL_DB_FILE, json_encode($this->db));
	}

	public function findById($spellId)
	{
		if (isset($this->db[$spellId])) {
			return $this->db[$spellId];
		} else {
			$spell = $this->api->spell($spellId);
			$this->db[(int)$spellId] = $spell;
			$this->saveDb();

			return $spell;
		}
	}

	public function findByName($name)
	{
		$result = [];

		foreach ($this->db as $spell) {
			if ($spell->Name == $name) {
				$result[$spell->ID] = $spell;
			}
		}

		if (empty($result)) {
			$result = $this->api->findSpells($name);
			foreach ($result as $spellId => $spell) {
				$this->db->{$spellId} = $spell;
			}

			$this->saveDb();
		}

		return $result;
	}
}