<?php

namespace Generator\Variable;

use Generator\Action;
use Generator\Profile;

abstract class Handler
{
	public static $availableHandlers = [];
	public static $handledPrefixes = [];

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
	public static function canHandle($variableParts)
	{
		return in_array($variableParts[0], self::$handledPrefixes);
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

	/**
	 * @param Profile $profile
	 * @param Action $action
	 * @return Handler[]
	 */
	public static function getAllHandlers(Profile $profile, Action $action)
	{
		if (!empty(self::$availableHandlers)) {
			return self::$availableHandlers;
		}

		self::$availableHandlers = [
			new DefaultHandler($profile, $action),
			new AuraHandler($profile, $action),
			new AzeriteHandler($profile, $action),
			new CooldownHandler($profile, $action),
			new GcdHandler($profile, $action),
			new ResourceHandler($profile, $action),
			new SpellHistoryHandler($profile, $action),
			new TalentHandler($profile, $action),
			new TargetsHandler($profile, $action),
			new VariableHandler($profile, $action),
		];

		return self::$availableHandlers;
	}
}