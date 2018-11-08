<?php

namespace Generator;

class ActionList
{
	public $name;
	/** @var Profile */
	public $profile;
	/** @var Action[] */
	public $actions = [];

	public static function forProfile(Profile $profile, $name = null)
	{
		$actionList = new ActionList();
		$actionList->profile = $profile;
		$actionList->name = $name ?? '_MAIN';

		return $actionList;
	}

	public function getFunctionName()
	{
		$result = Helper::properCase($this->profile->class);

		if ($this->name == '_MAIN') {
			return $result . ':' . Helper::properCase($this->profile->spec);
		} else {
			return $result . ':' . Helper::properCase($this->profile->spec) . Helper::properCase($this->name);
		}
	}

	/**
	 * @param $line
	 * @throws \Exception
	 */
	public function addAction($line)
	{
		$action = Action::fromSimcAction($line, $this->profile);
		if ($action && !$action->isBlacklisted) {
			$this->actions[] = $action;
		} else {
			echo 'Action blacklisted: ' . $line . PHP_EOL;
		}

	}
}