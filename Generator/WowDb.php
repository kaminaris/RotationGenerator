<?php

namespace Generator;

use GuzzleHttp\Client;

class WowDb
{
	const API_ENDPOINT = 'https://www.wowdb.com/';

	protected $client;

	public function getClient()
	{
		if ($this->client) {
			return $this->client;
		}

		$this->client = new Client([
			'base_uri' => self::API_ENDPOINT,
			'verify' => false
		]);

		return $this->client;
	}

	public function makeApiCall($path, $query = null)
	{
		$options = [];
		if (is_array($query)) {
			$options['query'] = $query;
		}

		$response = $this->getClient()->get($path, $options);

		$content = $response->getBody()->getContents();

		if ($content[0] == '(') {
			$content = trim($content, '()');
		}

		if ($content[0] == '"') {
			eval('$content = '.$content.';');
		}

		return json_decode($content);
	}

	public function find($query)
	{
		return $this->makeApiCall('find', ['q' => $query])->Data;
	}

	public function findSpells($name)
	{
		$result = $this->find($name);
		$spellList = [];
		foreach ($result as $entry) {
			if (preg_match('/spells\/(\d+)/', $entry->Url, $matches)) {
				$spellList[] = $matches[1];
			}
		}

		$spellList = array_unique($spellList);

		$output = [];
		foreach ($spellList as $spellId) {
			$spellId = (int)$spellId;
			$output[$spellId] = $this->spell($spellId);
		}

		return $output;
	}

	public function spell($spellId)
	{
		return $this->makeApiCall('api/spell/' . $spellId);
	}
}