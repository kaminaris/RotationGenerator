<?php

namespace Generator;

use Generator\Variable\Handler;
use Tmilos\Lexer\Config\LexerArrayConfig;
use Tmilos\Lexer\Lexer;

class Action
{
	const TYPE_SPELL = 'spell';
	const TYPE_RUN = 'run';
	const TYPE_CALL = 'call';
	const TYPE_VARIABLE = 'variable';

	/** @var Profile */
	public $profile;

	/** @var ActionList */
	public $actionList;

	public $allowedActionTypes = ['spell', 'run', 'call', 'variable'];

	public $type;

	public $spell;
	public $spellName; // Variable spell name like ShadowWordPain
	public $spellNameCanonical; // Canonical spell name like Shadow Word: Pain
	public $spellId;
	public $spellCondition; // Lexed spell condition
	public $spellTarget;

	public $aplToRun;
	public $aplCondition; // Lexed spell condition

	public $variableName;
	public $variableOperation;
	public $variableValue;
	public $variableValueElse;
	public $variableCondition;

	public $rawLine;

	public $isBlacklisted = false;

	public $variableHandlers = [];


	/**
	 * @param $line
	 * @param ActionList $actionList
	 * @return Action
	 * @throws \Exception
	 */
	public static function fromSimcAction($line, $actionList)
	{
		$action = new Action();
		$action->profile = $actionList->profile;
		$action->actionList = $actionList;
		$action->rawLine = $line;

		$action->variableHandlers = $handlers = [
			new Variable\DefaultHandler($action->profile, $action),
			new Variable\AuraHandler($action->profile, $action),
			new Variable\AzeriteHandler($action->profile, $action),
			new Variable\BlacklistHandler($action->profile, $action),
			new Variable\CooldownHandler($action->profile, $action),
			new Variable\GcdHandler($action->profile, $action),
			new Variable\ResourceHandler($action->profile, $action),
			new Variable\SpellHistoryHandler($action->profile, $action),
			new Variable\TalentHandler($action->profile, $action),
			new Variable\TargetHandler($action->profile, $action),
			new Variable\EnemyCountHandler($action->profile, $action),
			new Variable\TimeShiftHandler($action->profile, $action),
			new Variable\VariableHandler($action->profile, $action),
			new Variable\WiBombHandler($action->profile, $action),
			new Variable\TotemHandler($action->profile, $action),
			new Variable\ActionHandler($action->profile, $action),
			new Variable\ActiveDotHandler($action->profile, $action),
			new Variable\TimeToDieHandler($action->profile, $action),
			new Variable\EquippedHandler($action->profile, $action),
			new Variable\PetHandler($action->profile, $action),
		];

		$exploded = explode(',', $line);
		$actionName = array_shift($exploded);

		switch ($actionName) {
			case 'call_action_list':
			case 'run_action_list':
				$action->parseCallRun($actionName, $exploded);
				break;
			case 'variable':
				$action->parseVariable($actionName, $exploded);
				break;
			case 'use_item':
			case 'use_items':
				$action->isBlacklisted = true;
				break;
			default:
				$action->type = 'spell';
				$action->parseSpell($actionName, $exploded);
				break;
		}

		return $action;
	}

	/**
	 * @param $action
	 * @param $exploded
	 * @throws \Exception
	 */
	public function parseCallRun($action, $exploded)
	{
		if ($action == 'call_action_list') {
			$this->type = 'call';
		} else {
			$this->type = 'run';
		}

		foreach ($exploded as $item) {
			list($name, $value) = $this->parseVar($item);

			switch ($name) {
				case 'name': $this->aplToRun = $value; break;
				case 'if': $this->aplCondition = $this->parseExpression($value); break;
				default:
					throw new \Exception(
						'Unrecognized call/run command: ' . $name . ' expression: '. implode(',', $exploded)
					);
					break;
			}
		}
	}

	/**
	 * @param $action
	 * @param $exploded
	 * @throws \Exception
	 */
	public function parseVariable($action, $exploded)
	{
		$this->type = 'variable';

		foreach ($exploded as $item) {
			list($name, $value) = $this->parseVar($item);

			switch ($name) {
				case 'name': $this->variableName = Helper::camelCase($value); break;
				case 'value': $this->variableValue = $this->parseExpression($value); break;
				case 'value_else': $this->variableValueElse = $this->parseExpression($value); break;
				case 'op': $this->variableOperation = $value; break;
				case 'condition': $this->variableCondition = $this->parseExpression($value); break;
				case 'if': $this->variableCondition = $this->parseExpression($value); break;
				default:
					throw new \Exception(
						'Unrecognized variable operator: ' . $name . ' expression: '. implode(',', $exploded)
					);
					break;
			}
		}
	}

