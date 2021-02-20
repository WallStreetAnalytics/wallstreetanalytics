<?php
//Specific for Nasdaq API...
//For the most part, our expected data format is based on the Polygon API endpoints, so review their docs online

namespace Stocks;
include(__DIR__.'/api.config.php');


//market info
//https://api.nasdaq.com/api/market-info

//quote multi: https://api.nasdaq.com/api/quote/watchlist?symbol=gme|stocks&symbol=gbr|stocks&type=Rv
//for lists: https://api.nasdaq.com/api/quote/list-type/nasdaq100

//or: https://api.nasdaq.com/api/quote/list-type-extended/ipo_performance?&limit=20&sortColumn=symbol&sortOrder=asc
//realtime trades:
//https://api.nasdaq.com/api/quote/GME/realtime-trades?&limit=100&fromTime=00:00
//extended trasdes
//https://api.nasdaq.com/api/quote/GME/extended-trading?assetclass=stocks&markettype=post
//https://api.nasdaq.com/api/quote/GME/extended-trading?assetclass=stocks&markettype=pre
//dividends
//https://api.nasdaq.com/api/quote/GME/dividends?assetclass=stocks

//nocp (for nasdaq) historic
//https://api.nasdaq.com/api/company/GME/historical-nocp?timeframe=d5
//financials (not available for all, prefer polygonio)
//https://api.nasdaq.com/api/company/AAPL/financials?frequency=1
//earnings
//https://api.nasdaq.com/api/analyst/GME/earnings-date
//https://api.nasdaq.com/api/quote/GME/eps
//https://api.nasdaq.com/api/company/GME/earnings-surprise
//https://api.nasdaq.com/api/analyst/GME/earnings-forecast
//https://api.nasdaq.com/api/analyst/GME/estimate-momentum
//ratios
//pe: https://api.nasdaq.com/api/analyst/GME/peg-ratio
//option chain: https://api.nasdaq.com/api/quote/GME/option-chain?assetclass=stocks&limit=60
//short int. (nasdaq only): https://api.nasdaq.com/api/quote/GME/short-interest?assetClass=stocks

//institutional https://api.nasdaq.com/api/company/GME/institutional-holdings?limit=2000&type=TOTAL&sortColumn=marketValue&sortOrder=DESC
//institutional holdings -> https://api.nasdaq.com/api/company/6697/institutional-holdings?limit=20&type=TOTAL&sortColumn=value&sortOrder=DESC,
// -> holding types = NEW, TOTAL, INCREASED, DECREASED, SOLDOUT, ACTIVITY

//insider https://www.nasdaq.com/market-activity/stocks/gme/insider-activity

//revenue EPS (Nasdaq only): https://api.nasdaq.com/api/company/GME/revenue?limit=1
//dividend calendar
//https://api.nasdaq.com/api/calendar/dividends?date=2021-02-01
//or: https://api.nasdaq.com/api/calendar/upcoming

//screener:
//https://api.nasdaq.com/api/screener/stocks?tableonly=true&limit=56&exchange=NASDAQ&sector=transportation
//https://api.nasdaq.com/api/screener/stocks?tableonly=false&limit=7406&offset=0 (everything)
//get all:
//https://api.nasdaq.com/api/screener/stocks?download=true&exchange=nyse
//https://api.nasdaq.com/api/screener/stocks?download=true

//symbol search
//https://api.nasdaq.com/api/autocomplete/slookup/10?search=g

//ticker (movers)
//https://api.nasdaq.com/api/marketmovers?assetclass=stocks&limit=100

//https://api.nasdaq.com/api/quote/GME/info?assetclass=stocks
//indices https://api.nasdaq.com/api/quote/indices?chartFor=
//indicies chart https://api.nasdaq.com/api/quote/indices?chartFor=NYA (nyse w/ all other basic data)
//indicies chart restricted results - https://api.nasdaq.com/api/quote/indices?chartFor=NYA&chartFor=NDX&symbol=NYA&symbol=NDX (only show ndx and nya with chart data)



use \GuzzleHttp\Client;
use \GuzzleHttp\HandlerStack;
use \GuzzleRetry\GuzzleRetryMiddleware;

class nasdaqapi extends StocksAPI {
	//Constructor
	public function __construct($apicfg){
		$this->apiKey = $apicfg->apiKey;
		$this->customGuzzleOpts = $apicfg->customGuzzleOpts;
		$this->apiRateLimit = $apicfg->apiRateLimit;
		$this->providerID = $apicfg->providerID;
		
		$stack = \GuzzleHttp\HandlerStack::create();
		$stack->push(\GuzzleRetry\GuzzleRetryMiddleware::factory([
			'max_retry_attempts' => 5,
			'retry_on_status' => ['403','524','503','429'],
			'retry_on_timeout' => true,
			'default_retry_multiplier' => 0
		]));
		$this->customGuzzleOpts['handler'] = $stack;
		
		$this->guzzle = new \GuzzleHttp\Client($this->customGuzzleOpts);
	}	
	
