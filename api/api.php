<?php
//API Router
namespace Stocks;

ini_set('display_errors',0); //force error display to off

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

//sanitize function for array_map on "params" field, could be improved.
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
//See /includes/functions/apis/base/api.php for the main StocksAPI class
//See /includes/functions/apis/{provider}/api.php for any provider-specific classes

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
?>