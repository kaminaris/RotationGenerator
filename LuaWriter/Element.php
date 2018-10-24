<?php

namespace LuaWriter;

class Element
{
	const TYPE_CONDITION = 'COND';
	const TYPE_FUNCTION = 'FUNC';
	const TYPE_RESULT = 'RESULT';

	public $type;
	public $level;

	// for function
	public $children;

	public function __construct($type)
	{
		$this->type = $this;
	}

	public function write($stream)
	{

	}
}