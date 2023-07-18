<?php

require_once 'vendor/autoload.php';

$urlPrefix = 'https://raw.githubusercontent.com/simulationcraft/simc/dragonflight/profiles/Tier30/';

$availableProfiles = [
	'blood'       => 'T30_Death_Knight_Blood.simc',
	'dk_frost'    => 'T30_Death_Knight_Frost.simc',
	'unholy'      => 'T30_Death_Knight_Unholy.simc',
	'havoc'       => 'T30_Demon_Hunter_Havoc.simc',
	'vengeance'   => 'T30_Demon_Hunter_Vengeance.simc',
	'moonkin'     => 'T30_Druid_Balance.simc',
	'feral'       => 'T30_Druid_Feral.simc',
	'bear'        => 'T30_Druid_Guardian.simc',
	'dev'         => 'T30_Evoker_Devastation.simc',
	'bm'          => 'T30_Hunter_Beast_Mastery.simc',
	'mm'          => 'T30_Hunter_Marksmanship.simc',
	'survi'       => 'T30_Hunter_Survival.simc',
	'arcane'      => 'T30_Mage_Arcane.simc',
	'fire'        => 'T30_Mage_Fire.simc',
	'mage_frost'  => 'T30_Mage_Frost.simc',
	'brewmaster'  => 'T30_Monk_Brewmaster.simc',
	'ww'          => 'T30_Monk_Windwalker.simc',
	'ww_s'        => 'T30_Monk_Windwalker_SEF.simc',
	'pala_prot'   => 'T30_Paladin_Protection.simc',
	'ret'         => 'T30_Paladin_Retribution.simc',
	'shadow'      => 'T30_Priest_Shadow.simc',
	'assa'        => 'T30_Rogue_Assassination.simc',
	'outlaw'      => 'T30_Rogue_Outlaw.simc',
	'sub'         => 'T30_Rogue_Subtlety.simc',
	'ele'         => 'T30_Shaman_Elemental.simc',
	'enh'         => 'T30_Shaman_Enhancement.simc',
	'aff'         => 'T30_Warlock_Affliction.simc',
	'demo'        => 'T30_Warlock_Demonology.simc',
	'destro'      => 'T30_Warlock_Destruction.simc',
	'arms'        => 'T30_Warrior_Arms.simc',
	'fury'        => 'T30_Warrior_Fury.simc',
	'warr_prot'   => 'T30_Warrior_Protection.simc',
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
		echo str_pad($short, 20) . ' - ' . preg_replace('/T26_(.*).simc/', '$1', $v) . PHP_EOL;
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

if (!is_dir('./output')) {
	mkdir('./output', 0777);
}

$outFile = './output/' . ($argv[2] ?? $selectedProfile . '.lua');

if (file_exists($selectedProfile)) {
	$profile->load($selectedProfile);
} else {
	$dateTag = date('Y-m-d_H_i');
	$profileFileName = $availableProfiles[$selectedProfile];

	$tierClassSpec = str_replace(
		['Death_Knight', 'Demon_Hunter'],
		['DeathKnight', 'DemonHunter'],
		$profileFileName
	);

	$structureOutput = [];
	preg_match('/^([a-zA-Z0-9]+)_([a-zA-Z0-9]+)_([a-zA-Z0-9]+)/', $tierClassSpec, $structureOutput);
	$structureOutput[0] = 'output';
	$structurePath = './' . implode('/', $structureOutput);
	if (!is_dir($structurePath)) {
		mkdir($structurePath, 0777, true);
	}

	$outFile = $structurePath . '/' . str_replace('.simc', '_'.$dateTag.'.lua', $profileFileName);

	$content = file_get_contents($urlPrefix . $profileFileName);
	$outT = $structurePath . '/'. str_replace('.simc', '_'.$dateTag.'.simc', $profileFileName);
	file_put_contents($outT, $content);

	$profile->load($content);
}

$converter = new \Converter\Converter($outFile, $profile);
$converter->convert();
