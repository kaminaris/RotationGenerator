<?php

require_once 'vendor/autoload.php';

$urlPrefix = 'https://raw.githubusercontent.com/simulationcraft/simc/bfa-dev/profiles/Tier22/';

$availableProfiles = [
	'blood'       => 'T22_Death_Knight_Blood.simc',
	'dk_frost'    => 'T22_Death_Knight_Frost.simc',
	'unholy'      => 'T22_Death_Knight_Unholy.simc',
	'havoc'       => 'T22_Demon_Hunter_Havoc.simc',
	'vengeance'   => 'T22_Demon_Hunter_Vengeance.simc',
	'moonkin'     => 'T22_Druid_Balance.simc',
	'feral'       => 'T22_Druid_Feral.simc',
	'bear'        => 'T22_Druid_Guardian.simc',
	'bm'          => 'T22_Hunter_Beast_Mastery.simc',
	'mm'          => 'T22_Hunter_Marksmanship.simc',
	'survi'       => 'T22_Hunter_Survival.simc',
	'arcane'      => 'T22_Mage_Arcane.simc',
	'fire'        => 'T22_Mage_Fire.simc',
	'mage_frost'  => 'T22_Mage_Frost.simc',
	'brewmaster'  => 'T22_Monk_Brewmaster.simc',
	'ww'          => 'T22_Monk_Windwalker.simc',
	'ww_s'        => 'T22_Monk_Windwalker_Serenity.simc',
	'pala_prot'   => 'T22_Paladin_Protection.simc',
	'ret'         => 'T22_Paladin_Retribution.simc',
	'priest_holy' => 'T22_Priest_Holy.simc',
	'shadow'      => 'T22_Priest_Shadow.simc',
	'assa'        => 'T22_Rogue_Assassination.simc',
	'asss_exsq'   => 'T22_Rogue_Assassination_Exsg.simc',
	'outlaw'      => 'T22_Rogue_Outlaw.simc',
	'outlaw_snd'  => 'T22_Rogue_Outlaw_SnD.simc',
	'sub'         => 'T22_Rogue_Subtlety.simc',
	'ele'         => 'T22_Shaman_Elemental.simc',
	'enh'         => 'T22_Shaman_Enhancement.simc',
	'aff'         => 'T22_Warlock_Affliction.simc',
	'demo'        => 'T22_Warlock_Demonology.simc',
	'destro'      => 'T22_Warlock_Destruction.simc',
	'arms'        => 'T22_Warrior_Arms.simc',
	'fury'        => 'T22_Warrior_Fury.simc',
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
		echo str_pad($short, 20) . ' - ' . preg_replace('/T22_(.*).simc/', '$1', $v) . PHP_EOL;
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