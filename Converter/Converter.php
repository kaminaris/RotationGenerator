<?php

namespace Converter;

use Generator\Helper;
use Generator\Profile;
use LuaWriter\Element;

class Converter
{
	protected $fileName;
	protected $handle;
	/** @var Profile */
	protected $profile;

	/**
	 * Converter constructor.
	 * @param $fileName
	 * @param Profile $profile
	 * @throws \Exception
	 */
	public function __construct($fileName, Profile $profile)
	{
		$this->fileName = $fileName;
		$this->handle = fopen($fileName, 'w');

		if (!$this->handle) {
			throw new \Exception('Could not open file: ' . $fileName);
		}

		$this->profile = $profile;
	}

	public function convert()
	{
		$spellPrefix = strtoupper(substr($this->profile->spec, 0, 2));

		$spellList = new Element(Element::TYPE_ARRAY, $this->handle, 0);
		$spellList->makeArray($spellPrefix, $this->profile->spellList->toArray());
		$spellList->write();

		$class = Helper::properCase($this->profile->class);
		$spec = Helper::properCase($this->profile->spec);

		$mainList = new Element(Element::TYPE_FUNCTION, $this->handle, 0);

		$mainList->write();


	}
}