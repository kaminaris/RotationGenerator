<?php

namespace Generator;

class ActionList
{
	public $name;
	/** @var Profile */
	public $profile;
	public $actions = [];

	public static function forProfile(Profile $profile, $name = null)
	{
		$actionList = new ActionList();
		$actionList->profile = $profile;
		$actionList->name = $name ?? 'main';

		return $actionList;
	}

	/**
	 * @param $line
	 * @throws \Exception
	 */
	public function addAction($line)
	{
		$this->actions[] = Action::fromSimcAction($line);
	}
}