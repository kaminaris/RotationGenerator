<?php

namespace Generator;

use Tmilos\Lexer\Config\LexerArrayConfig;
use Tmilos\Lexer\Lexer;

class Action
{
	public $allowedActionTypes = ['spell', 'run', 'call', 'variable'];

	public $type;

	public $spell;
	public $spellName; // Canonical spell name like Shadow Word: Pain
	public $spellId;
	public $spellCondition; // Lexed spell condition

	public $aplToRun;
	public $aplCondition; // Lexed spell condition

	public $variableName;
	public $variableOperator;
	public $variableValue;
	public $variableValueElse;
	public $variableCondition;

	/**
	 * @param $line
	 * @return Action
	 * @throws \Exception
	 */
	public function parse($line)
	{
		if (preg_match('/^([\w]+),?(.*)$/', $line, $output)) {
			$action = $output[1];
			$directive = $output[2];

			switch ($action) {
				case 'call_action_list':
				case 'run_action_list':
					$this->parseCallRun($action, $directive);
					break;
				case 'variable':
					$this->parseVariable($action, $directive);
					break;
				default:
					$this->type = 'spell';
					$this->parseSpell($action, $directive);
					break;

			}

		} else {
			throw new \Exception('Unrecognized action: ' . $line);
		}

		return $this;
	}

	/**
	 * @param $action
	 * @param $expression
	 * @throws \Exception
	 */
	public function parseCallRun($action, $expression)
	{
		if ($action == 'call_action_list') {
			$this->type = 'call';
		} else {
			$this->type = 'run';
		}

		if (preg_match('/^name=([\w]+),?i?f?=?(.*)$/', $expression, $output)) {
			$this->aplToRun = $output[1];
			$this->aplCondition = empty($output[2]) ? null : $this->parseExpression($output[2]);
		} else {
			throw new \Exception('Unrecognized call/run command: ' . $expression);
		}
	}

	/**
	 * @param $action
	 * @param $expression
	 * @throws \Exception
	 */
	public function parseVariable($action, $expression)
	{
		$this->type = 'variable';

		$exploded = explode(',', $expression);

		foreach ($exploded as $item) {
			if (preg_match('/^(\w+)=(.*)$/', $item, $out)) {
				$name = $out[1];
				$val = $out[2];

				switch ($name) {
					case 'name': $this->variableName = $val; break;
					case 'value': $this->variableValue = $this->parseExpression($val); break;
					case 'value_else': $this->variableValueElse = $this->parseExpression($val); break;
					case 'op': $this->variableOperator = $val; break;
					case 'condition': $this->variableCondition = $this->parseExpression($val); break;
					default:
						throw new \Exception('Unrecognized variable operator: ' . $name . ' expression: '. $expression);
						break;
				}
			} else {
				throw new \Exception('Unrecognized variable command: ' . $expression);
			}
		}
	}

	/**
	 * @param $action
	 * @param $expression
	 * @throws \Exception
	 */
	public function parseSpell($action, $expression)
	{
		$this->spell = $action;
		if (empty($expression)) {
			// unconditional spell
			$this->spellCondition = true;
		} else {
			if (preg_match('/^if=(.*)$/', $expression, $output)) {
				$this->spellCondition = $this->parseExpression($output[1]);
			} elseif (preg_match('/^target_if=(.*)$/', $expression, $output)) {

				$this->spellCondition = $this->parseExpression($output[1]);
			} else {
				throw new \Exception('Unrecognized call/run command: ' . $expression);
			}
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
			'\\d+' => 'number',
			'[\\w\.]+' => 'variable',

			'\\+' => 'plus',
			'-' => 'minus',
			'\\*' => 'mul',
			'/' => 'div',
			'\\=' => 'eq',
			'\\:' => 'semicolon',

			'\\|' => 'or',
			'\\&' => 'and',
			'\\!' => 'not',
			'\\<' => 'lt',
			'\\>' => 'gt',

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
				case 'div':
				case 'lt':
				case 'gt':
				case 'open':
				case 'close':
				case 'number':
				case 'semicolon':
					$output[] = $value;
					break;
				case 'variable':
					if ($value == 'blacklisted_variable') {
						if ($previousElement == 'and' || $previousElement == 'or') {
							array_pop($output);
						}
					} elseif (strpos($value, '.') !== false) {
						$exploded = explode('.', $value);
						$variableType = $exploded[0];

						switch ($variableType) {
							case 'talent': $this->handleTalent($lexer, $exploded, $output); break;
							case 'azerite': $this->handleAzerite($lexer, $exploded, $output); break;
							case 'dot':
							case 'buff':
							case 'cooldown':
							case 'debuff': $this->handleAura($lexer, $exploded, $output); break;
							case 'next_wi_bomb': $output[] = $value; break; //@TODO
							case 'focus': $output[] = $value; break; //@TODO
							case 'action': $output[] = $value; break; //@TODO
							case 'race': $output[] = $value; break; //@TODO
							case 'target': $output[] = $value; break; //@TODO
							default:
								throw new \Exception('Unrecognized variable type: ' . $variableType . ' name: ' . $value);
								break;
						}


					} else {
						$output[] = $value;
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
	 * @throws \Exception
	 */
	protected function handleTalent($lexer, $variable, &$output)
	{
		$previousElement = end($output);
		$talentName = Helper::pascalCase($variable[1]);
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
		$spell = Helper::pascalCase($variable[1]);
		$suffix = $variable[2];

		$value = null;
		switch($suffix) {
			case 'rank':
				$value = "azerite[{$spell}]";
				break;
			case 'enabled':
				$value = "(azerite[{$spell}] > 0 and true or false)";
				break;
			default:
				throw new \Exception('Unrecognized azerite suffix type: ' . $suffix);
				break;
		}

		$output[] = $value;
	}

	/**
	 * @param $lexer
	 * @param $variable
	 * @param $output
	 * @throws \Exception
	 */
	protected function handleAura($lexer, $variable, &$output)
	{
		$prefix = $variable[0];
		if ($prefix == 'dot') {
			$prefix = 'debuff';
		}

		$spell = Helper::pascalCase($variable[1]);
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
			case 'stack':
				$value = "{$prefix}[{$spell}].count";
				break;
			case 'remains':
				$value = "{$prefix}[{$spell}].remains";
				break;
			case 'refreshable':
				$value = "{$prefix}[{$spell}].refreshable";
				break;
			default:
				throw new \Exception('Unrecognized spell/aura suffix type: ' . $suffix);
				break;
		}


		$output[] = $value;
	}
}