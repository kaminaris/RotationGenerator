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

	public function makeVariable($name, $value)
	{
		$this->type = self::TYPE_VARIABLE;
		$this->content = [
			'variable' => [
				'name' => $name,
				'value' => $value
			]
		];
	}

	public function makeStatement($statement)
	{
		$this->type = self::TYPE_STATEMENT;
		$this->content = [
			'statement' => $statement
		];
	}

	public function makeResult($result)
	{
		$this->type = self::TYPE_RESULT;
		$this->content = [
			'result' => $result
		];
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
	}

	/**
	 * @throws \Exception
	 */
	public function write()
	{
		switch ($this->type) {
			case self::TYPE_VARIABLE:
				$variable = $this->content['variable'];
				$this->writeLine("local {$variable['name']} = {$variable['value']};", $this->level);
				break;
			case self::TYPE_STATEMENT:
				$variable = $this->content['statement'];
				$this->writeLine("{$variable};", $this->level);
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