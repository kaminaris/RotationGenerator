<?php

namespace Converter;

use Generator\ActionList;
use Generator\Helper;
use Generator\Profile;
use LuaWriter\Element;

class Converter
{
	protected $fileName;
	protected $handle;
	/** @var Profile */
	protected $profile;
	protected $class;
	protected $spec;

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

	public function __destruct()
	{
		if (get_resource_type($this->handle) == 'stream') {
			fclose($this->handle);
		}
	}

	/**
	 * @throws \Exception
	 */
	public function convert()
	{
		$spellPrefix = $this->profile->spellPrefix;

		$spellList = new Element($this->handle, 0);
		$spellList->makeArray($spellPrefix, $this->profile->spellList->toArray());
		$spellList->write();

		$azeriteList = new Element($this->handle, 0);
		$azeriteList->makeArray('A', $this->profile->azeriteSpellList->toArray());
		$azeriteList->write();

		$this->class = Helper::properCase($this->profile->class);
		$this->spec = Helper::properCase($this->profile->spec);

		$mainList = new Element($this->handle, 0);
		$this->writeActionList($this->profile->mainActionList, $mainList);
		$mainList->write();

		foreach ($this->profile->actionLists as $actionList) {
			if ($actionList->name == 'precombat') {
				continue;
			}
			$list = new Element($this->handle, 0);
			$this->writeActionList($actionList, $list);
			if (is_null($list)) {
				var_dump($actionList);
			}
			$list->write();
			$list->makeChildren()->makeNewline()->write();
		}

		//fclose($this->handle);
	}

	/**
	 * @param ActionList $list
	 * @param Element $element
	 * @throws \Exception
	 */
	protected function writeActionList(ActionList $list, Element $element)
	{
		$funcName = $list->getFunctionName();

		$children = [];

		$this->writeResources($list, $element, $children);

		foreach ($list->actions as $action) {

			$children[] = $element->makeChildren()->makeNewline();
			$children[] = $element->makeChildren()->makeComment($action->rawLine);

			switch ($action->type) {
				case $action::TYPE_VARIABLE:
					if ($action->variableCondition) {
						$condition = $element->makeChildren();
						switch ($action->variableOperation) {
							case 'set':
							case 'add':
							case 'sub':
								$value = $action->variableValue;
								break;
							case 'reset':
								$value = 0;
								break;
							default:
								throw new \Exception('Unrecognized variable operation: ' . $action->variableOperation);
						}

						$var = $condition->makeChildren()->makeVariable($action->variableName, $value, $action->variableOperation);

						$children[] = $condition->makeCondition($action->variableCondition, [$var]);
					} else {
						$children[] = $element->makeChildren()->makeVariable($action->variableName, $action->variableValue, $action->variableOperation);
					}
					break;
				case $action::TYPE_SPELL:
					if ($action->spellCondition === true) {
						// unconditional spells
						$children[] = $element->makeChildren()->makeResult($action->spellName);
					} elseif ($action->spellCondition) {
						$child = $element->makeChildren();
						$result = $child->makeChildren()->makeResult($action->spellName);

						$children[] = $child->makeCondition($action->spellCondition, [$result]);
					} else {
						$children[] = $element->makeChildren()->makeResult($action->spellName);
					}

					break;
				case $action::TYPE_CALL: //@TODO
				case $action::TYPE_RUN:
					if ($action->aplCondition) {
						$subCondition = $element->makeChildren();

						$conditionChildren = [];
						if ($action->type == $action::TYPE_CALL) {

							$conditionChildren[] = $subCondition
								->makeChildren()
								->makeVariable('result', $this->getAplListName($action->aplToRun), 'set');

							$child = $subCondition->makeChildren();

							$conditionChildren[] = $subCondition
								->makeChildren()
								->makeCondition('result', [$child->makeChildren()->makeResult('result')]);
						} else {
							$conditionChildren[] = $subCondition
								->makeChildren()->makeResult($this->getAplListName($action->aplToRun));
						}

						$children[] = $subCondition->makeCondition($action->aplCondition, $conditionChildren);
					} else {
						if ($action->type == $action::TYPE_CALL) {
							$children[] = $element->makeChildren()->makeVariable('result', $this->getAplListName($action->aplToRun));
							$child = $element->makeChildren();
							$children[] = $child->makeCondition('result', [$child->makeChildren()->makeResult('result')]);
						} else {
							$children[] = $element->makeChildren()->makeResult($this->getAplListName($action->aplToRun));
						}
					}
					break;
				default:
					throw new \Exception('Unrecognized action type: ' . $action->type);
					break;
			}
		}

		$element->makeFunction($funcName, [], $children);
	}

	protected function writeResources(ActionList $list, Element $element, &$children)
	{
		$children[] = $element->makeChildren()->makeVariable('fd', 'MaxDps.FrameData');

		foreach ($list->resourceUsage as $key => $value) {
			if (!$value || $key == 'resources') {
				continue;
			}

			$children[] = $element->makeChildren()->makeVariable($key, 'fd.' . $key);
		}

		foreach ($list->resourceUsage->resources as $resource => $isUsed) {
			$properName = Helper::properCase($resource);
			$children[] = $element->makeChildren()->makeVariable($resource, "UnitPower('player', Enum.PowerType.{$properName})");
		}
	}

	protected function getAplListName($apl)
	{
		$actionList = $this->profile->getActionListByName($apl);
		return $actionList->getFunctionName() . '()';
	}
}