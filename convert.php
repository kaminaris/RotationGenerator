<?php

require_once 'vendor/autoload.php';

$urlPrefix = 'https://raw.githubusercontent.com/simulationcraft/simc/shadowlands/profiles/Tier26/';

$availableProfiles = [
	'blood'       => 'T26_Death_Knight_Blood.simc',
	'dk_frost'    => 'T26_Death_Knight_Frost.simc',
	'unholy'      => 'T26_Death_Knight_Unholy.simc',
	'havoc'       => 'T26_Demon_Hunter_Havoc.simc',
	'vengeance'   => 'T26_Demon_Hunter_Vengeance.simc',
	'moonkin'     => 'T26_Druid_Balance.simc',
	'feral'       => 'T26_Druid_Feral.simc',
	'bear'        => 'T26_Druid_Guardian.simc',
	'bm'          => 'T26_Hunter_Beast_Mastery.simc',
	'mm'          => 'T26_Hunter_Marksmanship.simc',
	'survi'       => 'T26_Hunter_Survival.simc',
	'arcane'      => 'T26_Mage_Arcane.simc',
	'fire'        => 'T26_Mage_Fire.simc',
	'mage_frost'  => 'T26_Mage_Frost.simc',
	'brewmaster'  => 'T26_Monk_Brewmaster.simc',
	'ww'          => 'T26_Monk_Windwalker.simc',
	'ww_s'        => 'T26_Monk_Windwalker_Serenity.simc',
	'pala_prot'   => 'T26_Paladin_Protection.simc',
	'ret'         => 'T26_Paladin_Retribution.simc',
	'priest_holy' => 'T26_Priest_Holy.simc',
	'shadow'      => 'T26_Priest_Shadow.simc',
	'assa'        => 'T26_Rogue_Assassination.simc',
	'asss_exsq'   => 'T26_Rogue_Assassination_Exsg.simc',
	'outlaw'      => 'T26_Rogue_Outlaw.simc',
	'outlaw_snd'  => 'T26_Rogue_Outlaw_SnD.simc',
	'sub'         => 'T26_Rogue_Subtlety.simc',
	'ele'         => 'T26_Shaman_Elemental.simc',
	'enh'         => 'T26_Shaman_Enhancement.simc',
	'aff'         => 'T26_Warlock_Affliction.simc',
	'demo'        => 'T26_Warlock_Demonology.simc',
	'destro'      => 'T26_Warlock_Destruction.simc',
	'arms'        => 'T26_Warrior_Arms.simc',
	'fury'        => 'T26_Warrior_Fury.simc',
	'warr_prot'   => 'T26_Warrior_Protection.simc',
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