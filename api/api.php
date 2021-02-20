<?php
//API Router
namespace Stocks;

ini_set('display_errors',1);
header('content-type: application/json');

include(__DIR__.'/../includes/maininclude.php');


/****
POST REQUEST FORMAT:
{
	"provider":"polygonio", 
	"operation":"ticker",
	"params":{
		"ticker":["AAPL","GME"],
		...
	}
}
****/
if(strlen(file_get_contents('php://input')) > 0){
	$inpt = json_decode(file_get_contents('php://input'), true);
	if(!$inpt){
		echo '{"status":"error", "details":"Invalid JSON input."}';
		exit;
	}
}else{
	echo '{"status":"error", "details":"No JSON input received."}';
	exit;
}

//sanitize function for array_map on "params" field
function quicksanitize($str) {
	return preg_replace("/[[:^print:]]/", "", $str);
}

//sanitize the data
$provider = preg_replace("/[^a-zA-Z0-9]+/", "", $inpt['provider']);
$operation = preg_replace("/[^a-zA-Z0-9]+/", "", $inpt['operation']);

if(is_array($inpt['params'])){
	$params = array_map("Stocks\quicksanitize", $inpt['params']);
}else{
	unset($inpt['params']);
}

if(!isset($GLOBALS['APIConfigs'][$provider])){
	exit('{"status":"error", "message":"Invalid provider specified.", "validProviders":'.json_encode(array_keys($GLOBALS['APIConfigs'])).'}');
}


//default to status if empty operation
if(!isset($operation)){
	$operation = 'status';
}

//create object
$sapi = new StocksAPI($provider);

if(!$sapi){
	exit('{"status":"error","message":"Could not create API object"}');
}

//send requet to API
$response = $sapi->getData($operation,$params);

if(!$response){
	exit('{"status":"error","message":"Request failed. Please check your parameters and retry."}');
}else{
	echo json_encode($response);
}

//tests...
//$sapi = new StocksAPI('polygonapi');
//$sapi->getData('status');
//$testdata = $sapi->getData('ticker'); //Ticker for all items
//$testdata = $sapi->getData('ticker',['symbol'=>'AAPL']); //Specific symbol
//$testdata = $sapi->getData('ticker',['tickertype'=>'losers']); //losers or gainers
//$testdata = $sapi->getData('historytxn',['symbol' => 'GME', 'date'=>'2020-01-29']);
//$testdata = $sapi->getData('historyqt',['symbol' => 'GME', 'date'=>'2020-01-29']);
//$testdata = $sapi->getData('agg',['symbol' => 'GME', 'startdate'=>'2020-01-29', 'enddate'=>'2021-01-29', 'timespan' => 'hour']);
//$testdata = $sapi->getData('agg',['symbol' => ['GME','AAPL','TRIB','NOK','BB'], 'startdate'=>'2020-01-29', 'enddate'=>'2021-01-29', 'timespan' => 'hour']);
//$testdata = $sapi->getData('financials',['symbol' => ['GME','AAPL','TRIB','NOK','BB']]);
//$testdata = $sapi->getData('news',['symbol' => ['GME','AAPL','TRIB','NOK','BB']]);
//$testdata = $sapi->getData('corpdetails',['symbol' => ['GME','AAPL','TRIB','NOK','BB']]);
//$testdata = $sapi->getData('corpdetails',['symbol' => ['GME','AAPL','TRIB','NOK','BB']]);
?>