	//Test request to get API status
	protected function sendTestRequest() {	
		$url = 'https://api.nasdaq.com/api/screener/stocks?tableonly=true&limit=10&exchange=NASDAQ&sector=transportation';
		try{
			$send = $this->guzzle->request('GET', $url);
		}catch(\Exception $e){
			return false;
		}
		$response = json_decode($send->getBody(),true);
		if($response['status']['rCode'] == 200){
			return true;
		}else{
			return false;
		}
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
		//Get single ticker
		if($params['limit'] > 0){
			$limit = $params['limit'];
		}else{
			$limit = 25;
		}
		//Get single ticker
		if($params['order']  == 'asc'){
			$order = 'asc';
		}else{
			$order = 'desc';
		}
		//Get single ticker
		if(in_array($params['sort'],['filed','period'])){
			$sort = $params['sort'];
		}else{
			$sort = 'filed';
		}
		foreach($symbols as $symbol){
				$endpoint = 'https://api.nasdaq.com/api/company/'.$symbol.'/sec-filings?limit='.$limit.'&sortColumn='.$sort.'&sortOrder='.$order;		
				try{
					$response[$symbol] = $this->guzzle->getAsync($endpoint);
				}catch(\Exception $e){
					continue;
				}
		}
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();

		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		$mappedresponse['meta']['limit'] = $limit;
		$mappedresponse['meta']['sort'] = $sort;
		$mappedresponse['meta']['sortorder'] = $order;
		foreach($response as $symbolrsp => $apirsp){
			$mappedresponse['results'][$symbolrsp]['meta'] = array('sym' => $symbolrsp);
			if(!is_object($apirsp['value'])){
				continue;
			}
			$apirsp = json_decode($apirsp['value']->getBody(),true);
			
			foreach($apirsp['data']['rows'] as $rspinner){
				$mappedresponse['results'][$symbolrsp]['docs'][] = [
					'title' => 'Form '.$rspinner['formType'].', filed on '.$rspinner['filed'],
					'url' => $rspinner['form'],
					'formType' => $rspinner['formType'],
					'excerpt' => $rspinner['excerpt'],
					'source' => 'SEC',
					'owner' => $rspinner['reportingOwner'],
					'pub' => $rspinner['filed'],
					'security' => [
		//				'id' => $rspinner['security']['id'],
		//				'x' => $rspinner['security']['exchange'],
		//				'xid' => $rspinner['security']['exchangeId'],
						'sym' => $apirsp['data']['symbol'],
		//				'cntry' => $rspinner['security']['country'],
		//				'idisin' => $rspinner['security']['isin'],
		//				'cusipSedol' => $rspinner['security']['cusipSedol'],
		//				'status' => $rspinner['security']['status'],
		//				'securityType' => $rspinner['security']['securityType'],
		//				'canonical' => $rspinner['security']['canonical'],
						'symbolDispName' => $rspinner['companyName'],
		//				'fullSymbol' => $rspinner['security']['fullSymbol'],
		//				'fullsymbolDispName' => $rspinner['security']['fullSymbolDisplayName'],
		//				'compactDispName' => $rspinner['security']['compactDisplayName'],
					]
				];
			}
		}
		return $mappedresponse;
	}
	
	//Get ownership
	//This function gets institutional ownership for a stock or company
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
		
		if(in_array(strtoupper($params['type']),['NEW', 'TOTAL', 'INCREASED', 'DECREASED', 'SOLDOUT', 'ACTIVITY'])){
			$type = strtoupper($params['type']);
		}else{
			$type = 'TOTAL';
		}
		
		if($params['limit'] > 0 && $params['limit'] < 50000){
			$limit = $params['limit'];
		}else{
			$params['limit'] = '1000';
		}
		if($params['offset'] > 0){
			$append = '&offset='.$params['offset'];
			$offset = $params['offset'];
		}
		
