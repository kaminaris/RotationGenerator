<?php

namespace Generator;

use Generator\Spell\SpellDb;

class SpellList
{
	/** @var Spell[] */
	public $spellList = [];

	/** @var SpellDb */
	public $spellDb;

	public function __construct($spellDb)
	{
		$this->spellDb = $spellDb;
	}

	/**
	 * @param $spellSimcName
	 * @throws \Exception
	 */
	public function addSpell($spellSimcName)
	{

		switch ($spellSimcName) {
			case 'bt_rake':
				$spellSimcName = 'rake';
				break;
			case 'bt_shred':
				$spellSimcName = 'shred';
				break;
			case 'bt_brutal_slash':
				$spellSimcName = 'brutal_slash';
				break;
			case 'bt_moonfire':
				$spellSimcName = 'moonfire';
				break;
			case 'bt_thrash':
				$spellSimcName = 'thrash';
				break;
			case 'bt_swipe':
				$spellSimcName = 'swipe';
				break;
			case 'thrash_cat':
				$spellSimcName = 'thrash';
				break;
			case 'thrash_bear':
				$spellSimcName = 'thrash';
				break;
			case 'moonfire_cat':
				$spellSimcName = 'moonfire';
				break;
			case 'swipe_cat':
				$spellSimcName = 'swipe';
				break;
			case 'swipe_bear':
				$spellSimcName = 'swipe';
				break;
			case 'berserk_bear':
				$spellSimcName = 'berserk';
				break;
		}
		foreach ($this->spellList as $spell) {
			if ($spell->spellSimcName == $spellSimcName) {
				return; // Already added
			}
		}
		$info = $this->spellDb->findByName($spellSimcName);
		$info2 = $this->spellDb->findByName(Helper::properCaseWithSpaces($spellSimcName));
		
		if ($spellSimcName == "execute"){
			$spell = new Spell("massacre");
			$spell->info = $this->spellDb->findByName("massacre");
			$this->spellList[] = $spell;
			$spell = new Spell("sudden_death_aura");
			$spell->info = $this->spellDb->findByName("sudden_death_aura");
			$this->spellList[] = $spell;
		}

		if ($info or $info2) {
			$spell = new Spell($spellSimcName);
			$spell->info = $info;
			$this->spellList[] = $spell;
		} else {
			if (strpos($spellSimcName, 'BL.') !== false) {
				throw new \Exception('Spell prefix is invalid');
			}

			if (!Action::isSpellBlacklisted($spellSimcName)) {
				echo 'Unknown spell: ' . $spellSimcName . PHP_EOL;

				#$data_a=file_get_contents("output/current.txt");
				#$data_a=trim($data_a);
				#$lines = file("output/{$data_a}.txt", FILE_IGNORE_NEW_LINES);
				#$linefound = false;
				#foreach ($lines as $line_num => $line) {
				#	if($line == "Unknown spell:  $spellSimcName"){
				#		$linefound = true;
				#	}
				#}
				#if (!$linefound){
				#    $myfile = fopen("output/{$data_a}.txt", "a") or die("Unable to open file!");
				#    $txt = "Unknown spell:  $spellSimcName\n";
                #    fwrite($myfile, $txt);
                #    fclose($myfile);
				#}
			}
		}

	}

	public function toArray()
	{
		$output = [];
		foreach ($this->spellList as $spell) {
			$output[str_replace(' ', '', $spell->spellName)] = $spell->info->id;
		}

		return $output;
	}
}