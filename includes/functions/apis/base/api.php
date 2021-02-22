<?php
//This file contains the base class for StockAPI
namespace Stocks;

function makeJSONerrorAndExit($msg, $addl = null){
	$response['status'] = 'error';
	$response['message'] = $msg;
	if(!is_null($addl)){
		$response['details'] = $addl;
	}
	exit(json_encode($response));
}

class StocksAPI {
//	public $providerID;
//	public $apiKey;
//	public $apiRateLimit;
	
	public function __construct($providerID){
		if(strlen($providerID) < 1){
			makeJSONerrorAndExit('ERROR: No provider specified.');
			return false;
		}
		$this->providerID = $providerID;
		if(empty($GLOBALS['APIConfigs'][$this->providerID])){
			//We could not find that provider...
			makeJSONerrorAndExit('ERROR: Invalid provider specified.');
			return false;
		}else{
			$this->apiKey =  $GLOBALS['APIConfigs'][$this->providerID]['apiKey'];
			$this->supportedOperations =  $GLOBALS['APIConfigs'][$this->providerID]['supportedOperations'];
			$this->customGuzzleOpts =  $GLOBALS['APIConfigs'][$this->providerID]['guzzleopts'];
			$this->apiRateLimit =  $GLOBALS['APIConfigs'][$this->providerID]['apiRateLimit'];
		}
	}
	
