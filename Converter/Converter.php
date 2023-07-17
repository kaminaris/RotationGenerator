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

		$pascalclass = Helper::pascalCase($this->profile->class);

		$constantsList = new Element($this->handle, 0);
		$constantsList->makeVariable("_, addonTable", "...");
		$constantsList->write();
		$constantsList->makeNewline();
		$constantsList->write();
		$constantsList->makeComment("@type MaxDps");
		$constantsList->write();
		$constantsList->makeStatement("if not MaxDps then return end");
		$constantsList->write();
		$constantsList->makeVariable("{$pascalclass}", "addonTable.{$pascalclass}");
		$constantsList->write();
		$constantsList->makeVariable("MaxDps", "MaxDps");
		$constantsList->write();
		$constantsList->makeNewline();
		$constantsList->write();
		$constantsList->makeVariable("UnitPower", "UnitPower");
		$constantsList->write();
		$constantsList->makeVariable("UnitPowerMax", "UnitPowerMax");
		$constantsList->write();
		$constantsList->makeNewline();
		$constantsList->write();

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
		$varelse = null;

		$this->writeResources($list, $element, $children);

		foreach ($list->actions as $action) {

			$children[] = $element->makeChildren()->makeNewline();
			$children[] = $element->makeChildren()->makeComment($action->rawLine);

			switch ($action->type) {
				case $action::TYPE_VARIABLE:
					if ($action->variableCondition) {
						$condition = $element->makeChildren();
						switch ($action->variableOperation) {
							case '':
								$value = 'WTFFFFFF';
								break;
							case 'set':
								$value = $action->variableValue;
								break;
							case 'setif':
								$value = $action->variableValue;
								break;
							case 'add':
							case 'sub':
								$value = $action->variableValue;
								break;
							case 'reset':
								$value = 0;
								break;
							case 'min':
								$value = $action->variableValue;
								break;
							case 'max':
								$value = $action->variableValue;
								break;
							default:
								throw new \Exception('Unrecognized variable operation: ' . $action->variableOperation);
						}
						if (!isset($value)) {
							throw new \Exception('Missing $value for: ' . $action->variableOperation);
						}
						$var = $condition->makeChildren()->makeVariable($action->variableName, $value, $action->variableOperation);
						if (isset($action->variableValueElse)){
							$varelse = $condition->makeChildren()->makeVariable($action->variableName, $action->variableValueElse, $action->variableOperation);
							$children[] = $condition->makeCondition($action->variableCondition, [$var], [$varelse]);
						} else {
						    $children[] = $condition->makeCondition($action->variableCondition, [$var]);
						}
						
					} else {
						$children[] = $element->makeChildren()->makeVariable($action->variableName, $action->variableValue, $action->variableOperation);
					}
					break;
				case $action::TYPE_SPELL:
					if ($action->spellCondition === true) {
						// unconditional spells
						$children[] = $element->makeChildren()->makeComment($action->spellName);
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
							if ($action->aplToRun == "trinkets" or $action->aplToRun == "racials") {
								break;
							}
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
		$children[] = $element->makeChildren()->makeVariable("timeTo35", "fd.timeToDie");
		$children[] = $element->makeChildren()->makeVariable("timeTo20", "fd.timeToDie");
		$children[] = $element->makeChildren()->makeVariable("targetHp", "MaxDps:TargetPercentHealth() * 100");

		foreach ($list->resourceUsage as $key => $value) {
			if (!$value || $key == 'resources') {
				continue;
			}

			$resourceValue = $list->resourceUsage->getResourceVariableValue($key);
			$children[] = $element->makeChildren()->makeVariable($key, $resourceValue);
		}

		foreach ($list->resourceUsage->resources as $resource => $isUsed) {
		    #handle (rage/mana).pct and (runicpower/insanity).deficit (rune).time_to_3 (rune).time_to_4 (rune).time_to_2
		    # (focus/energy).time_to_max (focus/chi).max (energy).regen (energy).regen_combined
			$properName = Helper::properCase($resource);
			$children[] = $element->makeChildren()->makeVariable($resource, "UnitPower('player', Enum.PowerType.{$properName})");
			if ($properName == 'Runes') {
				$children[] = $element->makeChildren()->makeVariable($resource . "duration", "select(2,GetRuneCooldown(1))");
				$children[] = $element->makeChildren()->makeVariable($resource . "Regen", $resource . "duration and math.floor({$resource}duration*100)/100");
				$children[] = $element->makeChildren()->makeVariable($resource . "TimeTo2", "DeathKnight:TimeToRunes(2)");
				$children[] = $element->makeChildren()->makeVariable($resource . "TimeTo3", "DeathKnight:TimeToRunes(3)");
				$children[] = $element->makeChildren()->makeVariable($resource . "TimeTo4", "DeathKnight:TimeToRunes(4)");
			} else {
				$children[] = $element->makeChildren()->makeVariable($resource . "Max", "UnitPowerMax('player', Enum.PowerType.{$properName})");
				$children[] = $element->makeChildren()->makeVariable($resource . "Pct", "UnitPower('player')/UnitPowerMax('player') * 100");
				$children[] = $element->makeChildren()->makeVariable($resource . "Regen", "select(2,GetPowerRegen())");
				$children[] = $element->makeChildren()->makeVariable($resource . "RegenCombined", "{$resource}Regen + {$resource}");
				$children[] = $element->makeChildren()->makeVariable($resource . "Deficit", "UnitPowerMax('player', Enum.PowerType.{$properName}) - $resource");
				$children[] = $element->makeChildren()->makeVariable($resource . "TimeToMax", "{$resource}Max - {$resource} / {$resource}Regen");
			}

		}
		if ($this->class == "Warrior"){
			$children[] = $element->makeChildren()->makeVariable("canExecute", "((talents[FR.Massacre] and targetHp < 35) or targetHp < 20) or buff[FR.SuddenDeathAura].up");
		}
	}

	protected function getAplListName($apl)
	{
		$actionList = $this->profile->getActionListByName($apl);
		return $actionList->getFunctionName() . '()';
	}
}