<?php
//Specific for Fintel PHP Client...
//Use this file as a reference when adapting other similar APIs to this software's required format
namespace Stocks;
include(__DIR__.'/api.config.php');

use \GuzzleHttp\Client;
use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Handler\CurlMultiHandler;

class fintelapi extends StocksAPI {
	//Constructor
	public function __construct($apicfg){
		$this->apiKey = $apicfg->apiKey;
		$this->customGuzzleOpts = $apicfg->customGuzzleOpts;
		$this->apiRateLimit = $apicfg->apiRateLimit;
		$this->providerID = $apicfg->providerID;
		$this->guzzle = new \GuzzleHttp\Client($this->customGuzzleOpts);
	}
	
	//Test request to get API status
	protected function sendTestRequest() {	
		$url = 'https://api.fintel.io/web/v/0.0/so/us/gme';
		try{
			$send = $this->guzzle->request('GET', $url);
		}catch(\Exception $e){
			return false;
		}
		$response = json_decode($send->getBody(),true);
		if(!empty($response['symbol'])){
			return true;
		}else{
			return false;
		}
	}
	
	protected function getOwnership($params){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('Ownership - required params: symbol is missing');
			return false;	
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		foreach($symbols as $symbol){
			if($GLOBALS['isPersonalInstallation']){
				$endpoint = 'https://api.fintel.io/data/v/0.0/so/us/'.$symbol;
			}else{
				$endpoint = 'https://api.fintel.io/web/v/0.0/so/us/'.$symbol;
			}
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint)->then(function ($result) {
					
					return json_decode($result->getBody(),true);
					
				})
				->wait();
			}catch(\Exception $e){
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $apirsp['symbol'],
																'x' => $apirsp['exchange'],
																'cntry' => $apirsp['country'],
																'name' => $apirsp['name']);
			foreach($apirsp['owners'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['ownership'][] = [
					'name' => $rspinner['name'],
					'formType' => $rspinner['formType'],
					'id' => $rspinner['slug'],
					'effDt' => $rspinner['effectiveDate'],
					'filedOn' => $rspinner['fileDate'],
					'ownedPct' => $rspinner['ownershipPercent'],
					'ownedPctChg' => $rspinner['ownershipPercentChange'],
					'shares' => $rspinner['shares'],
					'sharesChg' => $rspinner['sharesChange'],
					'val' => $rspinner['value'],
					'valChg' => $rspinner['valueChange'],
					'valChgPct' => $rspinner['valuePercentChange']
				];
			}
		}
		return $mappedresponse;
	}
	
	
	protected function getOwnershipHistory($params){
		if(empty($params['symbol']) || empty($params['ownerid'])){
			//get all tickers
			makeJSONerrorAndExit('Ownership history - required params: symbol or owner id is missing');
			return false;	
		}
		if(is_array($params['ownerid'])){
			makeJSONerrorAndExit('Ownership history - only one ownerid is accepted per request');
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		$investor = $params['ownerid'];
		foreach($symbols as $symbol){
			if($GLOBALS['isPersonalInstallation']){
				$endpoint = 'https://api.fintel.io/data/v/0.0/so/us/'.$symbol.'/'.$investor;
			}else{
				makeJSONerrorAndExit('Historic Ownership - only available for personal users');
			}
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint)->then(function ($result) {					
					return json_decode($result->getBody(),true);
				})
				->wait();
			}catch(\Exception $e){
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		$mappedresponse['meta']['owners'] = $investor;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $apirsp['symbol'],
																'x' => $apirsp['exchange'],
																'cntry' => $apirsp['country'],
																'name' => $apirsp['name']);
		if(is_array($apirsp['bo'])) 	foreach($apirsp['bo'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['ownership'][] = [
					'name' => $rspinner['name'],
					'metatype' => 'bo',
					'formType' => $rspinner['formType'],
					'id' => $rspinner['slug'],
					'effDt' => $rspinner['effectiveDate'],
					'filedOn' => $rspinner['fileDate'],
					'ownedPct' => $rspinner['ownershipPercent'],
					'ownedPctChg' => $rspinner['ownershipPercentChange'],
					'shares' => $rspinner['shares'],
					'sharesChg' => $rspinner['sharesChange']
				];
			}
			if(is_array($apirsp['stocks']))	 foreach($apirsp['stocks'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['ownership'][] = [
					'name' => $rspinner['name'],
					'metatype' => 'stocks',
					'formType' => $rspinner['formType'],
					'id' => $rspinner['slug'],
					'effDt' => $rspinner['effectiveDate'],
					'filedOn' => $rspinner['fileDate'],
					'ownedPct' => $rspinner['ownershipPercent'],
					'ownedPctChg' => $rspinner['ownershipPercentChange'],
					'shares' => $rspinner['shares'],
					'sharesChg' => $rspinner['sharesChange'],
					'val' => $rspinner['value'],
					'valChg' => $rspinner['valueChange'],
					'valChgPct' => $rspinner['valuePercentChange']
				];
			}
		}
		return $mappedresponse;
	}
	
	
	protected function getActivist($params){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('Activist Investor Ownership - required params: symbol is missing');
			return false;	
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		foreach($symbols as $symbol){
			if($GLOBALS['isPersonalInstallation']){
				$endpoint = 'https://api.fintel.io/data/v/0.0/so/us/'.$symbol.'/bo';
			}else{
				makeJSONerrorAndExit('Activist Investor Ownership - only available for personal users');
			}
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint)->then(function ($result) {
					
					return json_decode($result->getBody(),true);
					
				})
				->wait();
			}catch(\Exception $e){
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $apirsp['symbol'],
																'x' => $apirsp['exchange'],
																'cntry' => $apirsp['country'],
																'name' => $apirsp['name']);
			foreach($apirsp['owners'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['ownership'][] = [
					'name' => $rspinner['name'],
					'id' => $rspinner['slug'],
					'formType' => $rspinner['formType'],
					'effDt' => $rspinner['effectiveDate'],
					'filedOn' => $rspinner['fileDate'],
					'ownedPct' => $rspinner['ownershipPercent'],
					'ownedPctChg' => $rspinner['ownershipPercentChange'],
					'shares' => $rspinner['shares'],
					'sharesChg' => $rspinner['sharesChange']
				];
			}
		}
		return $mappedresponse;
	}
	
	protected function getShortVol($params){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('Short Volume - required params: symbol is missing');
			return false;	
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		foreach($symbols as $symbol){
				$endpoint = 'https://api.fintel.io/web/v/0.0/ss/us/'.$symbol.'';		
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint)->then(function ($result) {
					return json_decode($result->getBody(),true);
				})
				->wait();
			}catch(\Exception $e){
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $apirsp['symbol'],
																'x' => $apirsp['exchange'],
																'cntry' => $apirsp['country'],
																'name' => $apirsp['name']);
			$mappedresponse['results'][$symbolrsp]['chart'] = $apirsp['chart']['url'];
			foreach($apirsp['data'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['shortVolume'][] = [
					'date' => $rspinner['marketDate'],
					'shortVol' => $rspinner['shortVolume'],
					'totalVol' => $rspinner['totalVolume'],
					'shortVolRatio' => $rspinner['shortVolumeRatio']
				];
			}
		}
		return $mappedresponse;
	}
	
	
	protected function getFilings($params){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('Filings - required params: symbol is missing');
			return false;	
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		foreach($symbols as $symbol){
				$endpoint = 'https://api.fintel.io/web/v/0.0/sf/us/'.$symbol.'';		
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint)->then(function ($result) {
					return json_decode($result->getBody(),true);
				})
				->wait();
			}catch(\Exception $e){
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $symbolrsp);
			foreach($apirsp as $rspinner){
				$mappedresponse['results'][$symbolrsp]['docs'][] = [
					'title' => $rspinner['title'],
					'url' => $rspinner['url'],
					'formType' => $rspinner['formType'],
					'excerpt' => $rspinner['excerpt'],
					'source' => $rspinner['host'],
					'pub' => $rspinner['publishDate'],
					'security' => [
						'id' => $rspinner['security']['id'],
						'x' => $rspinner['security']['exchange'],
						'xid' => $rspinner['security']['exchangeId'],
						'sym' => $rspinner['security']['symbol'],
						'cntry' => $rspinner['security']['country'],
						'idisin' => $rspinner['security']['isin'],
						'cusipSedol' => $rspinner['security']['cusipSedol'],
						'status' => $rspinner['security']['status'],
						'securityType' => $rspinner['security']['securityType'],
						'canonical' => $rspinner['security']['canonical'],
						'symbolDispName' => $rspinner['security']['symbolDisplayName'],
						'fullSymbol' => $rspinner['security']['fullSymbol'],
						'fullsymbolDispName' => $rspinner['security']['fullSymbolDisplayName'],
						'compactDispName' => $rspinner['security']['compactDisplayName'],
					]
				];
			}
		}
		return $mappedresponse;
	}
		
	protected function getInsiderTrades($params){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('Insider Trades - required params: symbol is missing');
			return false;	
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		foreach($symbols as $symbol){
				$endpoint = 'https://api.fintel.io/web/v/0.0/n/us/'.$symbol.'';		
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint)->then(function ($result) {
					return json_decode($result->getBody(),true);
				})
				->wait();
			}catch(\Exception $e){
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $apirsp['symbol'],
																'x' => $apirsp['exchange'],
																'cntry' => $apirsp['country'],
																'name' => $apirsp['name']);
			$mappedresponse['results'][$symbolrsp]['totalInsiderPct'] = $apirsp['insiderOwnershipPercent'];
			$mappedresponse['results'][$symbolrsp]['totalInsiderPctFloat'] = $apirsp['insiderOwnershipPercentFloat'];
			foreach($apirsp['insiders'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['ownership'][] = [
					'name' => $rspinner['name'],
					'formType' => $rspinner['formType'],
					'filedOn' => $rspinner['fileDate'],
					'txnDate' => $rspinner['transactionDate'],
					'code' => $rspinner['code'],
					'shares' => $rspinner['shares'],
					'value' => $rspinner['value']
				];
			}
		}
		return $mappedresponse;
	}
	
	//Custom function
	//If you want to add a custom function that is not defined in base/api.php, you can do it here.
	//Call $operation=custom with your desired $customOperation
	//Add $customOperation to the switch function below.
	protected function customOperation($params = null){
		switch($params['customOperation']){
			case 'rocketship':
				$result['status'] = 'ok';
				$result['results'] = $GLOBALS['rocketship'];
			break;
			default:
				makeJSONerrorAndExit('Invalid customOperation');
			break;
		}
		return $result;
	}
}

?>