	public function getData($operation, $requestdata = null){
		if(!$operation){
			makeJSONerrorAndExit('ERROR: getData was called but no operation was not specified');
			return false;
		}
		if(!$this->providerID){
			makeJSONerrorAndExit('ERROR: getData was called but no data provider was not specified');
			return false;
		}
		$api = new \ReflectionClass('\Stocks\\'.$this->providerID);
		$api = $api->newInstance($this);
		//$api = new Fintel($this);
		//$api = new lowfloat($this);
		if(!in_array($operation, $this->supportedOperations)){
			makeJSONerrorAndExit($this->providerID.' does not support this operation.', ['supported_operations' => $this->supportedOperations]);
		}
		
		switch($operation){
			
			//////////////////////////////////////////////////////////////////////
			//Custom:															//
			//Call another operation, not defined below							//
			//Actual operation name should be $params['customOperation']		//
			//////////////////////////////////////////////////////////////////////
			case 'custom':
				$result = $api->customOperation($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Custom request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			
			////////////////////////////////////////////////////////////////////////
			//Filings:													   		  //
			//This sends an API request to get filing details on a symbol		  //
			////////////////////////////////////////////////////////////////////////
			case 'filings':
				$result = $api->getFilings($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Filing request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			
			////////////////////////////////////////////////////////////////////////
			//Insiders:													   		  //
			//This sends an API request to get Insider ownership data on a symbol//
			////////////////////////////////////////////////////////////////////////
			case 'insiders':
				$result = $api->getInsiderTrades($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Insider Ownership request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			////////////////////////////////////////////////////////////////////////
			//Shortvol:														   	  //
			//This sends an API request to get activist short vol data on a symbol//
			////////////////////////////////////////////////////////////////////////
			case 'shortvol':
				$result = $api->getShortVol($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Short Volume request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			////////////////////////////////////////////////////////////////////////
			//Quote:														   	  //
			//This sends an API request to get activist ownership data on a symbol//
			////////////////////////////////////////////////////////////////////////
			case 'activists':
				$result = $api->getActivist($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Activist Ownership request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//Hist. Owner:															//
			//This sends an API request to get ownership data on a symbol		//
			//////////////////////////////////////////////////////////////////////
			case 'historyownership':
				$result = $api->getOwnershipHistory($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Ownership history request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//Owners:															//
			//This sends an API request to get ownership data on a symbol		//
			//////////////////////////////////////////////////////////////////////
			case 'ownership':
				$result = $api->getOwnership($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Ownership request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//Owners:															//
			//This sends an API request to get ownership data on a symbol		//
			//////////////////////////////////////////////////////////////////////
			case 'instholdings':
				$result = $api->getInstitutionalHoldings($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Holdings request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//Ticker:															//
			//This sends an API request to get ticker data						//
			//////////////////////////////////////////////////////////////////////
			case 'ticker':
				$result = $api->getTicker($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Quote request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//historytxn:														//
			//This sends an API request to get historic txn data				//
			//////////////////////////////////////////////////////////////////////
			case 'historytxn':
				$result = $api->getHistoricTransactions($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! History transaction request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//historyqt:															//
			//This sends an API request to get historic quote data				//
			//////////////////////////////////////////////////////////////////////
			case 'historyqt':
				$result = $api->getHistoricQuotes($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! History quote request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			//////////////////////////////////////////////////////////////////////
			//Quote:															//
			//This sends an API request to get quote data on a symbol			//
			//////////////////////////////////////////////////////////////////////
		//This is a duplicate of historyqt or ticker, not needed...
		//	case 'quote':
		//		$result = $api->getQuote($requestdata);
		//		if(!$result){
		//			makeJSONerrorAndExit($this->providerID.': API ERROR! Quote request failed.');
		//			return false;
		//		}else{
		//			return $result;
		//		}
		//	break;
			
			//////////////////////////////////////////////////////////////////////
			//News:															    //
			//This sends an API request to get news data on a symbol	    	//
			//////////////////////////////////////////////////////////////////////
			case 'news':
				$result = $api->getNews($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! News request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//Aggregate:														//
			//This sends an API request to get aggregate data on a symbol		//
			//////////////////////////////////////////////////////////////////////
			case 'agg':
				$result = $api->getAgg($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Agg request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//corpdetails:														//
			//This sends an API request to get company details on a symbol		//
			//////////////////////////////////////////////////////////////////////
			case 'corpdetails':
				$result = $api->getTickerData($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! corpdetails request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//Financials:														//
			//This sends an API request to get reference data on a symbol		//
			//////////////////////////////////////////////////////////////////////
			case 'financials':
				$result = $api->getFinancials($requestdata);
				if(!$result){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Financials+Dividends+Splits request failed.');
					return false;
				}else{
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////////
			//Status:															    //
			//This sends a test API request and makes sure it returns 'true' 	 	//
			//////////////////////////////////////////////////////////////////////////
			case 'status':
				$status = $api->sendTestRequest();
				if(!$status){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Test request failed.');
					return false;
				}else{
					$result = ['status' => 'ok', 'message' => $this->providerID.': API is up.', 'details' => $GLOBALS['rocketship']];
					return $result;
				}
			break;
			
			////////////////////////////////////////////////////////////////////////
			//getws:															  //
			//This request returns your provider's webSocket configuration	  	  //
			//so it can be made available to the frontend gui					  //
			//This may expose your API key to the public, 						  //
			//so exposeWebSocket must be set to true in your provider config file //
			////////////////////////////////////////////////////////////////////////
			case 'getws':
				if($GLOBALS['APIConfigs'][$this->providerID]['exposeWebSocket'] != true){
					makeJSONerrorAndExit('You must set exposeWebSocket to true in your API config file to use websockets. This will expose your private API Key.');
					exit;
				}else{
					if(empty($GLOBALS['APIConfigs'][$this->providerID]['webSocketConfig'])){
						makeJSONerrorAndExit('No web sockets are configured for this provider.');
						exit;
					}else{
						$result['webSocketConfig'] = $GLOBALS['APIConfigs'][$this->providerID]['webSocketConfig'];
						$result['apiKey'] = $GLOBALS['APIConfigs'][$this->providerID]['apiKey'];
						$result['providerid'] = $this->providerID;
						return $result;
					}	
				}
			break;
			
			//test case for websocket, pull data and output to user
			case 'wstest':
				$status = $api->sendWSTestRequest();
				if(!$status){
					makeJSONerrorAndExit($this->providerID.': API ERROR! Test request failed.');
					return false;
				}else{
					$result = ['status' => 'ok', 'message' => $this->providerID.': API is up.', 'details' => $status];
					return $result;
				}
			break;
			
			//////////////////////////////////////////////////////////////////////
			//default case														//
			//No valid $operation was given. Just error out					    //
			//////////////////////////////////////////////////////////////////////
			default:
				makeJSONerrorAndExit('Invalid getData operation specified');
				return false;
			break;
		}
		
	}
	
	
	//Send data to the API
	public function sendData(){
		if(!$this->operationName){
			makeJSONerrorAndExit('ERROR: requestData was called but no operation was not specified');
			return false;
		}
		if(!$this->providerID){
			makeJSONerrorAndExit('ERROR: requestData was called but no data provider was not specified');
			return false;
		}
		
		
	}
}

?>