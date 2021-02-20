<?php
//Configure your API here
//THIS IS NOT FOR NASDAQ DATAONDEMAND! This is a scraper for api.nasdaq.com's publicly available data.

$APIConfigs['nasdaqapi'] = array( //rename the array key to something unique if you are adding another api. Name must only be in character set: a-z, A-Z, 0-9
	 'apiKey' => null,
	 'isProviderEnabled' => true,
	 'apiType' => 0, // 0 = Market Data, 1 = Trading
	 'apiName' => 'api.nasdaq.com',
	 'apiRateLimit' => 0, //0 = unlimited, otherwise QPS limit here
	 'brandLogo' => 'https://dataondemand.nasdaq.com/docs/images/logo.png',
	 'brandColor' => '#009fc2',
	 'exposeWebSocket' => false, //no web socket for this provider
	 'supportedOperations' => [
			'status',
			'ownership', //"institutional holdings"
			'instholdings', //"institution details"
			'insiders',
		//	'shortvol',
			'filings',
			'activists', //this requires a personal installation
			'historyownership', //this requires a personal installation
			'status',
			'financials',
			'corpdetails',
			'agg',
			'news',
		//	'quote',
			'ticker',
		//	'symbolsearchautocomplete',		
			'custom'
		]
	);
	
$APIConfigs['nasdaqapi']['webSocketConfig'] = false;
//Set up curl opts for this provider
$APIConfigs['nasdaqapi']['guzzleopts'] = [
	'headers' => [
		'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:84.0) Gecko/20100101 Firefox/84.0',
		'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Accept-Language' => 'en-US,en;q=0.5',
		'Upgrade-Insecure-Requests' => '1',
		'Connection' => 'keep-alive',
		'Cache-Control' => 'max-age=0'
	],
	'proxy' => 'http://127.0.0.1:24000', //optional proxy, advisable.
	'verify' => false,
	'synchronous' => false,
	'connect_timeout' => 5,
	'timeout' => 30,
	'read_timeout' => 15 //?
];
?>