<?php

namespace Generator;

use Generator\Variable\Handler;
use Tmilos\Lexer\Config\LexerArrayConfig;
use Tmilos\Lexer\Lexer;

class Action {
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
	 * @param            $line
	 * @param ActionList $actionList
	 *
	 * @return Action
	 * @throws \Exception
	 */
	public static function fromSimcAction($line, $actionList) {
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
			new Variable\EssenceHandler($action->profile, $action),
			new Variable\RuneforgeHandler($action->profile, $action),
			new Variable\SoulbindHandler($action->profile, $action),
			new Variable\ConduitHandler($action->profile, $action),
			new Variable\CovenantHandler($action->profile, $action),
			new Variable\PetHandler($action->profile, $action),
			new Variable\RaidEventHandler($action->profile, $action),
			new Variable\SetBonusHandler($action->profile, $action),
			new Variable\TimeEventHandler($action->profile, $action),
			new Variable\SpellHandler($action->profile, $action),
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
			case 'concentrated_flame':
			case 'bag_of_tricks':
			case 'anima_of_death':
			case 'memory_of_lucid_dreams':
			case 'worldvein_resonance':
			case 'cancel_action':
			case 'cancel_buff':
			case 'invoke_external_buff':
			case 'invoke_power_infusion_0':
			case 'retarget_auto_attack':
			case 'pick_up_fragment':
			case 'out_of_range':
			case 'ripple_in_space':
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
	 *
	 * @throws \Exception
	 */
	public function parseCallRun($action, $exploded) {
		if ($action == 'call_action_list') {
			$this->type = 'call';
		}
		else {
			$this->type = 'run';
		}

		foreach ($exploded as $item) {
			list($name, $value) = $this->parseVar($item);

			switch ($name) {
				case 'name':
					$this->aplToRun = $value;
					break;
				case 'if':
					$this->aplCondition = $this->parseExpression($value);
					break;
				case 'target_if':
					$this->aplCondition = $this->parseExpression($value);
					break;
				default:
					throw new \Exception(
						'Unrecognized call/run command: ' . $name . ' expression: ' . implode(',', $exploded)
					);
					break;
			}
		}
	}

	/**
	 * @param $action
	 * @param $exploded
	 *
	 * @throws \Exception
	 */
	public function parseVariable($action, $exploded) {
		$this->type = 'variable';

		foreach ($exploded as $item) {
			list($name, $value) = $this->parseVar($item);

			switch ($name) {
				case 'name':
					$this->variableName = Helper::camelCase($value);
					break;
				case 'value':
					$this->variableValue = $this->parseExpression($value);
					break;
				case 'value_else':
					print_r($value);
					$this->variableValueElse = $this->parseExpression($value);
					break;
				case 'op':
					$this->variableOperation = $value;
					break;
				case 'condition':
					$this->variableCondition = $this->parseExpression($value);
					break;
				case 'if':
					$this->variableCondition = $this->parseExpression($value);
					break;
				case 'target_if':
					$this->variableCondition = $this->parseExpression($value);
					break;
				case 'default':
					break;
				case 'use_off_gcd':
					break;
				case 'use_while_casting':
					break;
				default:
					throw new \Exception(
						'Unrecognized variable operator: ' . $name . ' expression: ' . implode(',', $exploded)
					);
					break;
			}
		}
	}

	/**
	 * @param $action
	 * @param $exploded
	 *
	 * @throws \Exception
	 */
	public function parseSpell($action, $exploded) {
		//Replacement for spell alias
		switch ($action) {
			case 'bt_rake':
				$action = 'rake';
				break;
			case 'bt_shred':
				$action = 'shred';
				break;
			case 'bt_brutal_slash':
				$action = 'brutal_slash';
				break;
			case 'bt_moonfire':
				$action = 'moonfire';
				break;
			case 'bt_thrash':
				$action = 'thrash';
				break;
			case 'bt_swipe':
				$action = 'swipe';
				break;
			case 'thrash_cat':
				$action = 'thrash';
				break;
			case 'thrash_bear':
				$action = 'thrash';
				break;
			case 'moonfire_cat':
				$action = 'moonfire';
				break;
			case 'swipe_cat':
				$action = 'swipe';
				break;
			case 'swipe_bear':
				$action = 'swipe';
				break;
			case 'berserk_bear':
				$action = 'berserk';
				break;
		}

		$this->spell = $action;
		$this->spellName = $this->profile->SpellName($action);
		$this->spellNameCanonical = Helper::properCaseWithSpaces($action);

		if ($this::isSpellBlacklisted($action)) {
			$this->isBlacklisted = true;

			return;
		}

		if (empty($exploded)) {
			// unconditional spell
			$this->spellCondition = true;
		}
		else {
			foreach ($exploded as $item) {
				list($name, $value) = $this->parseVar($item);

				switch ($name) {
					case 'name':
						$this->spellName = $value;
						break;
					case 'target_if':
						$this->spellTarget = $this->parseExpression($value);
						break;
					case 'if':
						$this->spellCondition = $this->parseExpression($value);
						break;
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
					case 'max_energy': //ignore line_cd
					case 'sec': //ignore line_cd
					case 'op': //ignore op
					case 'value': //ignore op
					case 'only_cwc': //ignore op
					case 'use_off_gcd':
						break; //ignore use_off_gcd
					case 'precast_time':
						break; //ignore precast_time
					case 'type':
						break; //ignore precast_time
					case 'toggle': //ignore op
					case 'mode': //ignore mode
					case 'for_next': //@TODO
					case 'early_chain_if':
					case 'empower_to': //@TODO
					case 'precombat_seconds':
						$this->isBlacklisted = true;
						return;
						break;
					case 'precombat_time':
						$this->isBlacklisted = true;
						return;
						break;
					default:
						throw new \Exception(
							'Unrecognized spell operator: ' .
							$name .
							', action' .
							$action .
							' expression: ' .
							implode(',', $exploded)
						);
						break;
				}
			}
		}

		$additionalConditions = [];
		$spellInfo = $this->profile->spellDb->findByName($action);
		if ($action == "execute"){
			$additionalConditions[] = "canExecute";
		}
		if ($action == "bloodbath" or $action == "crushing_blow"){
			$additionalConditions[] = "MaxDps:FindSpell({$this->spellName})";
		}
		if ($action == "templar_slash"){
			$additionalConditions[] = "MaxDps.Spells[{$this->spellName}]";
		}
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
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function parseVar($part) {
		if (preg_match('/^(\w+)=(.*)$/', $part, $out)) {
			array_shift($out);

			return $out;
		}
		else {
			throw new \Exception('Unrecognized variable part: ' . $part);
		}
	}

	/**
	 * @param $expression
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function parseExpression($expression) {
		$config = new LexerArrayConfig(
			[
				'\\s'      => '',
				'[\\d\.]+' => 'number',
				'[\\w\.]+' => 'variable',

				'\\|'  => 'or',
				'\\?'  => 'what',
				'\\&'  => 'and',
				'\\!'  => 'not',
				'\\<=' => 'lteq',
				'\\>=' => 'gteq',
				'\\<'  => 'lt',
				'\\>'  => 'gt',

				'\\+' => 'plus',
				'-'   => 'minus',
				'\\*' => 'mul',
				'\\%' => 'mod',
				'/'   => 'div',
				'\\=' => 'eq',
				'\\:' => 'semicolon',

				'\\(' => 'open',
				'\\)' => 'close',
			]
		);

		$lexer = new Lexer($config);
		$lexer->setInput($expression);
		$lexer->moveNext();

		$output = [];

		while ($lookAhead = $lexer->getLookahead()) {
			$name = $lookAhead->getName();
			$value = $lookAhead->getValue();

			$previousElement = end($output);

			switch ($name) {
				case 'and':
					$output[] = 'and';
					break;
				case 'or':
					$output[] = 'or';
					break;
				case 'not':
					$output[] = 'not';
					break;
				case 'eq':
					$output[] = '==';
					break;
				case 'what':
					$output[] = '?';
					break;
				case 'div':
				case 'mod':
					$output[] = '/';
					break;
				case 'plus':
				case 'minus':
				case 'mul':
				case 'lteq':
				case 'gteq':
				case 'lt':
				case 'gt':
				case 'open':
				case 'close':
				case 'number':
				case 'semicolon':
				case 'demon_soul_fragments':
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

	public static function isSpellBlacklisted($spellName) {
		return in_array(
			$spellName, [
			'flask', 'food', 'augmentation', 'summon_pet', 'snapshot_stats', 'potion', 'arcane_pulse',
			'lights_judgment', 'arcane_torrent', 'blood_fury', 'berserking', 'fireblood', 'auto_attack',
			'use_items', 'flying_serpent_kick', 'ancestral_call', 'auto_shot', 'bloodlust',
			'mind_freeze', 'strangulate', 'skull_bash', 'solar_beam', 'counter_shot', 'counterspell',
			'spear_hand_strike', 'rebuke', 'silence', 'kick', 'wind_shear', 'spell_lock', 'optical_blast',
			'pummel', 'quell',
			'shadowmeld', 'pool_resource', 'wait', 'guardian_of_azeroth', 'focused_azerite_beam',
			'essence_of_the_focusing_iris', 'reaping_flames', 'purifying_blast', 'blood_of_the_enemy',
			'the_unbound_force', 'reckless_force', 'reckless_force_counter', 'memory_of_lucid_dreams',
			'charge', 'roll', 'chi_torpedo', 'blink', 'heroic_leap'
		]
		);
	}
}