		foreach($symbols as $symbol){
			$endpoint = 'https://api.nasdaq.com/api/company/'.$symbol.'/institutional-holdings?limit='.$limit.'&type='.$type.'&sortColumn=marketValue&sortOrder=DESC'.$append;
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint);
			}catch(\Exception $e){
				continue;
			}
		}
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		$mappedresponse['meta']['params']['limit'] = $limit;
		$mappedresponse['meta']['params']['type'] = $type;
		$mappedresponse['meta']['params']['offset'] = $offset ?: 0;
		foreach($response as $symbolrsp => $apirsp){
			if(!is_object($apirsp['value'])){
				continue;
			}
			$apirsp = json_decode($apirsp['value']->getBody(),true);
			
			$mappedresponse['results'][$symbolrsp] = [
				'meta' => ['sym' => $symbolrsp],
				'shareoutstandingTotal' => preg_replace("/[^0-9\(\)\.-]/","",$apirsp['data']['ownershipSummary']['ShareoutstandingTotal']['value'])*1000000,
				'holdingValueTotal' => preg_replace("/[^0-9\(\)\.-]/","",$apirsp['data']['ownershipSummary']['TotalHoldingsValue']['value'])*1000000,
				'instHoldersCount' => $apirsp['data']['holdingsTransactions']['totalRecords'],
				'instSharesHeld' => preg_replace("/[^0-9\(\)\.-]/","",$apirsp['data']['holdingsTransactions']['sharesHeld']),
				'instOwnershipPCT' => preg_replace("/[^0-9\(\)\.-]/","",$apirsp['data']['ownershipSummary']['SharesOutstandingPCT']['value']),
			];
			foreach($apirsp['data']['activePositions']['rows'] as $actpos){
				switch($actpos['positions']){
					case "Increased Positions":
						$mappedresponse['results'][$symbolrsp]['positions']['increased'] = array(
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
					case 'Decreased Positions':
						$mappedresponse['results'][$symbolrsp]['positions']['decreased'] = array(
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
					case 'Held Positions':
						$mappedresponse['results'][$symbolrsp]['positions']['held'] = array(
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
					case 'Total Institutional Shares':
						$mappedresponse['results'][$symbolrsp]['positions']['total'] = array(
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
						
					default:
						$mappedresponse['results'][$symbolrsp]['positions']['other'] = array(
							'type' => $actpos['positions'],
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
				}
			}
			unset($actpos);
			foreach($apirsp['data']['newSoldOutPositions']['rows'] as $actpos){
				switch($actpos['positions']){
					case "New Positions":
						$mappedresponse['results'][$symbolrsp]['positions']['new'] = array(
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
					case 'Sold Out Positions':
						$mappedresponse['results'][$symbolrsp]['positions']['soldOut'] = array(
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;						
					default:
						$mappedresponse['results'][$symbolrsp]['positions']['other'] = array(
							'type' => $actpos['positions'],
							'holders' => $actpos['holders'],
							'shares' => preg_replace("/[^0-9\(\)\.-]/","",$actpos['shares'])
						);
					break;
				}
			}
			foreach($apirsp['data']['holdingsTransactions']['table']['rows'] as $rspinner){
				$cmpid = explode('-',$rspinner['url']);
				$cmpid = $cmpid[count($cmpid) - 1];
				$held = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['sharesHeld']);
				$chg = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['valueChange']);
				$val = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['marketValue']);
				$vcp = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['valuePercentChange']);
				$scp = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['sharesChangePCT']);
				$mappedresponse['results'][$symbolrsp]['ownership'][] = [
					'name' => ucwords(strtolower($rspinner['ownerName'])),
					'formType' => '',
					'url' => 'https://www.nasdaq.com/'.$rspinner['url'],
					'id' => $cmpid,
					'effDt' => $rspinner['date'],
					'filedOn' => '',
					'ownedPct' => $rspinner['ownershipPercent'],
					'ownedPctChg' => $rspinner['ownershipPercentChange'],
					'shares' => $held,
					'sharesChg' => $rspinner['sharesChange'],
					'sharesChgPct' => $scp,
					'val' => $val,
					'valChg' => $chg,
					'valChgPct' => $vcp
				];
			}
		}
		return $mappedresponse;
	}

	//Inst. Holdings
	//This function gets holdings for a particular institution
	protected function getInstitutionalHoldings($params){
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
		
		if(in_array(strtoupper($params['type']),['NEW', 'TOTAL', 'INCREASED', 'DECREASED', 'SOLDOUT', 'ACTIVITY'])){
			$type = strtoupper($params['type']);
		}else{
			$type = 'TOTAL';
		}
		
		if($params['limit'] > 0 && $params['limit'] < 50000){
			$limit = $params['limit'];
		}else{
			$params['limit'] = '1000';
		}
		
		if($params['offset'] > 0){
			$append = '&offset='.$params['offset'];
			$offset = $params['offset'];
		}
		
		foreach($symbols as $symbol){
			$endpoint = 'https://api.nasdaq.com/api/company/'.$symbol.'/institutional-holdings?limit='.$limit.'&type='.$type.'&sortColumn=marketValue&sortOrder=DESC'.$append;
			try{
				$response[$symbol] = $this->guzzle->getAsync($endpoint);
			}catch(\Exception $e){
				continue;
			}
		}
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['companyId'] = $symbols;
		$mappedresponse['meta']['params']['limit'] = $limit;
		$mappedresponse['meta']['params']['type'] = $type;
		$mappedresponse['meta']['params']['offset'] = $offset ?: 0;
		foreach($response as $symbolrsp => $apirsp){
			if(!is_object($apirsp['value'])){
				continue;
			}
			$apirsp = json_decode($apirsp['value']->getBody(),true);
			
			if(empty($apirsp['data']['title'])){
				makeJSONerrorAndExit('Not found. Please check that the nasdaq.com company ID is valid and try again.',['requestedId' => $params['symbol']]);
			}
			
			$mappedresponse['results'][$symbolrsp] = [
				'meta' => ['sym' => $symbolrsp],
				'reportDate' => preg_replace('#^Report last updated #','',$apirsp['data']['reportDate']),
				'companyDetails' => [
					'name' => $apirsp['data']['title'],
					'address' => $apirsp['data']['address']],
				'holdingValueTotal' => preg_replace("/[^0-9\(\)\.-]/","", $apirsp['data']['positionStatistics']['TotalMktValue']['value'])*1000000,
			];
			
			$mappedresponse['results'][$symbolrsp]['positionsOverview']['total'] = $apirsp['data']['positionStatistics']['TotalPositions']['value'];
			$mappedresponse['results'][$symbolrsp]['positionsOverview']['new'] = $apirsp['data']['positionStatistics']['NewPositions']['value'];
			$mappedresponse['results'][$symbolrsp]['positionsOverview']['increased'] = $apirsp['data']['positionStatistics']['IncreasedPositions']['value'];
			$mappedresponse['results'][$symbolrsp]['positionsOverview']['decreased'] = $apirsp['data']['positionStatistics']['DecreasedPositions']['value'];
			$mappedresponse['results'][$symbolrsp]['positionsOverview']['active'] = $apirsp['data']['positionStatistics']['PositionswithActivity']['value'];
			$mappedresponse['results'][$symbolrsp]['positionsOverview']['soldOut'] = $apirsp['data']['positionStatistics']['SoldOutPositions']['value'];
			foreach($apirsp['data']['sectorWeighting'] as $sectors){
				$mappedresponse['results'][$symbolrsp]['sectorsPct'][$sectors['label']] =  preg_replace("/[^0-9\(\)\.-]/","", $sectors['value']);
			}
			unset($actpos);
			foreach($apirsp['data']['institutionPositions']['table']['rows'] as $rspinner){
				$cmpid = explode('/',$rspinner['url']);
				$cmpid = $cmpid[count($cmpid) - 2];
				$value = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['value'])*1000;
				$held = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['sharesHeld']);
				$chg = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['change'])*1000;
				$scp = preg_replace("/[^0-9\(\)\.-]/","",$rspinner['pctChange']);
				$mappedresponse['results'][$symbolrsp]['holdings'][] = [
					'name' => ucwords(strtolower($rspinner['company'])),
					'formType' => '13-F',
					'url' => 'https://www.nasdaq.com/'.$rspinner['url'],
					'symbol' => $cmpid,
					'class' => $rspinner['class'],
					'ownedChg' => '',
					'ownedPctChg' => '',
					'shares' => $held,
					'sharesChg' => '',
					'sharesChgPct' => '',
					'val' => $value,
					'valChg' => $chg,
					'valChgPct' => $scp
				];
			}
		}
		return $mappedresponse;
	}
	
	protected function getNasdaqCustomMovers($params = null){
		
		if(isset($params['exchange'])){
			$append = '&exchange='.$params['exchange'];
		}
		if(isset($params['sector'])){
			$append = $append.'&sector='.$params['sector'];
		}
		if(isset($params['limit']) && is_numeric($params['limit']) && $params['limit'] <= 100){
			$append = $append.'&limit='.$params['limit'];
		}
		$type = 'stocks';
		try{
			$qresponse[$symbol] = $this->guzzle->getAsync('https://api.nasdaq.com/api/marketmovers?assetclass='.$type.$append);
		}catch(\Exception $e){
			return false;
		}
		
		
		$qresponse = \GuzzleHttp\Promise\Utils::settle($qresponse)->wait();				
				
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta'] = $params;
		
		foreach($qresponse as $key => $resp){	
			if(!is_object($resp['value'])){
				continue;
			}
			$symbol = $key;
			$apik=json_decode($resp['value']->getBody(),true);
			foreach($apik['data']['STOCKS'] as $tmptype => $responset){
				if($tmptype == 'MostActiveByShareVolume') $type = 'mostActiveByShrs';
				elseif($tmptype == 'MostAdvanced') $type = 'gainers';
				elseif($tmptype == 'MostDeclined') $type = 'losers';
				elseif($tmptype == 'MostActiveByDollarVolume') $type = 'mostActiveByDls';
				elseif($tmptype == 'Nasdaq100Movers') $type = 'NDXMovers';
				$ct = $responset['table']['headers']['change'];
				foreach($responset['table']['rows'] as $response){
				$mappedresponse['results'][$type][] = [
					'upd' => '',
					'pctchgtd' => preg_replace("/[^0-9\(\)\.-]/","",$response['pctchange']),
					'chgtd' => preg_replace("/[^0-9\(\)\.-]/","",$response['change']),
					'chgtype' => $ct,
					'tkr' => $response['symbol'],
					'n' => $response['name'],
					'cap' => preg_replace("/[^0-9\(\)\.-]/","",$response['marketCap']),
					/*'day' => [ //day
							'o' => $response['day']['o'], //opening price
							'h' => $response['day']['h'], //high price (day)
							'l' => $response['day']['l'], //low price (day)
							'c' => $response['day']['c'], //close price (day)
							'v' => $response['day']['v'], //trading volume (day)
							'vw' => $response['day']['vw'] //volume weighted average price (day)
						],
					'pday' => [ //previous day
						'o' => $response['prevDay']['o'], //opening price
						'h' => $response['prevDay']['h'], //high price (day)
						'l' => $response['prevDay']['l'], //low price (day)
						'c' => $response['prevDay']['c'], //close price (day)
						'v' => $response['prevDay']['v'], //trading volume (day)
						'vw' => $response['prevDay']['vw'] //volume weighted average price (day)
					],
					'lq' => [ //last quote
						'bp' => $response['lastQuote']['p'], //bid price
						'bs' => $response['lastQuote']['s'], //bid size
						'ap' => $response['lastQuote']['P'], //ask price
						'as' => $response['lastQuote']['S'], //ask size
						't' => $response['lastQuote']['t'] //timestamp unix
					],*/
					'lt' => [ //last trade
					//	'c' => $response['lastTrade']['c'], //trade condition
					//	'i' => $response['lastTrade']['i'], //trade id
						'p' => preg_replace("/[^0-9\(\)\.-]/","",$response['lastSalePrice']), //price
						'chg' => preg_replace("/[^0-9\(\)\.-]/","",$response['lastSaleChange']) //added from nasdaq: last sale price changed compared to open
					//	's' => $response['lastTrade']['s'], //size
					//	'x' => $response['lastTrade']['x'], //PolygonIO Exchange ID //https://api.polygon.io/v1/meta/exchanges?&apiKey={APIKEY}
					//	't' => $response['lastTrade']['t'] //timestamp unix
					],
					/*'bar' => [ //one-minute bar
						'av' => $response['prevDay']['av'], //accumulated volume
						'o' => $response['prevDay']['o'], //opening price (minute)
						'h' => $response['prevDay']['h'], //high price (minute)
						'l' => $response['prevDay']['l'], //low price (minute)
						'c' => $response['prevDay']['c'], //close price (minute)
						'v' => $response['prevDay']['v'], //trading volume (minute)
						'vw' => $response['prevDay']['vw'] //volume weighted average price (minute)
					]*/
					];
				}
			}
		}
		
		//if we have a specific ticker type requested, clear the other results.
		if($params['tickertype'] == 'gainers'){ $mappedresponse['results'] = $mappedresponse['results']['gainers']; }
		elseif($params['tickertype'] == 'losers'){ $mappedresponse['results'] = $mappedresponse['results']['losers']; }
		elseif($params['tickertype'] == 'mostactivebyshares'){ $mappedresponse['results'] = $mappedresponse['results']['mostActiveByShrs']; }
		elseif($params['tickertype'] == 'mostactivebydollars'){ $mappedresponse['results'] = $mappedresponse['results']['mostActiveByDls']; }
		elseif($params['tickertype'] == 'ndxmovers'){ $mappedresponse['results'] = $mappedresponse['results']['NDXMovers']; }
		

		return $mappedresponse;
		
	}
	
	//Get Ticker Snapshots
	//Params can be:
	//$params['tickertype'] = 'gainers' or 'losers', or null to check all or single symbol
	//$params['symbol'] = 'AAPL' or null for all
	protected function getTicker($params = null){
	    $validtickertypes = ['allmovers', 'gainers','losers', 'mostactivebyshares', 'mostactivebydollars', 'ndxmovers'];
		if(in_array($params['tickertype'],$validtickertypes)){
			//moved this into another function since it was too much code to keep here
			return $this->getNasdaqCustomMovers($params);
		}else{
			unset($params['tickertype']);
			if(empty($params['symbol'])){
				//https://api.nasdaq.com/api/screener/stocks?tableonly=true&limit=56&exchange=NASDAQ&sector=transportation
				if(isset($params['exchange'])){
					$append = '&exchange='.$params['exchange'];
				}
				if(isset($params['sector'])){
					$append = $append.'&sector='.$params['sector'];
				}
				if(isset($params['limit']) && is_numeric($params['limit'])){
					$append = $append.'&limit='.$params['limit'];
				}
				if($params['assetclass'] == 'index'){
					$type = 'index';
				}else{
					$type = 'stocks';
				}
				try{
					$qresponse[$symbol] = $this->guzzle->getAsync('https://api.nasdaq.com/api/screener/'.$type.'?tableonly=false&offset=0'.$append);
				}catch(\Exception $e){
					//just ignore it
				}
			}else{
				//Get single ticker
				//This is mapped to the same format as all tickers so we can use the same loop
				if(!is_array($params['symbol'])){
					$symbols[0] = $params['symbol'];
				}else{
					$symbols = $params['symbol'];
				}
				if($params['assetclass'] == 'index'){
					$ac = 'index';
				}else{
					$ac = 'stocks';
				}
				foreach ($symbols as $symbol){
					try{
						$qresponse[$symbol] = $this->guzzle->getAsync('https://api.nasdaq.com/api/quote/'.$symbol.'/info?assetclass='.$ac);
					}catch(\Exception $e){
						//just ignore it
					}
				}				
			}
		}

		$qresponse = \GuzzleHttp\Promise\Utils::settle($qresponse)->wait();				
				
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta'] = $params;
		
		foreach($qresponse as $key => $resp){	
			if(!is_object($resp['value'])){
				continue;
			}
			$symbol = $key;
			$apik=json_decode($resp['value']->getBody(),true);
				if(is_array($apik['data']['table'])){
					foreach($apik['data']['table']['rows'] as $response){
					$mappedresponse['results'][] = [
						'upd' => '',
						'pctchgtd' => preg_replace("/[^0-9\(\)\.-]/","",$response['pctchange']),
						'chgtd' => preg_replace("/[^0-9\(\)\.-]/","",$response['netchange']),
						'tkr' => $response['symbol'],
						'n' => $response['name'],
						'cap' => preg_replace("/[^0-9\(\)\.-]/","",$response['marketCap']),
						/*'day' => [ //day
								'o' => $response['day']['o'], //opening price
								'h' => $response['day']['h'], //high price (day)
								'l' => $response['day']['l'], //low price (day)
								'c' => $response['day']['c'], //close price (day)
								'v' => $response['day']['v'], //trading volume (day)
								'vw' => $response['day']['vw'] //volume weighted average price (day)
							],
						'pday' => [ //previous day
							'o' => $response['prevDay']['o'], //opening price
							'h' => $response['prevDay']['h'], //high price (day)
							'l' => $response['prevDay']['l'], //low price (day)
							'c' => $response['prevDay']['c'], //close price (day)
							'v' => $response['prevDay']['v'], //trading volume (day)
							'vw' => $response['prevDay']['vw'] //volume weighted average price (day)
						],
						'lq' => [ //last quote
							'bp' => $response['lastQuote']['p'], //bid price
							'bs' => $response['lastQuote']['s'], //bid size
							'ap' => $response['lastQuote']['P'], //ask price
							'as' => $response['lastQuote']['S'], //ask size
							't' => $response['lastQuote']['t'] //timestamp unix
						],*/
						'lt' => [ //last trade
						//	'c' => $response['lastTrade']['c'], //trade condition
						//	'i' => $response['lastTrade']['i'], //trade id
							'p' => preg_replace("/[^0-9\(\)\.-]/","",$response['lastsale']), //price
						//	's' => $response['lastTrade']['s'], //size
						//	'x' => $response['lastTrade']['x'], //PolygonIO Exchange ID //https://api.polygon.io/v1/meta/exchanges?&apiKey={APIKEY}
						//	't' => $response['lastTrade']['t'] //timestamp unix
						],
						/*'bar' => [ //one-minute bar
							'av' => $response['prevDay']['av'], //accumulated volume
							'o' => $response['prevDay']['o'], //opening price (minute)
							'h' => $response['prevDay']['h'], //high price (minute)
							'l' => $response['prevDay']['l'], //low price (minute)
							'c' => $response['prevDay']['c'], //close price (minute)
							'v' => $response['prevDay']['v'], //trading volume (minute)
							'vw' => $response['prevDay']['vw'] //volume weighted average price (minute)
						]*/
					];
				}
			
		}else{
				$mappedresponse['results'][] = [
					'upd' => preg_replace('#^DATA AS OF #','',$apik['data']['primaryData']['lastTradeTimestamp']),
					'pctchgtd' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['primaryData']['percentageChange']),
					'chgtd' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['primaryData']['netChange']),
					'tkr' => $apik['data']['symbol'],
					'n' => $apik['data']['companyName'],
					'exch' => $apik['data']['exchange'],
					'cap' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['keyStats']['MarketCap']['value']),
					'day' => [ //day
							'o' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['keyStats']['OpenPrice']['value']), //opening price
						//	'h' => $response['day']['h'], //high price (day)
						//	'l' => $response['day']['l'], //low price (day)
						//	'c' => $response['day']['c'], //close price (day)
							'v' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['keyStats']['Volume']['value']), //trading volume (day)
						//	'vw' => $response['day']['vw'] //volume weighted average price (day)
						],
					'pday' => [ //previous day
				//		'o' => $response['prevDay']['o'], //opening price
				//		'h' => $response['prevDay']['h'], //high price (day)
				//		'l' => $response['prevDay']['l'], //low price (day)
						'c' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['keyStats']['PreviousClose']['value']), //close price (day)
				//		'v' => $response['prevDay']['v'], //trading volume (day)
				//		'vw' => $response['prevDay']['vw'] //volume weighted average price (day)
					],
			/*		'lq' => [ //last quote
						'bp' => $response['lastQuote']['p'], //bid price
						'bs' => $response['lastQuote']['s'], //bid size
						'ap' => $response['lastQuote']['P'], //ask price
						'as' => $response['lastQuote']['S'], //ask size
						't' => $response['lastQuote']['t'] //timestamp unix
					],*/
					'lt' => [ //last trade
					//	'c' => $response['lastTrade']['c'], //trade condition
					//	'i' => $response['lastTrade']['i'], //trade id
						'p' => preg_replace("/[^0-9\(\)\.-]/","",$apik['data']['primaryData']['lastSalePrice']), //price
					//	's' => $response['lastTrade']['s'], //size
					//	'x' => $response['lastTrade']['x'], //PolygonIO Exchange ID //https://api.polygon.io/v1/meta/exchanges?&apiKey={APIKEY}
					//	't' => $response['lastTrade']['t'] //timestamp unix
					],
					/*'bar' => [ //one-minute bar
						'av' => $response['prevDay']['av'], //accumulated volume
						'o' => $response['prevDay']['o'], //opening price (minute)
						'h' => $response['prevDay']['h'], //high price (minute)
						'l' => $response['prevDay']['l'], //low price (minute)
						'c' => $response['prevDay']['c'], //close price (minute)
						'v' => $response['prevDay']['v'], //trading volume (minute)
						'vw' => $response['prevDay']['vw'] //volume weighted average price (minute)
					]*/
				];
			}
		}
	
		return $mappedresponse;
	}
	
	//Get Historic Transactions
	//Params can be:
	//Symbol REQUIRED
	//Date REQUIRED
	protected function getHistoricTransactions($params = null){

		if(empty($params['symbol']) || empty($params['date'])){
			//get all tickers
			makeJSONerrorAndExit('Historic trades - required params: symbol or date are missing');
			return false;	
		}
			
			if(preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$params['date'])) {
				//ok
			}else{
				makeJSONerrorAndExit('Historic trades - required params: date is in wrong format. Use YYYY-MM-DD');
				return false;
			}
			
			//Get single ticker
			if(!is_array($params['symbol'])){
				$symbols[] = $params['symbol'];
			}else{
				$symbols = $params['symbol'];
			}
			foreach($symbols as $symbol){
				try{
					$apik[] = $this->rest->stocks->historicTradesV2->get($symbol, $params['date']);		
				}catch(\Exception $e) {
					continue;
				}
			}
		
		if(!$apik){
			return false;
		}
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		$mappedresponse['meta']['date'] = $params['date'];
		$mappedresponse['config']['tapenames'] = $this->friendlytapeids;
		foreach($apik as $apiss){
			foreach($apiss['results'] as $response){
				$mappedresponse['results'][] = [
						'ids' => [
							'oid' => $response['I'], //original identifier
							'tid' => $response['i'], //close price (day)
							'xid' => $response['x'], //trade id (The Trade ID which uniquely identifies a trade. These are unique per combination of ticker, exchange, and TRF.)
							'e' => $response['e'], //trade correction id
							'r' => $response['r'] //reporting facility
						],
						'ts' => [
							't' => $response['t'], //ns accurate SIP timestamp
							'y' => $response['y'], //ns accurate p/e timestamp
							'f' => $response['f'] //ns accurate TRF(Trade Reporting Facility) Unix Timestamp
						],
						'seq' => $response['q'], //sequence of trade events
						'c' => $response['c'], //trade conditions
						's' => $response['s'], //size of trade
						'z' => $response['z'], //tape type
						//'zf' => $this->friendlytapeids[$response['z']] //friendly tape type
				];
			}
		}
		return $mappedresponse;
	}
	
	
	//Get Historic Quotes, agg per day
	//Params can be:
	//Symbol REQUIRED
	//Date REQUIRED
	protected function getAgg($params = null){

		if(empty($params['symbol']) || empty($params['date'])){
			//get all tickers
			makeJSONerrorAndExit('agg - required params: symbol or date are missing');
			return false;	
		}
			
		if(preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$params['date'])) {
			//ok
		}else{
			makeJSONerrorAndExit('agg - required params: date is in wrong format. Use YYYY-MM-DD');
			return false;
		}
		if(preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$params['enddate'])) {
			//ok
		}else{
			$params['enddate'] = date('Y-m-d'); //this api does not work if we actually give it a reasonable end date so just set it to today regardless of how far back our from date is.
			// = date('Y-m-d', strtotime('+1 month', strtotime($params['date'])));
		}
		
		//Get single ticker
		if(!is_array($params['symbol'])){
			$symbols[] = $params['symbol'];
		}else{
			$symbols = $params['symbol'];
		}
		foreach($symbols as $symbol){
			
				try{
					if($params['assetclass'] == 'index'){
						$response[$symbol] = $this->guzzle->getAsync('https://api.nasdaq.com/api/quote/'.$symbol.'/historical?assetclass=index&limit=5000&fromdate='.$params['date'].'&todate='.$params['enddate']);
					}else{
						$response[$symbol] = $this->guzzle->getAsync('https://api.nasdaq.com/api/quote/'.$symbol.'/historical?assetclass=stocks&limit=5000&fromdate='.$params['date'].'&todate='.$params['enddate']);
					}
				}catch(\Exception $e){
					//just ignore it
				}
			
		}
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['requested'] = $symbols;
		$mappedresponse['meta']['period'] = 'day';
		$mappedresponse['meta']['start'] = $params['date'];
		$mappedresponse['meta']['end'] = $params['enddate'];
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
	
		foreach($response as $apikss){
			if(!is_object($apikss['value'])){
				continue;
			}
			$apikss = json_decode($apikss['value']->getBody(),true);

			foreach($apikss['data']['tradesTable']['rows'] as $response){
				$fvh = preg_replace("/[^0-9\(\)\.-]/","",$response['high']);
				$fvl = preg_replace("/[^0-9\(\)\.-]/","",$response['low']);
				$fvc = preg_replace("/[^0-9\(\)\.-]/","",$response['close']);
				$fvo = preg_replace("/[^0-9\(\)\.-]/","",$response['open']);
				$fvv = preg_replace("/[^0-9\(\)\.-]/","",$response['volume']);
				if(!empty($fvv)){
					$vw = substr( ( ( (	( $fvh
						  + $fvl
						 + $fvc ) / 3 ) * $fvv
					) / $fvv), 0, -1 ); //this is not a precise number! use https://php-decimal.io/#installation
				}else{
					$vw = '';
				}
				$symbol = $apikss['data']['symbol'];
				$mappedresponse['results'][$symbol]['ticker'] = $symbol;
				$mappedresponse['results'][$symbol]['splitadj'] = '';
				$mappedresponse['results'][$symbol]['count'] = $response['data']['totalRecords'];
					$mappedresponse['results'][$symbol]['points'][] = [
						'ts' => $response['date'], //unix timestamp, start of agg window
						'num' => 1, //# of items in agg window - this is not always available?
						'vw' => $vw,
						'v' => $fvv, //volume
						'c' => $fvc, //close price
						'l' => $fvl, //low price
						'h' => $fvh, //high price
						'o' => $fvo //open price
				];
			}
		}
		return $mappedresponse;
	}
	
	//Get financials for a particular symbol, or an array of symbols
	//symbol REQUIRED
	//sort 'reportPeriod','-reportPeriod','calendarDate','-calendarDate'
	//type Y = Year YA = Year annualized Q = Quarter QA = Quarter Annualized T = Trailing twelve months TA = trailing twelve months annualized
	//limit (default 20)
	protected function getFinancials($params = null){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('GetFinancials - required params: symbol is missing');
			return false;	
		}
		if(!is_array($params['symbol'])){
			$params['symbol'] = array($params['symbol']);
		}
		if(in_array($params['sort'],['reportPeriod','-reportPeriod','calendarDate','-calendarDate'])){
			$reqparams['sort'] = $params['sort'];
		}else{
			$reqparams['sort'] = 'calendarDate';
		}
		if(in_array($params['type'],['Y','YA','Q','QA','T','TA'])){
			$reqparams['type'] = $params['type'];
		}else{
			$reqparams['type'] = 'Y';
		}
		$reqparams['limit'] = $limit ?: 20;
		
		foreach($params['symbol'] as $symbol){
			try{
				$apires[$symbol] = $this->rest->reference->stockFinancials->get($symbol, $reqparams);	
			}catch(\Exception $e) {
				//nothing..
			}
			try{
				$apidiv[$symbol] = $this->rest->reference->stockDividends->get($symbol);	
			}catch(\Exception $e) {
				//nothing...
			}
			try{
				$apisplit[$symbol] = $this->rest->reference->stockSplits->get($symbol);	
			}catch(\Exception $e) {
				//nothing...
			}
		}
		if(!$apires && !$apidiv && !$apisplit){
			return false;
		}
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['requested'] = $params['symbol'];
		$mappedresponse['meta']['operations'] = ['stockFinancials','stockSplits','stockDividends']; //used for metadata only
		$mappedresponse['meta']['parameters'] = $reqparams;
		foreach($apires as $symbol=>$apik){
			$mappedresponse['results'][$symbol]['ticker'] = $symbol;
			//These three APIs return self explanatory property names
			//We will not map these here since there are so many properties.
			//stockFinancials ($apires) is mapped to the GUI in FinancialDataFormat.csv -> If you are adding a new API, then please make sure to re-map this file to your API's property names
			$mappedresponse['results'][$symbol]['splits'] = $apisplit[$symbol];
			$mappedresponse['results'][$symbol]['dividends'] = $apidiv[$symbol];
			$mappedresponse['results'][$symbol]['financials'] = $apires[$symbol];
		}
		return $mappedresponse;
	}
	
	
	//Get news for a particular symbol, or an array of symbols
	//symbol REQUIRED
	//limit (default 50)
	//page # (default 1)
	protected function getNews($params = null){
		if(is_numeric($params['page'])){
			$page = $params['page'];
		} 
		if(is_numeric($params['limit'])){
			$limit = $params['limit'];
		}
		$reqparams['page'] = 1; //this is here but not sure how to paginate the results.// $page ?: 
		$reqparams['perpage'] = $limit ?: 50;
		
		if(empty($params['symbol'])){
			$returnsHtml = false;
			//get all news
			try{
				$response[$params['symbol']] = $this->guzzle->getAsync('https://www.nasdaq.com/api/v1/recent-articles/6/'.$reqparams['perpage']);
			}catch(\Exception $e){
				//just ignore it
			}
		}else{
			$returnsHtml = true;
			if(!is_array($params['symbol'])){
				$params['symbol'] = array($params['symbol']);
			}
			foreach($params['symbol'] as $symbol){
				try{
					$response[$symbol] = $this->guzzle->getAsync('https://www.nasdaq.com/api/v1/press-releases-fetcher/'.$symbol.'/0/'.$reqparams['perpage']);
				}catch(\Exception $e){
					//just ignore it
				}
			}
		}
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['requested'] = $params['symbol'];
		$mappedresponse['meta']['pagesize'] = $reqparams['perpage'];
		$mappedresponse['meta']['page'] = $reqparams['page'];

		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		foreach($response as $key => $responsess){			
			if($returnsHtml){
				$symbol = $key;
				if(!is_object($responsess['value'])){
					continue;
				}
				$dom = new \DOMDocument();
				@$dom->loadHTML(mb_convert_encoding($responsess['value']->getBody(), 'HTML-ENTITIES', 'UTF-8'));
				$domr = new \DomXPath($dom);
				$stocks = $domr->query('//div[@class="quote-press-release__card"]');
				//quote-press-release__card-timestamp
				//quote-press-release__link
				//quote-press-release__link -> firstChild
				foreach($stocks as $row){
					if(!is_object($row)){
						continue;
					}
					$mappedresponse['results'][$symbol][] = array(
					'ts' => $domr->query('.//div[@class="quote-press-release__card-timestamp"]', $row)->item(0)->textContent,
					'url' => 'https://www.nasdaq.com'.$domr->query('.//a[@class="quote-press-release__link"]',$row)->item(0)->getAttribute('href'),
					'title' => str_replace(" ", " ", $domr->query('.//span',$row)->item(0)->textContent) //do not change this, there's 0xCA left in here because of a character encoding error from LibXML (?)
					);
				}
			
			}else{
				$apires = json_decode($responsess['value']->getBody(),true);
				$symbol = 'LATEST';

				foreach($apires as $apik){
					
						$mappedresponse['results'][$symbol][] = [
							'symbols' => '',
							'keywords' => '',
							'ts' => $apik['ago'],
							'title' => $apik['title'],
							'url' => 'https://www.nasdaq.com'.$apik['url'],
							'src' => 'nasdaq.com',
							'sum' => $apik['title'],
							'img' => ''
						];
					
				}
			}
			
		}
		return $mappedresponse;
	}
		
	//Get metadata for a particular symbol, or an array of symbols
	//symbol REQUIRED
	protected function getTickerData($params = null){
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('getTickerData - required params: symbol is missing');
			return false;	
		}
		if(!is_array($params['symbol'])){
			$params['symbol'] = array($params['symbol']);
		}
		
		foreach($params['symbol'] as $symbol){
			$url = 'https://api.nasdaq.com/api/company/'.$symbol.'/company-profile';	
			try{
				$response[] = $this->guzzle->getAsync($url);
			}catch(\Exception $e){
				continue;
			}
		}
		
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		foreach($response as $responsess){
			$mappedresponse['status'] = 'ok';
			$mappedresponse['status'] = 'ok';
			$mappedresponse['meta']['requested'] = $params['symbol'];
			if(!is_object($responsess['value'])){
				continue;
			}
			$apires = json_decode($responsess['value']->getBody(),true);
			$symbol = $apires['data']['Symbol']['value'];
			$addrexp = explode(', ',$apires['data']['Address']['value']);
			$mappedresponse['results'][$symbol] = [
				'logo' => '',
				'symbol' => $symbol,
				'xchg' => '',
				'xchgsym' => '',
				'type' => 'CS',
				'name' => $apires['data']['CompanyName']['value'],
				'listdata' => '',
				'guids' => [ 
				//	'cik' => $apik['cik'],
				//	'bloomberg' => $apik['bloomberg'],
				//	'figi' => $apik['cik'],
				//	'lei' => $apik['lei']
				'not_provided_by_endpoint'
				],
				'sic' => '',
				'description' => $apires['data']['CompanyDescription']['value'],
				'country' => $addrexp[\count($addrexp) - 1],
				'industry' => $apires['data']['Industry']['value'],
				'sector' => $apires['data']['Sector']['value'],
				'marketcap' => '',
				'employees' => $apires['data']['Employees']['value'],
				'contact' => [
					'phone' => $apires['data']['Phone']['value'],
					'ceo' => '',
					'keyPeople' => $apires['data']['KeyExecutives']['value'],
					'url' => $apires['data']['CompanyUrl']['value'],
					'addr' => $apires['data']['Address']['value'],
					'addrComponents' => $addrexp,
					'state' => '',
					'country' => $addrexp[count($addrexp)-1],
				],
				'similar' => '',
				'tags' => '',
				'upd' => '',
				'active' => ''
			];
			
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