	/**
	 * @param $action
	 * @param $exploded
	 * @throws \Exception
	 */
	public function parseSpell($action, $exploded)
	{
		$this->spell = $action;
		$this->spellName = $this->profile->SpellName($action);
		$this->spellNameCanonical = Helper::properCaseWithSpaces($action);

		if ($this->isSpellBlacklisted($action)) {
			$this->isBlacklisted = true;
			return;
		}

		if (empty($exploded)) {
			// unconditional spell
			$this->spellCondition = true;
		} else {
			foreach ($exploded as $item) {
				list($name, $value) = $this->parseVar($item);

				switch ($name) {
					case 'name': $this->spellName = $value; break;
					case 'target_if': $this->spellTarget = $this->parseExpression($value); break;
					case 'if': $this->spellCondition = $this->parseExpression($value); break;

					case 'interval': // ignore intervals
					case 'pct_health': // ignore pct_health
					case 'cycle_targets': //ignore cycling targets
					case 'moving': //ignore moving
					case 'strikes': //ignore strikes
					case 'interrupt_global': //ignore interrupt_global
					case 'interrupt': //ignore interrupt_global
					case 'interrupt_if': //ignore interrupt_global
					case 'interrupt_immediate': //ignore interrupt_immediate
					case 'chain': //ignore chain
					case 'use_while_casting': //ignore use_while_casting
					case 'max_cycle_targets': //ignore use_while_casting
					case 'cancel_if': //ignore cancel_if
					case 'line_cd': //ignore line_cd
					case 'delay': //ignore line_cd
					case 'use_off_gcd': break; //ignore use_off_gcd
					case 'precast_time': break; //ignore precast_time

					case 'for_next': //@TODO
					case 'precombat_seconds':
						$this->isBlacklisted = true;
						return;
						break;
					default:
						throw new \Exception(
							'Unrecognized spell operator: ' . $name . ' expression: '. implode(',', $exploded)
						);
						break;
				}
			}
		}

		$additionalConditions = [];
		$spellInfo = $this->profile->spellDb->findByName($action);
		if ($spellInfo) {
			if ($spellInfo->isTalent) {
				$this->actionList->resourceUsage->talents = true;
				$additionalConditions[] = "talents[{$this->spellName}]";
			}

			if ($spellInfo->hasCooldown()) {
				$this->actionList->resourceUsage->cooldown = true;
				$additionalConditions[] = "cooldown[{$this->spellName}].ready";
			}

			if ($spellInfo->hasCost()) {
				foreach ($spellInfo->costs as $resource => $amount) {
					$resource = Helper::camelCase($resource);
					$this->actionList->resourceUsage->addResource($resource);
					$additionalConditions[] = "$resource >= $amount";
				}
			}

			if ($spellInfo->castTime > 0) {
				$this->actionList->resourceUsage->currentSpell = true;
				$additionalConditions[] = "currentSpell ~= {$this->spellName}";
			}
		}

		if (!empty($additionalConditions)) {
			if ($this->spellCondition !== true) {
				$additionalConditions[] = "({$this->spellCondition})";
			}

			$this->spellCondition = implode(' and ', $additionalConditions);
		}
	}

	/**
	 * @param $part
	 * @return mixed
	 * @throws \Exception
	 */
	public function parseVar($part)
	{
		if (preg_match('/^(\w+)=(.*)$/', $part, $out)) {
			array_shift($out);
			return $out;
		} else {
			throw new \Exception('Unrecognized variable part: ' . $part);
		}
	}

	/**
	 * @param $expression
	 * @return string
	 * @throws \Exception
	 */
	public function parseExpression($expression)
	{
		$config = new LexerArrayConfig([
			'\\s' => '',
			'[\\d\.]+' => 'number',
			'[\\w\.]+' => 'variable',

			'\\|' => 'or',
			'\\&' => 'and',
			'\\!' => 'not',
			'\\<=' => 'lteq',
			'\\>=' => 'gteq',
			'\\<' => 'lt',
			'\\>' => 'gt',

			'\\+' => 'plus',
			'-' => 'minus',
			'\\*' => 'mul',
			'\\%' => 'mod',
			'/' => 'div',
			'\\=' => 'eq',
			'\\:' => 'semicolon',


			'\\(' => 'open',
			'\\)' => 'close',
		]);

		$lexer = new Lexer($config);
		$lexer->setInput($expression);
		$lexer->moveNext();

		$output = [];

		while ($lookAhead = $lexer->getLookahead()) {
			$name = $lookAhead->getName();
			$value = $lookAhead->getValue();

			$previousElement = end($output);

			switch ($name) {
				case 'and': $output[] = 'and'; break;
				case 'or': $output[] = 'or'; break;
				case 'not': $output[] = 'not'; break;
				case 'eq': $output[] = '=='; break;
				case 'plus':
				case 'minus':
				case 'mul':
				case 'mod':
				case 'div':
				case 'lteq':
				case 'gteq':
				case 'lt':
				case 'gt':
				case 'open':
				case 'close':
				case 'number':
				case 'semicolon':
					$output[] = $value;
					break;
				case 'variable':
					$exploded = explode('.', $value);
					$variableType = $exploded[0];

					foreach ($this->variableHandlers as $handler) {
						if ($handler->canHandle($exploded)) {
							$handler->handle($lexer, $exploded, $output);
							break 2;
						}
					}

					throw new \Exception(
						'Unrecognized variable type: ' . $variableType . ' name: ' . $value . ' expr: ' . $expression
					);

					break;
				default:
					throw new \Exception(
						'Unrecognized token: ' . $name . ' expr: ' . $expression
					);
					break;
			}

			$lexer->moveNext();
		}

		return implode(' ', $output);
	}


	protected function isSpellBlacklisted($spellName)
	{
		return in_array($spellName, [
			'flask', 'food', 'augmentation', 'summon_pet', 'snapshot_stats', 'potion', 'arcane_pulse',
			'lights_judgment', 'arcane_torrent', 'blood_fury', 'berserking', 'fireblood', 'auto_attack',
			'use_items', 'flying_serpent_kick', 'ancestral_call', 'auto_shot', 'bloodlust', 'wind_shear',
			'counterspell'
		]);
	}
}