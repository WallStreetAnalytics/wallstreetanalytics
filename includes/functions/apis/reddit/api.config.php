<?php
//reddit
//part of this code is based on https://github.com/iam-abbas/Reddit-Stock-Trends/

$APIConfigs['redditapi'] = array( //rename the array key to something unique if you are adding another api. Name must only be in character set: a-z, A-Z, 0-9
	 'apiKey' => '',//yourAPIKey
	 'appId' => '',//yourAPIKey
	 'isProviderEnabled' => true, //this should be false in distro since it requires a customer API key
	 'apiType' => 2, // 0 = Market Data, 1 = Trading, 2 = social - this var is currently unused
	 'apiName' => 'Reddit',
	 'apiRateLimit' => '60', //0 = unlimited, otherwise QPS limit here
	 'brandLogo' => 'https://www.redditinc.com/assets/images/site/logo.svg',
	 'brandColor' => 'FF5700',
	 'exposeWebSocket' => false, //no websock for reddit
	 'supportedOperations' => [
			'status',
			'sentiment',
			'mentions',
			'redditorprofiler',
			'cronworker', //cronworker is a function called by your cron job,
			'custom'
		]
	);
	
$APIConfigs['redditapi']['guzzleopts'] = [
	'headers' => [
		'X-API-KEY' => $APIConfigs['fintelapi']['apiKey'],
		'User-Agent' => 'StocksTestApp-b69.420'
	],
	'stream' => true,
	'connect_timeout' => 5,
	'read_timeout' => 15
];

$sentimentconfig['redditapi'] =  [ // 'BEST', 'MOON', 'HOLD',
	'subreddits' => [
		//array (subredditName => precedence)
		//SRs with higher precedence will have a higher 'score' placed on their mentions
		//This lets us manually assign a greater weight to mentions on more popular, default, or arbitrary subs
		'robinhoodpennystocks' => 1,
		'pennystocks' => 1,
		'wallstreetbets' => 0,
		'stocks' => 2,
		'investing' => 2,
		'deepfuckingvalue' => 1],
	'blocked_terms' => 
		['Question','Advice', 'RH', 'YOLO', 'PORN', 'FAKE', 'WISH', 'USD', 'EV', 'MARK', 'RELAX', 'LOL', 'LMAO', 'LMFAO', 
		 'EPS', 'DCF', 'NYSE', 'FTSE', 'APE', 'CEO', 'CTO', 'FUD', 'DD', 'AM', 'PM', 'FDD', 'EDIT', 'TA', 'UK', 'AMC', 'GME', 'DOGE', 'BTC', 'CRYPTO'],
	'positive_terms' => 
	['Invest' => 1, 'Buy' => 1, 'Moon' => 1, 'HODL' => 1, 'DD' => 1, 'Strong' => 1, 'Solid' => 1, 'Undervalued' => 0.75, 'Underpriced' => 0.75, 'Bullish' => 0.45, 'Call' => 0.2, 'Calls' => 0.2, 
		'Long' => 0.3, 'Best' => 1, 'Return' => 0.5, 'Profit' => 1, 'Positive' => 0.5, 'Green' => 0.5, 'In' => 0.33],
	'negative_terms' => ['HOLD' => -1, 'HODL' => -1, //HODL implies the stock is dipping so we'll consider this negative for our purposes
						'Bearish' => -.5, 'Sell' => -1, 'Put' => -.2, 'Puts' => -.2, 'Short' => -.2, 'Overpriced' => -.75, 'Overvalued' => -.75, 'Loss' => -1, 'Dive' => -1, 'Shit' => -1, 'Garbage' => -1, 
						'Terrible' => -1, 'Peaked' => -.5, 'Negative' => -.5, 'Red' => -.5, 'Sold' => -.5, 'Out' => -.5, 'Bomb' => 1, 'Risky' => -.2],
	'neutral_terms' => ['Earnings' => 0.0] //these are key words that don't sway our opinion of a post, but we may still want to note in our list of triggers.
];
?>