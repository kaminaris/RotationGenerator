<?php

namespace Generator;

use Generator\Spell\AzeriteDb;
use Generator\Spell\GameDb;
use GuzzleHttp\Client;

class Profile
{
	protected $classList = [
		'warrior',
		'paladin',
		'hunter',
		'rogue',
		'priest',
		'deathknight',
		'shaman',
		'mage',
		'warlock',
		'monk',
		'druid',
		'demonhunter',
		'evoker'
	];

	public $spellPrefix;
	public $spellPrefixes = [
		'warrior'     => ['arms'          => 'AR', 'fury'         => 'FR', 'protection'  => 'PR'],
		'paladin'     => ['retribution'   => 'RT', 'holy'         => 'HL', 'protection'  => 'PR'],
		'hunter'      => ['beast_mastery' => 'BM', 'marksmanship' => 'MM', 'survival'    => 'SV'],
		'rogue'       => ['assassination' => 'AS', 'outlaw'       => 'OL', 'subtlety'    => 'SB'],
		'priest'      => ['shadow'        => 'SH', 'discipline'   => 'DS', 'holy'        => 'HL'],
		'deathknight' => ['blood'         => 'BL', 'frost'        => 'FR', 'unholy'      => 'UH'],
		'shaman'      => ['elemental'     => 'EL', 'enhancement'  => 'EH', 'restoration' => 'RT'],
		'mage'        => ['arcane'        => 'AR', 'fire'         => 'FR', 'frost'       => 'FT'],
		'warlock'     => ['affliction'    => 'AF', 'demonology'   => 'DE', 'destruction' => 'DS'],
		'monk'        => ['brewmaster'    => 'BR', 'windwalker'   => 'WW', 'mistweaver'  => 'MW'],
		'druid'       => ['balance'       => 'BL', 'guardian'     => 'GR', 'feral'       => 'FR', 'restoration' => 'RS'],
		'demonhunter' => ['havoc'         => 'HV', 'vengeance'    => 'VG'],
		'evoker' => ['devastation'         => 'DV'],
	];

	public $rawProfile;
	public $parsedProfile;

	public $class;
	public $spec;
	public $name;

	/** @var ActionList */
	public $mainActionList;

	/** @var ActionList[] */
	public $actionLists = [];

	/** @var SpellList */
	public $spellList;

	/** @var SpellListAzerite */
	public $azeriteSpellList;

	/** @var GameDb */
	public $spellDb;

	/** @var AzeriteDb */
	public $azeriteDb;

	/**
	 * Loads simcraft profile either from URL or string
	 * @param $string
	 * @return Profile
	 * @throws \Exception
	 */
	public function load($string)
	{
		if (strpos($string, 'http') === 0) {
			$this->loadFromUri($string);
		} elseif (file_exists($string)) {
			$this->loadFromString(file_get_contents($string));
		} else {
			$this->loadFromString($string);
		}

		return $this;
	}

	/**
	 * Loads simcraft profile from URI ex
	 * https://raw.githubusercontent.com/simulationcraft/simc/bfa-dev/profiles/Tier22/T22_Hunter_Survival.simc
	 *
	 * @param $uri
	 * @return Profile
	 * @throws \Exception
	 */
	public function loadFromUri($uri)
	{
		$guzzle = new Client(['verify' => false]);
		$response = $guzzle->get($uri);

		return $this->loadFromString($response->getBody()->getContents());
	}

	/**
	 * @param $string
	 * @return $this
	 * @throws \Exception
	 */
	public function loadFromString($string)
	{
		$this->rawProfile = Helper::splitString($string);

		$this->mainActionList = ActionList::forProfile($this);

		$this->actionLists = [];
		$this->parsedProfile = [];

		foreach ($this->rawProfile as $line) {
			$line = trim($line);
			if (strpos($line, '#') === 0 || empty($line)) {
				continue;
			}

			if (preg_match('/^([\w\.]+)\+?=\/?(.*)$/', $line, $output)) {
				$key = $output[1];
				$value = $output[2];

				$this->parseProfileLine($key, $value);
			} else {
				throw new \Exception('Unrecognized profile line: ' . $line);
			}
		}

		return $this;
	}

	protected function getOrCreateActionList($name)
	{
		if (!isset($this->actionLists[$name])) {
			$this->actionLists[$name] = ActionList::forProfile($this, $name);
		}

		return $this->actionLists[$name];
	}

	public function getActionListByName($name)
	{
		return $this->actionLists[$name] ?? null;
	}

	/**
	 * @param $key
	 * @param $value
	 * @throws \Exception
	 */
	protected function parseProfileLine($key, $value)
	{
		if (in_array($key, $this->classList)) {
			$this->class = $key;
			$this->name = trim($value, '"');
			return;
		}

		$detailedKey = explode('.', $key);
		$mainKey = $detailedKey[0];
		$subKey = $detailedKey[1] ?? null;

		if ($mainKey == 'actions') {
			if (empty($subKey)) {
				$this->mainActionList->addAction($value);
			} else {
				$this->getOrCreateActionList($subKey)->addAction($value);
			}
			return;
		}

		switch ($key) {
			case 'spec':
				$this->spec = $value;
				$this->spellPrefix = $this->spellPrefixes[$this->class][$this->spec];
				// we need to know spec first
				$this->spellDb = new Spell\GameDb($this->class, $this->spec);
				$this->azeriteDb = new Spell\AzeriteDb($this->class, $this->spec);

				$this->spellList = new SpellList($this->spellDb);
				$this->azeriteSpellList = new SpellListAzerite($this->azeriteDb);
			break;
			default:
				$this->parsedProfile[$key] = $value;
				break;
		}
	}

	/**
	 * Prefix Spell Name
	 *
	 * @param $name
	 * @return string
	 * @throws \Exception
	 */
	public function SpellName($name)
	{
		// keep it in spell list
		$this->spellList->addSpell($name);

		return $this->spellPrefix . '.' . Helper::properCase($name);
	}

	/**
	 * Prefix Spell Name
	 *
	 * @param $name
	 * @return string
	 * @throws \Exception
	 */
	public function AzeriteName($name)
	{
		// keep it in spell list
		$this->azeriteSpellList->addSpell($name);

		return 'A.' . Helper::properCase($name);
	}
}