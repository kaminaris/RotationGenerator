<?php

namespace Generator;

class ActionList
{
	public $name;
	public $actions = [];

	public function __construct($name = null)
	{
		$this->name = $name ?? 'main';
	}

	/**
	 * @param $line
	 * @throws \Exception
	 */
	public function addAction($line)
	{
		$this->actions[] = (new Action())->parse($line);
	}
}