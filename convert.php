<?php

require_once 'vendor/autoload.php';

$urlPrefix = 'https://raw.githubusercontent.com/simulationcraft/simc/shadowlands/profiles/PreRaids/';

$availableProfiles = [
	'blood'       => 'PR_Death_Knight_Blood.simc',
	'dk_frost'    => 'PR_Death_Knight_Frost.simc',
	'unholy'      => 'PR_Death_Knight_Unholy.simc',
	'havoc'       => 'PR_Demon_Hunter_Havoc.simc',
	'vengeance'   => 'PR_Demon_Hunter_Vengeance.simc',
	'moonkin'     => 'PR_Druid_Balance.simc',
	'feral'       => 'PR_Druid_Feral.simc',
	'bear'        => 'PR_Druid_Guardian.simc',
	'bm'          => 'PR_Hunter_Beast_Mastery.simc',
	'mm'          => 'PR_Hunter_Marksmanship.simc',
	'survi'       => 'PR_Hunter_Survival.simc',
	'arcane'      => 'PR_Mage_Arcane.simc',
	'fire'        => 'PR_Mage_Fire.simc',
	'mage_frost'  => 'PR_Mage_Frost.simc',
	'brewmaster'  => 'PR_Monk_Brewmaster.simc',
	'ww'          => 'PR_Monk_Windwalker.simc',
	'ww_s'        => 'PR_Monk_Windwalker_Serenity.simc',
	'pala_prot'   => 'PR_Paladin_Protection.simc',
	'ret'         => 'PR_Paladin_Retribution.simc',
	'priest_holy' => 'PR_Priest_Holy.simc',
	'shadow'      => 'PR_Priest_Shadow.simc',
	'assa'        => 'PR_Rogue_Assassination.simc',
	'asss_exsq'   => 'PR_Rogue_Assassination_Exsg.simc',
	'outlaw'      => 'PR_Rogue_Outlaw.simc',
	'outlaw_snd'  => 'PR_Rogue_Outlaw_SnD.simc',
	'sub'         => 'PR_Rogue_Subtlety.simc',
	'ele'         => 'PR_Shaman_Elemental.simc',
	'enh'         => 'PR_Shaman_Enhancement.simc',
	'aff'         => 'PR_Warlock_Affliction.simc',
	'demo'        => 'PR_Warlock_Demonology.simc',
	'destro'      => 'PR_Warlock_Destruction.simc',
	'arms'        => 'PR_Warrior_Arms.simc',
	'fury'        => 'PR_Warrior_Fury.simc',
	'warr_prot'   => 'PR_Warrior_Protection.simc',
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
		echo str_pad($short, 20) . ' - ' . preg_replace('/PR_(.*).simc/', '$1', $v) . PHP_EOL;
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