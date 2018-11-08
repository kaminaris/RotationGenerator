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
					case 'interval': break; // ignore intervals
					case 'pct_health': break; // ignore pct_health
					case 'cycle_targets': break; //ignore cycling targets
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
		$spellsFound = [];

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
					if (strpos($value, '.') !== false) {
						$exploded = explode('.', $value);
						$variableType = $exploded[0];

						switch ($variableType) {
							case 'talent': $this->handleTalent($lexer, $exploded, $output, $spellsFound); break;
							case 'azerite': $this->handleAzerite($lexer, $exploded, $output, $spellsFound); break;
							case 'dot':
							case 'buff':
							case 'cooldown':
							case 'debuff': $this->handleAura($lexer, $exploded, $output, $spellsFound); break;
							case 'variable': $this->handleVariable($lexer, $exploded, $output); break;
							case 'prev_gcd': $this->handlePreviousSpell($lexer, $exploded, $output, $spellsFound); break;

							case 'action': $output[] = $value; break; //@TODO
							case 'race': $output[] = $value; break; //@TODO
							case 'target': $output[] = $value; break; //@TODO
							case 'bloodseeker': $output[] = $value; break; //@TODO
							case 'stealthed': $output[] = $variableType; break; //@TODO

							// targets
							case 'spell_targets':
							case 'active_enemies': $output[] = 'targets'; break;

							// resources
							case 'chi': $output[] = $value; break;
							case 'focus': $output[] = $value; break;
							case 'combo_points': $output[] = 'combo'; break;
							case 'energy': $output[] = $value; break;

							case 'next_wi_bomb':
								$spellPrefix = $this->profile->spellPrefix;
								$bombName = Helper::properCase($exploded[1]) . 'Bomb';
								$output[] = "nextWiBomb == {$spellPrefix}.$bombName";
								break; //@TODO

							case 'raid_event':
								$this->handleBlacklisted($lexer, $exploded, $output);
								break;
							default:
								throw new \Exception(
									'Unrecognized variable type: ' . $variableType . ' name: ' . $value . ' expr: ' . $expression
								);
								break;
						}


					} else {
						switch ($value) {
							case 'cp_max_spend':
								$output[] = Helper::camelCase($value);
								break;
							case 'combo_points':
								$output[] = 'combo';
								break;
							case 'spell_targets': $output[] = 'targets'; break;
							case 'active_enemies': $output[] = 'targets'; break;
							case 'full_recharge_time': $output[] = "cooldown[{$this->spellName}].fullRecharge"; break;
							case 'ticking': $output[] = "debuff[{$this->spellName}].up"; break;
							case 'refreshable':
							case 'remains': $output[] = "debuff[{$this->spellName}].{$value}"; break;
							default:
								$output[] = $value;
								break;
						}
					}
					break;
				default:
					//
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
	 * @param $spellsFound
	 * @throws \Exception
	 */
	protected function handleTalent($lexer, $variable, &$output, &$spellsFound)
	{
		$previousElement = end($output);
		$spellsFound[$variable[1]] = true;

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
	 * @param $spellsFound
	 * @throws \Exception
	 */
	protected function handleAzerite($lexer, $variable, &$output, &$spellsFound)
	{
		$spellsFound[$variable[1]] = true;
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
	 * @param $spellsFound
	 * @throws \Exception
	 */
	protected function handlePreviousSpell($lexer, $variable, &$output, &$spellsFound)
	{
		$history = is_numeric($variable[1]) ? intval($variable[1]) : 1;
		$spellSimcName = is_numeric($variable[1]) ? $variable[2] : $variable[1];
		$spell = $this->profile->SpellName($spellSimcName);
		$spellsFound[$spellSimcName] = true;

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

	/**
	 * @param Lexer $lexer
	 * @param $variable
	 * @param $output
	 * @param $spellsFound
	 * @throws \Exception
	 */
	protected function handleAura($lexer, $variable, &$output, &$spellsFound)
	{
		$spell = $this->profile->SpellName($variable[1]);
		$spellsFound[$variable[1]] = true;

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
				$value = "{$prefix}[{$spell}].count";
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
				throw new \Exception('Unrecognized spell/aura suffix type: ' . $suffix);
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