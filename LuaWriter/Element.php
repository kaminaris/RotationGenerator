<?php

namespace LuaWriter;

class Element
{
	const TYPE_CONDITION = 'COND';
	const TYPE_FUNCTION = 'FUNC';

	const TYPE_VARIABLE = 'VARIABLE';
	const TYPE_RESULT = 'RESULT';
	const TYPE_ARRAY = 'ARRAY';

	public $stream;
	public $type;
	public $level;

	public $content = [];
	// for function
	public $children;

	public function __construct($type, $stream, $level)
	{
		$this->type = $this;
		$this->level = $level;
	}

	public function makeChildren($type)
	{
		$child = new Element($type, $this->stream, $this->level + 1);
		return $child;
	}

	public function makeVariable($name, $value)
	{
		$this->content = [
			'variable' => [
				'name' => $name,
				'value' => $value
			]
		];
	}

	public function makeArray($arrayName, $array)
	{
		$this->content = [
			'array' => [
				'name' => $arrayName,
				'array' => $array
			]
		];
	}

	public function makeCondition($condition, $if, $else)
	{
		$this->content = [
			'condition' => [
				'condition' => $condition,
				'if' => $if,
				'else' => $else,
			]
		];
	}

	public function write()
	{
		switch ($this->type) {
			case self::TYPE_VARIABLE:
				$variable = $this->content['variable'];
				$this->writeLine("local {$variable['name']} = {$variable['value']};", $this->level);
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

		}
	}

	protected function writeLine($string, $level)
	{
		fputs($this->stream, str_repeat("\t", $level) . $string . PHP_EOL);
	}
}