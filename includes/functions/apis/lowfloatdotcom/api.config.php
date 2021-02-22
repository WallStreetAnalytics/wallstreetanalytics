<?php
//Configure your API here

//This is a scraper example that will pull in data from
//https://www.lowfloat.com/all_with_otcbb/xyz
//https://www.highshortinterest.com/

$APIConfigs['lowfloat'] = array( //rename the array key to something unique if you are adding another api. Name must only be in character set: a-z, A-Z, 0-9
	 'apiKey' => null,
	 'isProviderEnabled' => true,
	 'apiType' => 0, // 0 = Market Data, 1 = Trading
	 'apiName' => 'lowfloat.com',
	 'apiRateLimit' => 0, //0 = unlimited, otherwise QPS limit here
	 'brandLogo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/No_image_available.svg/600px-No_image_available.svg.png',
	 'brandColor' => '#087515',
	 'exposeWebSocket' => false, //no web socket for this
	 'supportedOperations' => [
			'status',
			'custom'
		]
	);
	
$APIConfigs['lowfloat']['webSocketConfig'] = false;
//Set up curl opts for this provider
$APIConfigs['lowfloat']['guzzleopts'] = [
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
	'read_timeout' => 15
];
?>