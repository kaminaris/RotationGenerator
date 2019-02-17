<?php

require_once 'vendor/autoload.php';

$urlPrefix = 'https://raw.githubusercontent.com/simulationcraft/simc/bfa-dev/profiles/Tier23/';

$availableProfiles = [
	'blood'       => 'T23_Death_Knight_Blood.simc',
	'dk_frost'    => 'T23_Death_Knight_Frost.simc',
	'unholy'      => 'T23_Death_Knight_Unholy.simc',
	'havoc'       => 'T23_Demon_Hunter_Havoc.simc',
	'vengeance'   => 'T23_Demon_Hunter_Vengeance.simc',
	'moonkin'     => 'T23_Druid_Balance.simc',
	'feral'       => 'T23_Druid_Feral.simc',
	'bear'        => 'T23_Druid_Guardian.simc',
	'bm'          => 'T23_Hunter_Beast_Mastery.simc',
	'mm'          => 'T23_Hunter_Marksmanship.simc',
	'survi'       => 'T23_Hunter_Survival.simc',
	'arcane'      => 'T23_Mage_Arcane.simc',
	'fire'        => 'T23_Mage_Fire.simc',
	'mage_frost'  => 'T23_Mage_Frost.simc',
	'brewmaster'  => 'T23_Monk_Brewmaster.simc',
	'ww'          => 'T23_Monk_Windwalker.simc',
	'ww_s'        => 'T23_Monk_Windwalker_Serenity.simc',
	'pala_prot'   => 'T23_Paladin_Protection.simc',
	'ret'         => 'T23_Paladin_Retribution.simc',
	'priest_holy' => 'T23_Priest_Holy.simc',
	'shadow'      => 'T23_Priest_Shadow.simc',
	'assa'        => 'T23_Rogue_Assassination.simc',
	'asss_exsq'   => 'T23_Rogue_Assassination_Exsg.simc',
	'outlaw'      => 'T23_Rogue_Outlaw.simc',
	'outlaw_snd'  => 'T23_Rogue_Outlaw_SnD.simc',
	'sub'         => 'T23_Rogue_Subtlety.simc',
	'ele'         => 'T23_Shaman_Elemental.simc',
	'enh'         => 'T23_Shaman_Enhancement.simc',
	'aff'         => 'T23_Warlock_Affliction.simc',
	'demo'        => 'T23_Warlock_Demonology.simc',
	'destro'      => 'T23_Warlock_Destruction.simc',
	'arms'        => 'T23_Warrior_Arms.simc',
	'fury'        => 'T23_Warrior_Fury.simc',
	'warr_prot'   => 'T23_Warrior_Protection.simc',
];

function usage()
{
	echo <<<TEXT
Usage: php convert.php profile [output]

    profile - short name of class/spec or existing file
    output - file to write to (optional, if not provided it will be 'profile.lua')
TEXT;
}

function specList()
{
	global $availableProfiles;
	echo <<<TEXT
Invalid profile, needs to be either existing file or one of:


TEXT;

	foreach ($availableProfiles as $short => $v) {
		echo str_pad($short, 20) . ' - ' . preg_replace('/T23_(.*).simc/', '$1', $v) . PHP_EOL;
	}
}


if ($argc < 2) {
	usage();
	die;
}

$selectedProfile = $argv[1];

if (!file_exists($selectedProfile) && !array_key_exists($selectedProfile, $availableProfiles)) {
	specList();
	die;
}

$profile = new \Generator\Profile();

$outFile = $argv[2] ?? 'result.lua';
if (file_exists($selectedProfile)) {
	$profile->load($selectedProfile);
} else {
	$outFile = $argv[2] ?? $selectedProfile . '.lua';
	$profile->load($urlPrefix . $availableProfiles[$selectedProfile]);
}

$converter = new \Converter\Converter($outFile, $profile);
$converter->convert();