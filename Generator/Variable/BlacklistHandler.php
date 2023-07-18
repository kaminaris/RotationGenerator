<?php

namespace Generator\Variable;

class BlacklistHandler extends Handler
{
	public $handledPrefixes = [
		'min', 'max', 'movement', 'sim', 'travel_time', 'trinket', 'expected_combat_length',
		'in_flight', 'incoming_imps', 'self', 'interpolated_fight_remains', 'main_hand', 'fight_style', 'active_bt_triggers',
		'ticks_gained_on_refresh', 'steady_focus_count', 'ca_active', 'bloodseeker', 'mana_gem_charges', 'dbc', 'searing_touch',
		'hot_streak_spells_in_flight', 'hyperthread_wristwraps', 'expected_kindling_reduction', 'level', 'remaining_winters_chill',
		'spinning_crane_kick', 'time_to_hpg', 'exsanguinated', 'master_assassin_remains', 'priority_rotation', 'will_lose_exsanguinate',
		'pmultiplier', 'tick_time', 'exsanguinated_rate', 'improved_garrote_remains', 'ptr', 'time_to_sht', 'ti_lightning_bolt',
		'ti_chain_lightning', 'alpha_wolf_min_remains', 'ti_lightning_bolt', 'used_for_danse', 'enemies', 't30_2pc_timer',
        'two_cast_imps'
	];

	public function handle($lexer, $variableParts, &$output)
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
}