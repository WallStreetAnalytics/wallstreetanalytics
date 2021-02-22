<?php
//Configure your API here
//Visit polygon.io to get an API key

$APIConfigs['polygonapi'] = array( //rename the array key to something unique if you are adding another api. Name must only be in character set: a-z, A-Z, 0-9
	 'apiKey' => '',
	 'isProviderEnabled' => true, //this should be false in distro since it requires a customer API key
	 'apiType' => 0, // 0 = Market Data, 1 = Trading
	 'apiName' => 'Polygon.io',
	 'apiRateLimit' => 0, //0 = unlimited, otherwise QPS limit here
 	 'brandLogo' => 'https://polygon.io/imgs/brand/logo_color.svg?auto=format&w=1200',
  	 'brandColor' => '5f5cff',
   	 'exposeWebSocket' => true,
	 'supportedOperations' => [
			'getws',
			'status',
			'financials',
			'corpdetails',
			'agg',
			'news',
			'historyqt',
			'historytxn',
			'ticker',
			'custom'
		]
	);
	
$APIConfigs['polygonapi']['webSocketConfig'] = [
	'stocks' => [
		'delayed' => 'wss://delayed.polygon.io/stocks', //stocks only for now
		'realtime' => 'wss://socket.polygon.io/stocks',
		'availableStreams' => [
			'auth' => '{"action":"auth","params":"'.$APIConfigs['polygonapi']['apiKey'].'"}',
			'quotes' => '{"action":"subscribe", "params":"Q.*"}',
			'trades' => '{"action":"subscribe", "params":"T.*"}',
			'agg' => '{"action":"subscribe", "params":"A.*"}',
			'aggbyminute' => '{"action":"subscribe", "params":"AM.*"}',
			'luld' => '{"action":"subscribe", "params":"LULD.*"}',
			'imbalances' => '{"action":"subscribe", "params":"NOI.*"}'
		],
		'responseAttributeNames' => [
			'auth' => ['ev' => 'event', 'status' => 'status', 'message' => 'message'],
			'quotes' => ['ev' => 'event', 'sym' => 'Symbol', 'x' => 'Exchange ID', 'i' => 'Trade ID', 'z' => 'Tape', 'p' => 'Price', 's' => 'Size', 't' => 'Time (Unix)'],
			'trade' => ['ev' => 'event', 'sym' => 'Symbol', 'bx' => 'Bid Exchange ID', 'bp' => 'Bid Price', 'bs' => 'Bid Size', 
						'ax' => 'Ask Exchange ID', 'ap' => 'Ask Price', 'as' => 'Ask Size', 'c' => 'Condition', 't' => 'Time (Unix)'],
			'agg' => ['ev' => 'event', 'sym' => 'Symbol', 'v' => 'Tick Volume', 'av' => 'Accumulated Volume', 'op' => 'Open Price (Today)', 
					'vw' => 'Volume Weighted Price (Today)', 'o' => 'Open Price (Window)', 'c' => 'Closing Price (Window)', 'h' => 'High Price (Window)', 'l' => 'Low Price (Window)',
					 'a' => 'Volume Weighted Price (Window)', 'z' => 'Avg. Trade Size (Window)', 's' => 'Strating Time (Unix)', 'e' => 'Ending Time (Unix)'],
			'aggbyminute' => ['ev' => 'event', 'sym' => 'Symbol', 'v' => 'Tick Volume', 'av' => 'Accumulated Volume', 'op' => 'Open Price (Today)', 
								'vw' => 'Volume Weighted Price (Today)', 'o' => 'Open Price (Window)', 'c' => 'Closing Price (Window)', 'hlp' => 'Help! I\'m trapped in the source code!', 
								'h' => 'High Price (Window)', 'l' => 'Low Price (Window)', 'a' => 'Volume Weighted Price (Window)', 'z' => 'Avg. Trade Size (Window)', 
								's' => 'Strating Time (Unix)', 'e' => 'Ending Time (Unix)'],
			'luld' => ['ev' => 'event', 'sym' => 'Symbol', 'h' => 'High Price', 'l' => 'Low Price', 'z' => 'Tape', 'q' => 'Sequence Num', 't' => 'Time (Unix)'],
			'imbalances' => ['ev' => 'event', 'T' => 'Symbol', 't' => 'Time (Unix)', 'at' => 'Auction Time (EST)', 'a' => 'Auction Type', 
							'i' => 'Symbol Sequence', 'x' => 'Exchange ID', 'o' => 'Imbalance Quantity', 'p' => 'Paired Quantity', 'b' => 'Book Clearing Price'],
		]
	],
	'forex' => [
		'delayed' => null,
		'realtime' => 'wss://socket.polygon.io/forex',
		'availableStreams' => [
			'auth' => '{"action":"auth","params":"'.$APIConfigs['polygonapi']['apiKey'].'"}',
			'quotes' => '{"action":"subscribe", "params":"CA.*"}',
			'aggbyminute' => '{"action":"subscribe", "params":"XT.*"}',
		],
		'responseAttributeNames' => [ 'Someone Please Define These!' ]
	],
	'crypto' => [
		 'delayed' => null, //stocks only for now
		 'realtime' => 'wss://socket.polygon.io/crypto',
		'availableStreams' => [
			'auth' => '{"action":"auth","params":"'.$APIConfigs['polygonapi']['apiKey'].'"}',
			'quotes' => '{"action":"subscribe", "params":"XQ.*"}',
			'trades' => '{"action":"subscribe", "params":"XT.*"}',
			'level2book' => '{"action":"subscribe", "params":"XL2.*"}',
			'aggbyminute' => '{"action":"subscribe", "params":"XA.*"}'
		],
		'responseAttributeNames' => [ 'Someone Please Define These!' ]
	]
 ];
?>