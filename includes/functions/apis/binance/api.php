<?php
namespace Stocks;
include(__DIR__.'/api.config.php');


class binanceapi extends StocksAPI{
	//Constructor
	public function __construct($apicfg){
		$this->apiKey = $apicfg->apiKey;
		$this->apiRateLimit = $apicfg->apiRateLimit;
		$this->providerID = $apicfg->providerID;
		$this->proxyServer = $GLOBALS['APIConfigs']['binanceapi']['webSocketConfig']['proxy'];
	}
	
	//Just make a test websocket request and test for the first response data
	function sendWSTestRequest(){
		$headersfortest = [
			'Host: stream.binance.com:9443',
			'Accept: */*',
			'Connection: Upgrade',
			'Upgrade: websocket',
			'Sec-WebSocket-Version: 13',
			'Sec-WebSocket-Key: '.rand(0,999)
		];
		//websocket_open($host='',$port=80,$headers='',&$error_string='',$timeout=10,$ssl=false, $persistant = false, $path = '/', $context = null)
		if($sp = websocket_open('stream.binance.com', 9443, $headersfortest, $errstr, 10, true, false, '/stream?streams=btcusdt@kline_1m')) {
			//we can write here with: websocket_write($sp,$data,$final=true,$binary=true)
			//or read:  websocket_read($sp,&$error_string=NULL)
		   $decode = json_decode(websocket_read($sp,$errstr),true);
		   if(is_array($decode)){
			   return $decode;
		   }
		}else{
			makeJSONerrorAndExit('failed to open websocket', $errstr);
		}
	}
	
}