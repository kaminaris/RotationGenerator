<?php

namespace Generator\Variable;

use Generator\Action;
use Generator\Profile;

abstract class Handler
{
	public $handledPrefixes = [];

	/** @var Profile */
	public $profile;

	/** @var Action */
	public $action;

	public function __construct(Profile $profile, Action $action)
	{
		$this->profile = $profile;
		$this->action = $action;
	}

	/**
	 * Checks if variable can be converted using this handler
	 *
	 * @param $variableParts
	 * @return bool
	 */
	public function canHandle($variableParts)
	{
		return in_array($variableParts[0], $this->handledPrefixes);
	}

	/**
	 * Converts simcraft variable to lua variable
	 *
	 * @param \Tmilos\Lexer\Lexer $lexer
	 * @param $variableParts
	 * @param $output
	 * @throws \Exception
	 */
	public function handle($lexer, $variableParts, &$output)
	{
		throw new \Exception('Not implemented');
	}
}