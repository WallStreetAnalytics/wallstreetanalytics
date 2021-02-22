<?php
//Configure your API here
//Visit fintel.io and subscribe to get an API key

$APIConfigs['fintelapi'] = array( //rename the array key to something unique if you are adding another api. Name must only be in character set: a-z, A-Z, 0-9
	 'apiKey' => '',
	 'isProviderEnabled' => true, //this should be false in distro since it requires a customer API key
	 'apiType' => 0, // 0 = Market Data, 1 = Trading
	 'apiName' => 'Fintel.io',
	 'apiRateLimit' => 0, //0 = unlimited, otherwise QPS limit here
	 'brandLogo' => 'https://pbs.twimg.com/profile_images/1044647076770963456/ynYAFLm9.jpg',
	 'brandColor' => '#087515',
	 'exposeWebSocket' => false, //no web socket for this
	 'supportedOperations' => [
			'status',
			'ownership', //for corp installs, set isPersonalInstallation to false in accordance with fintel API license
			'insiders',
			'shortvol',
			'filings',
			'activists', //this requires a personal installation
			'historyownership', //this requires a personal installation
			'custom'
		]
	);
	
$APIConfigs['fintelapi']['webSocketConfig'] = false;
//Set up curl opts for this provider
$APIConfigs['fintelapi']['guzzleopts'] = [
	'headers' => [
		'X-API-KEY' => $APIConfigs['fintelapi']['apiKey']
	],
	'stream' => true,
	'connect_timeout' => 5,
	'read_timeout' => 15
];
?>