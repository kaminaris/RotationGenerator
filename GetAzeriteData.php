<?php

require_once 'vendor/autoload.php';

$url = 'https://raw.githubusercontent.com/herotc/bfa.herodamage.com/master/src/assets/wow-data/raw/AzeritePower.json';

$client = new \GuzzleHttp\Client(['verify' => false]);
$response = $client->get($url);
$data = json_decode($response->getBody()->getContents());

foreach ($data as $trait) {
	unset($trait->classesId);
	unset($trait->specsId);
	$trait->normalizedName = str_replace(' ', '_', strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $trait->spellName)));
}

file_put_contents('data/azerite.json', json_encode($data, JSON_PRETTY_PRINT));