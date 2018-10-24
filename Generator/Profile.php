<?php

namespace Generator;

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
		'demonhunter'
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

	/**
	 * Loads simcraft profile either from URL or string
	 * @param $string
	 */
	public function load($string)
	{

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

		$this->mainActionList = new ActionList();
		$this->actionLists = [];
		$this->parsedProfile = [];

		foreach ($this->rawProfile as $line) {
			$line = trim($line);
			if (strpos($line, '#') || empty($line)) {
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
			$this->actionLists[$name] = new ActionList($name);
		}

		return $this->actionLists[$name];
	}

	protected function parseProfileLine($key, $value)
	{
		if (in_array($key, $this->classList)) {
			$this->class = $key;
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
			case 'spec': $this->spec = $value; break;
		}
	}
}