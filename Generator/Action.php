<?php

namespace Generator;

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
	public $variableOperator;
	public $variableValue;
	public $variableValueElse;
	public $variableCondition;

	public $rawLine;

	public $isBlacklisted = false;

	public $spellList = [];
	public $resourcesUsed = [];

	/**
	 * @param $line
	 * @param $profile
	 * @return Action
	 * @throws \Exception
	 */
	public static function fromSimcAction($line, $profile)
	{
		$action = new Action();
		$action->profile = $profile;
		$action->rawLine = $line;

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
				case 'op': $this->variableOperator = $value; break;
				case 'condition': $this->variableCondition = $this->parseExpression($value); break;
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
					case 'use_off_gcd': break; //ignore use_off_gcd

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

					switch ($variableType) {
						case 'talent': $this->handleTalent($lexer, $exploded, $output); break;
						case 'azerite': $this->handleAzerite($lexer, $exploded, $output); break;
						case 'cooldown': $this->handleCooldown($lexer, $exploded, $output); break;
						case 'dot':
						case 'buff':
						case 'debuff': $this->handleAura($lexer, $exploded, $output); break;
						case 'variable': $this->handleVariable($lexer, $exploded, $output); break;
						case 'prev_gcd': $this->handlePreviousSpell($lexer, $exploded, $output); break;

						case 'action': $output[] = $value; break; //@TODO
						case 'race': $output[] = $value; break; //@TODO
						case 'target': $output[] = $value; break; //@TODO
						case 'bloodseeker': $output[] = $value; break; //@TODO
						case 'stealthed': $output[] = $variableType; break; //@TODO

						// targets
						case 'spell_targets':
						case 'active_enemies': $output[] = 'targets'; break;

						// resources
						case 'runic_power':
						case 'chi':
						case 'focus':
						case 'combo_points':
						case 'rune':
						case 'gcd':
						case 'energy': $this->handleResources($lexer, $exploded, $output); break;

						// global vars
						case 'tick_time':
						case 'priority_rotation':
						case 'cp_max_spend':
							$output[] = Helper::camelCase($value);
							break;

						// shortcuts
						case 'charges_fractional': $this->handleCooldown($lexer, ['cooldown', $this->spellName, 'charges'], $output); break;
						case 'full_recharge_time': $this->handleCooldown($lexer, ['cooldown', $this->spellName, 'fullRecharge'], $output); break;
						case 'ticking': $output[] = "debuff[{$this->spellName}].up"; break;
						case 'refreshable':
						case 'remains': $output[] = "debuff[{$this->spellName}].{$value}"; break;

						case 'next_wi_bomb':
							$spellPrefix = $this->profile->spellPrefix;
							$bombName = Helper::properCase($exploded[1]) . 'Bomb';
							$output[] = "nextWiBomb == {$spellPrefix}.$bombName";
							break; //@TODO

						case 'min':
						case 'max':
						case 'movement':
						case 'raid_event':
						case 'time':
							$this->handleBlacklisted($lexer, $exploded, $output);
							break;
						default:
							throw new \Exception(
								'Unrecognized variable type: ' . $variableType . ' name: ' . $value . ' expr: ' . $expression
							);
							break;
					}

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

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleTalent($lexer, $variable, &$output)
	{
		$previousElement = end($output);
		$this->spellList[$variable[1]] = true;

		$talentName = $this->profile->SpellName($variable[1]);
		$suffix = $variable[2];

		if ($suffix == 'enabled') {
			$value = 'talents[' . $talentName . ']';
		} elseif ($suffix == 'disabled') {
			$value = 'not talents[' . $talentName . ']';
		} else {
			throw new \Exception('Unrecognized talent switch type: ' . $suffix);
		}

		$glimpse = $lexer->glimpse();
		if ($glimpse) {
			$nextVal = $glimpse->getValue();
			if (
				in_array($previousElement, ['*', '/', '+', '-']) ||
				in_array($nextVal, ['*', '/', '+', '-'])
			) {
				$value = "({$value} and 1 or 0)";
			}
		}

		$output[] = $value;
	}

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleAzerite($lexer, $variable, &$output)
	{
		$this->spellList[$variable[1]] = true;
		$spell = $this->profile->SpellName($variable[1]);

		$suffix = $variable[2];

		$value = null;
		switch($suffix) {
			case 'rank':
				$value = "azerite[{$spell}]";
				break;
			case 'enabled':
				$value = "azerite[{$spell}] > 0";
				break;
			default:
				throw new \Exception('Unrecognized azerite suffix type: ' . $suffix);
				break;
		}

		$output[] = $value;
	}

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handlePreviousSpell($lexer, $variable, &$output)
	{
		$history = is_numeric($variable[1]) ? intval($variable[1]) : 1;
		$spellSimcName = is_numeric($variable[1]) ? $variable[2] : $variable[1];
		$spell = $this->profile->SpellName($spellSimcName);
		$this->spellList[$spellSimcName] = true;

		$value = "spellHistory[{$history}] == {$spell}";

		$output[] = $value;
	}

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleVariable($lexer, $variable, &$output)
	{
		$variable = Helper::camelCase($variable[1]);

		$output[] = $variable;
	}

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleBlacklisted($lexer, $variable, &$output)
	{
		$previousElement = end($output);
		$blacklist = ['*', '/', '+', '-', '>', '<', '<=', '>=', '&', '|'];

		while (in_array($previousElement, $blacklist)) {
			array_pop($output);
			$previousElement = end($output);
		}

		while ($glimpse = $lexer->glimpse()) {
			$nextVal = $glimpse->getValue();

			if (in_array($nextVal, $blacklist)) {
				// skip next
				$lexer->moveNext();
			} else {
				break;
			}
		}

		if (count($output) == 1 && in_array($output[0], ['and', 'or'])) {
			array_pop($output);
		}
	}


	protected function handleResources($lexer, $exploded, &$output)
	{
		switch ($exploded[0]) {
			case 'runic_power': $exploded[0] = 'runic'; break;
			case 'combo_points': $exploded[0] = 'combo'; break;
		}

		$output[] = Helper::camelCase(implode('_', $exploded));
	}

	/**
	 * @param $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleCooldown($lexer, $variable, &$output)
	{
		$spell = $this->profile->SpellName($variable[1]);
		$this->spellList[$variable[1]] = true;

		$prefix = $variable[0];
		$suffix = $variable[2];

		$value = null;
		switch($suffix) {
			case 'up':
			case 'ready': $value = "{$prefix}[{$spell}].ready"; break;
			case 'charges':
			case 'charges_fractional':
			case 'stack': $value = "{$prefix}[{$spell}].charges"; break;
			case 'remains': $value = "{$prefix}[{$spell}].remains"; break;
			default:
				throw new \Exception(
					'Unrecognized cooldown suffix type: ' . $suffix . ' expression: ' . implode('.', $variable)
				);
				break;
		}


		$output[] = $value;
	}

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleAura($lexer, $variable, &$output)
	{
		$spell = $this->profile->SpellName($variable[1]);
		$this->spellList[$variable[1]] = true;

		$prefix = $variable[0];
		if ($prefix == 'dot') {
			$prefix = 'debuff';
		}

		$suffix = $variable[2];

		$value = null;
		switch($suffix) {
			case 'ticking':
			case 'up':
				$value = "{$prefix}[{$spell}].up";
				break;
			case 'down':
				$value = "not {$prefix}[{$spell}].up";
				break;
			case 'charges':
			case 'stack':
			case 'react':
				$value = "{$prefix}[{$spell}].count";
				break;
			case 'duration':
				$value = "{$prefix}[{$spell}].duration";
				break;
			case 'remains':
				$value = "{$prefix}[{$spell}].remains";
				break;
			case 'refreshable':
				$value = "{$prefix}[{$spell}].refreshable";
				break;
			case 'pmultiplier':
				$previousElement = end($output);
				$glimpse = $lexer->glimpse();

				if ($glimpse) {
					$nextVal = $glimpse->getValue();
					$blacklist = ['*', '/', '+', '-', '>', '<', '<=', '>='];

					if (in_array($previousElement, $blacklist)) {
						array_pop($output);
						array_pop($output);
					}

					if (in_array($nextVal, $blacklist)) {
						// skip next
						$lexer->moveNext();
						$lexer->moveNext();
					}
				}

				return;
				break;
			default:
				throw new \Exception(
					'Unrecognized spell/aura suffix type: ' . $suffix . ' expression: ' . implode('.', $variable)
				);
				break;
		}


		$output[] = $value;
	}

	protected function isSpellBlacklisted($spellName)
	{
		//echo $spellName . PHP_EOL;
		return in_array($spellName, [
			'flask', 'food', 'augmentation', 'summon_pet', 'snapshot_stats', 'potion', 'arcane_pulse',
			'lights_judgment', 'arcane_torrent', 'blood_fury', 'berserking', 'fireblood', 'auto_attack',
			'use_items', 'flying_serpent_kick', 'ancestral_call'
		]);
	}
}