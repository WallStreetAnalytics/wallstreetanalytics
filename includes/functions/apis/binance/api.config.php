<?php
//Configure your API here
//Visit fintel.io and subscribe to get an API key

$APIConfigs['binanceapi'] = array( //rename the array key to something unique if you are adding another api. Name must only be in character set: a-z, A-Z, 0-9
	 'apiKey' => '',
	 'isProviderEnabled' => true, //this should be false in distro since it requires a customer API key
	 'apiType' => 0, // 0 = Market Data, 1 = Trading
	 'apiName' => 'Binance - websocket testing only',
	 'apiRateLimit' => 0, //0 = unlimited, otherwise QPS limit here
	 'brandLogo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/Binance_logo.svg/1024px-Binance_logo.svg.png',
	 'brandColor' => '#f3ba2f',
	 'exposeWebSocket' => false, //no web socket for this
	 'supportedOperations' => [
			'status',
			'wstest'
		],
	);
	
$APIConfigs['binanceapi']['webSocketConfig'] = [
	'proxy' => 'tcp://127.0.0.1:24000'
	];

$APIConfigs['binanceapi']['guzzleopts'] = [
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