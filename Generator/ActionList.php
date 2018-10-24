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

	public function addAction($line)
	{

	}
}