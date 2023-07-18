<?php

namespace LuaWriter;

class Element
{
	const TYPE_CONDITION = 'COND';
	const TYPE_FUNCTION = 'FUNC';

	const TYPE_VARIABLE = 'VARIABLE';
	const TYPE_RESULT = 'RESULT';
	const TYPE_ARRAY = 'ARRAY';
	const TYPE_STATEMENT = 'STATEMENT';
	const TYPE_COMMENT = 'COMMENT';
	const TYPE_NEWLINE = 'NEWLINE';

	public $stream;
	public $type;
	public $level;

	public $content = [];

	public function __construct($stream, $level)
	{
		$this->stream = $stream;
		$this->level = $level;
	}

	public function makeChildren()
	{
		$child = new Element($this->stream, $this->level + 1);
		return $child;
	}

	public function makeVariable($name, $value, $operator = 'set')
	{
		$this->type = self::TYPE_VARIABLE;
		$this->content = [
			'variable' => [
				'name' => $name,
				'value' => $value,
				'operator' => $operator
			]
		];
		return $this;
	}

	public function makeStatement($statement)
	{
		$this->type = self::TYPE_STATEMENT;
		$this->content = [
			'statement' => $statement
		];
		return $this;
	}

	public function makeComment($comment)
	{
		$this->type = self::TYPE_COMMENT;
		$this->content = [
			'comment' => $comment
		];
		return $this;
	}

	public function makeNewline()
	{
		$this->type = self::TYPE_NEWLINE;
		return $this;
	}

	public function makeResult($result)
	{
		$this->type = self::TYPE_RESULT;
		$this->content = [
			'result' => $result
		];
		return $this;
	}

	public function makeSpellResult($result)
	{
		$this->type = self::TYPE_CONDITION;
		$this->content = [
			'condition' => [
				'condition' => "MaxDps:FindSpell({$result})",
				'if' => 'if',
				'else' => null,
			]
		];
		return $this;
	}

	public function makeArray($arrayName, $array)
	{
		$this->type = self::TYPE_ARRAY;
		$this->content = [
			'array' => [
				'name' => $arrayName,
				'array' => $array
			]
		];
		return $this;
	}

	public function makeCondition($condition, $if, $else = null)
	{
		$this->type = self::TYPE_CONDITION;
		$this->content = [
			'condition' => [
				'condition' => $condition,
				'if' => $if,
				'else' => $else,
			]
		];
		return $this;
	}

	public function makeFunction($name, $arguments = [], $children)
	{
		$this->type = self::TYPE_FUNCTION;
		$this->content = [
			'function' => [
				'name' => $name,
				'arguments' => $arguments,
				'children' => $children,
			]
		];
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function write()
	{
		switch ($this->type) {
			case self::TYPE_VARIABLE:
				$variable = $this->content['variable'];
				$operator = $variable['operator'];
				if ($operator == 'add') {
					$this->writeLine("local {$variable['name']} = {$variable['name']} + {$variable['value']};", $this->level);
				} elseif ($operator == 'sub') {
					$this->writeLine("local {$variable['name']} = {$variable['name']} - {$variable['value']};", $this->level);
				} else {
					$this->writeLine("local {$variable['name']} = {$variable['value']};", $this->level);
				}
				break;
			case self::TYPE_NEWLINE:
				$this->writeLine('', 0);
				break;
			case self::TYPE_STATEMENT:
				$statement = $this->content['statement'];
				$this->writeLine("{$statement};", $this->level);
				break;
			case self::TYPE_COMMENT:
				$comment = $this->content['comment'];
				$this->writeLine("-- {$comment};", $this->level);
				break;
			case self::TYPE_RESULT:
				$result = $this->content['result'];
				$this->writeLine("return {$result};", $this->level);
				break;
			case self::TYPE_ARRAY:
				$array = $this->content['array'];
				$this->writeLine("local {$array['name']} = {", $this->level);

				foreach ($array['array'] as $key => $value) {
					if (is_string($value)) {
						$value = "'{$value}'";
					}
					$this->writeLine("{$key} = {$value},", $this->level + 1);
				}

				$this->writeLine("};", $this->level);
				break;
			case self::TYPE_CONDITION:
				$condition = $this->content['condition'];
				$this->writeLine("if {$condition['condition']} then", $this->level);

				/** @var Element $children */
				foreach ($condition['if'] as $children) {
					$children->write();
				}

				if (!empty($condition['else'])) {
					$this->writeLine("else", $this->level);

					/** @var Element $children */
					foreach ($condition['else'] as $children) {
						$children->write();
					}
				}

				$this->writeLine("end", $this->level);
				break;
			case self::TYPE_FUNCTION:
				$function = $this->content['function'];
				$arguments = implode(', ', $function['arguments']);
				$this->writeLine("function {$function['name']}({$arguments})", $this->level);

				/** @var Element $children */
				foreach ($function['children'] as $children) {
					$children->write();
				}

				$this->writeLine("end", $this->level);
				break;
			default:
				throw new \Exception('Unrecognized element type: ' . $this->type);
		}
	}

	protected function writeLine($string, $level)
	{
		fputs($this->stream, str_repeat("\t", $level) . $string . PHP_EOL);
	}
}