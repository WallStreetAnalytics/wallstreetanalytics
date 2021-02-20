<?php
//Specific for PolygonIO PHP Client...
//Use this file as a reference when adapting other APIs to this software's required format
//For the most part, our data format is based on the Polygon API endpoints, so review their docs online

//Not all fields are required, so if your API endpoints don't have the same exact data, then it's probably OK to leave it off, or you can combine multiple API calls in one function
//You must leave the function names the same, unless you want to rework the rest of the scripts
//You can add your own functions to the class, then add them to StocksAPI->getData in ../base/api.php, and implement them however you see fit

namespace Stocks;
include(__DIR__.'/api.config.php');

class polygonapi extends StocksAPI {

	//This converts identifiers from API to text values
	private $friendlytapeids = [1 => 'NYSE', 2 => 'NYSE ARCA', 3 => 'NASDAQ'];	
	/*
	There are 3 tapes which define which exchange the ticker is listed on. These are integers in our objects which represent the letter of the alphabet. Eg: 1 = A, 2 = B, 3 = C.
	Tape A is NYSE listed securities
	Tape B is NYSE ARCA / NYSE American
	Tape C is NASDAQ
	*/
	
	//Constructor
	public function __construct($apicfg){
		$this->apiKey = $apicfg->apiKey;
		$this->apiRateLimit = $apicfg->apiRateLimit;
		$this->providerID = $apicfg->providerID;
		$this->rest = new \PolygonIO\rest\Rest($this->apiKey);
	}
	
	//Test request to get API status
	protected function sendTestRequest() {	
		$response = $this->rest->stocks->exchanges->get();
		if(!empty($response[0]['market'])){
			return true;
		}else{
			return false;
		}
	}
	
	//Get Exchange IDs so that we can convert from the ID in the ticker to the exchange name
	protected function getExchangeIDs() {	
		try{		
			$response = $this->rest->stocks->exchanges->get();
		}catch(\Exception $e) {
			return false;
		}
		$mappedresponse['status'] = 'ok';
		if(!empty($response[0]['market'])){
			foreach($response as $exch){
				$mappedresponse['results'][$exch['id']] = [
					'id' => $exch['id'],
					'type' => $exch['type'],
					'mkt' => $exch['market'],
					'mic' => $exch['mic'],
					'name' => $exch['name'],
					'tape' => $exch['tape'],
					'code' => $exch['code']
				];
			}
			return $mappedresponse;
		}else{
			return false;
		}
	}
	
	
	//Get Ticker Snapshots
	//Params can be:
	//$params['tickertype'] = 'gainers' or 'losers', or null to check all or single symbol
	//$params['symbol'] = 'AAPL' or null for all
	protected function getTicker($params = null){

		if($params['tickertype'] == 'gainers' || $params['tickertype'] == 'losers'){
			//get gainers or losers
			try{
				$apik = $this->rest->stocks->snapshotGainersLosers->get($params['tickertype']);		
			}catch(\Exception $e) {
				return false;
			}
			if(!$apik){
				return false;
			}
		}else{
			if(empty($params['symbol'])){
				//get all tickers
				try{
					$apik = $this->rest->stocks->snapshotAllTickers->get();		
				}catch(\Exception $e) {
					return false;
				}
				if(!$apik){
					return false;
				}
			}else{
				//Get single ticker
				//This is mapped to the same format as all tickers so we can use the same loop
				if(!is_array($params['symbol'])){
					$symbols[0] = $params['symbol'];
				}else{
					$symbols = $params['symbol'];
				}
				foreach ($symbols as $symbol){
					
					try{
						$apik['tickers'][] = $this->rest->stocks->snapshotSingleTicker->get($symbol)['ticker'];		
					}catch(\Exception $e) {
						continue;
					}
				}
				
				if(!$apik['tickers'][0]){
					return false;
				}
				
			}
		}
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta'] = $params;
		foreach($apik['tickers'] ?: $apik as $response){
			$mappedresponse['results'][] = [
				'upd' => $response['updated'],
				'pctchgtd' => $response['todaysChangePerc'],
				'chgtd' => $response['todaysChange'],
				'tkr' => $response['ticker'],
				'day' => [ //day
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
				],
				'lt' => [ //last trade
					'c' => $response['lastTrade']['c'], //trade condition
					'i' => $response['lastTrade']['i'], //trade id
					'p' => $response['lastTrade']['p'], //price
					's' => $response['lastTrade']['s'], //size
					'x' => $response['lastTrade']['x'], //PolygonIO Exchange ID //https://api.polygon.io/v1/meta/exchanges?&apiKey={APIKEY}
					't' => $response['lastTrade']['t'] //timestamp unix
				],
				'bar' => [ //one-minute bar
					'av' => $response['prevDay']['av'], //accumulated volume
					'o' => $response['prevDay']['o'], //opening price (minute)
					'h' => $response['prevDay']['h'], //high price (minute)
					'l' => $response['prevDay']['l'], //low price (minute)
					'c' => $response['prevDay']['c'], //close price (minute)
					'v' => $response['prevDay']['v'], //trading volume (minute)
					'vw' => $response['prevDay']['vw'] //volume weighted average price (minute)
				]
			];
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
			
			if(is_numeric($params['limit'])){
				$limit = $params['limit'];
			}
			
			if(is_numeric($params['startts'])){
				$offset = $params['startts']; //[timestamp] = The timestamp offset, used for pagination. This is the offset at which to start the results. Using the timestamp of the last result as the offset will give you the next page of results.
			}
			
			foreach($symbols as $symbol){
				try{
					$apik[] = $this->rest->stocks->historicTradesV2->get($symbol, $params['date'], ['limit' => $limit, 'timestamp' => $offset]);		
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
			$mappedresponse['count'] = count($apiss['results']);
			//return $mappedresponse;
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
	
	
	//Get Historic Quotes
	//AKA NBBO
	//Params can be:
	//Symbol REQUIRED
	//Date REQUIRED
	protected function getHistoricQuotes($params = null){

		if(empty($params['symbol']) || empty($params['date'])){
			//get all tickers
			makeJSONerrorAndExit('Historic quotes - required params: symbol or date are missing');
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
		
		if(is_numeric($params['limit'])){
			$limit = $params['limit'];
		}
		
		if(is_numeric($params['startts'])){
			$offset = $params['startts']; //[timestamp] = The timestamp offset, used for pagination. This is the offset at which to start the results. Using the timestamp of the last result as the offset will give you the next page of results.
		}
				
		foreach($symbols as $symbol){
			try{
				$apik[] = $this->rest->stocks->historicQuotesV2->get($symbol, $params['date'], ['limit' => $limit, 'timestamp' => $offset]);		
			}catch(\Exception $e) {
				continue;
			}
		}
		
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['tickers'] = $symbols;
		$mappedresponse['meta']['date'] = $params['date'];
		$mappedresponse['config']['tapenames'] = $this->friendlytapeids;
		foreach($apik as $apikss){
			foreach($apikss['results'] as $response){
				$mappedresponse['results'][] = [
						'ts' => [
							't' => $response['t'], //ns accurate SIP timestamp
							'y' => $response['y'], //ns accurate p/e timestamp
							'f' => $response['f'] //ns accurate TRF(Trade Reporting Facility) Unix Timestamp
						],
						'bid' => [
							'p' => $response['p'], //bid price
							'x' => $response['x'], //exchange identifier
							's' => $response['s'], //bid size
						],
						'ask' => [
							'p' => $response['P'], //ask price
							'x' => $response['X'], //exchange identifier
							's' => $response['S'], //ask size
						],
						'seq' => $response['q'], //sequence of trade events
						'c' => $response['c'], //trade condition codes
						'i' => $response['s'], //trade indicators (https://polygon.io/glossary/us/stocks/conditions-indicators)
						'z' => $response['z'], //tape type
				];
			}
		}
		return $mappedresponse;
	}
	
	//Get Aggregates (bars)
	//Params (lowercase names!) can be:
	//Symbol REQUIRED, array or single symbol
	//Multiplier (default 1)
	//Timespan (min,hour,day,week,month,quarter,year) default = day
	//StartDate (default today, or YYYY-MM-DD)
	//EndDate (default today, or YYYY-MM-DD)
	//Unadjusted (Show data as unadjusted for splits? default false)
	//Sort (default asc. Or asc, desc)
	//Limit (Max 50000 default 5000)
	
	protected function getAgg($params = null){

		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('Aggregate/Bar - required params: symbol is missing');
			return false;	
		}
		
		if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$params['startdate'])) {
			unset($params['startdate']);
		}
		if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$params['enddate'])) {
			unset($params['enddate']);
			if(isset($params['startdate'])){
				//have start date but not end date
				$params['enddate'] = $params['startdate']; // we do not have an end date set, so just get one day of data
			}
		}else{
			if(!isset($params['startdate'])){
				//have end date but not start date
				$params['startdate'] = $params['enddate']; // we do not have a start date set, so just get one day of data
			}
		}
		
		if(!in_array($params['timespan'],['min','hour','day','week','month','quarter','year'])){
			$params['timespan'] = 'day';
		}
		
		$datetime = new \DateTime(strtotime(time()), new \DateTimeZone('America/New_York'));
		$today = $datetime->format('Y-m-d');
		//if $today is a weekend, we need to get the last trading day, or else the request will fail
		//We should probably check if this is a holiday, but I'm too lazy to do that
		//So, just use the closest Friday.
		if($datetime->format('w') == '6' || $datetime->format('w') == '7'){
			$today = $datetime->modify('friday this week')->format('Y-m-d');
		}
		
		if(!is_array($params['symbol'])){
			$params['symbol'] = array($params['symbol']);
		}
		
		foreach($params['symbol'] as $symbol){
			try{
				$apires[$symbol] = $this->rest->stocks->aggregates->get($symbol, $params['multiplier'] ?: '1', $params['startdate'] ?: $today, $params['enddate'] ?: $today, $params['timespan'] ?: 'day', 
				['unadjusted' => $params['unadjusted'] ?: false, 'sort' => $params['sort'] ?: 'desc', 'limit' => $params['limit'] ?: 5000] );					
			}catch(\Exception $e) {
				continue;
			}
		}
		
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['start'] = $params['startdate'] ?: $today;
		$mappedresponse['meta']['end'] = $params['enddate'] ?: $today;
		$mappedresponse['meta']['period'] = $params['timespan'] ?: 'day';
		$mappedresponse['meta']['requested'] = $params['symbol'];
		foreach($apires as $symbol=>$apik){
			if($apik['count'] < 1){
				continue;
			}
			$mappedresponse['results'][$symbol]['ticker'] = $apik['ticker'];
			$mappedresponse['results'][$symbol]['splitadj'] = $apik['adjusted'];
			$mappedresponse['results'][$symbol]['count'] = $apik['count'];
			foreach($apik['results'] as $response){
				$mappedresponse['results'][$symbol]['points'][] = [
						'ts' => $response['t'], //unix timestamp, start of agg window
						'num' => $response['n'], //# of items in agg window - this is not always available?
						'vw' => $response['vw'], //vol weighted avg price
						'c' => $response['c'], //close price
						'l' => $response['l'], //low price
						'h' => $response['h'], //high price
						'o' => $response['o'] //open price
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
		if(empty($params['symbol'])){
			//get all tickers
			makeJSONerrorAndExit('getNews - required params: symbol is missing');
			return false;	
		}
		if(!is_array($params['symbol'])){
			$params['symbol'] = array($params['symbol']);
		}
		
		$reqparams['perpage'] = $limit ?: 50;
		$reqparams['page'] = $limit ?: 1;
		
		foreach($params['symbol'] as $symbol){
			try{
				$apires[$symbol] = $this->rest->reference->tickerNews->get($symbol, $reqparams);	
			}catch(\Exception $e) {
				continue; 
			}
		}
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['requested'] = $params['symbol'];
		$mappedresponse['meta']['parameters'] = $reqparams;
		foreach($apires as $symbol=>$apika){
			foreach($apika as $apik){
				$mappedresponse['results'][$symbol][] = [
					'symbols' => $apik['symbols'],
					'keywords' => $apik['keywords'],
					'ts' => $apik['timestamp'],
					'title' => $apik['title'],
					'url' => $apik['url'],
					'src' => $apik['source'],
					'sum' => $apik['summary'],
					'img' => $apik['image']
				];
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
			try{
				$apires[$symbol] = $this->rest->reference->tickerDetails->get($symbol);	
			}catch(\Exception $e) {
				continue;
			}
		}
		
		if(!$apires){
			return false;
		}
		$mappedresponse['status'] = 'ok';
		$mappedresponse['meta']['requested'] = $params['symbol'];
		foreach($apires as $symbol=>$apik){
				$mappedresponse['results'][$symbol] = [
					'logo' => $apik['logo'],
					'symbol' => $apik['symbol'],
					'xchg' => $apik['exchange'],
					'xchgsym' => $apik['exchangeSymbol'],
					'type' => $apik['type'],
					'name' => $apik['name'],
					'listdata' => $apik['listdata'],
					'guids' => [ 
						'cik' => $apik['cik'],
						'bloomberg' => $apik['bloomberg'],
						'figi' => $apik['cik'],
						'lei' => $apik['lei']
					],
					'sic' => $apik['sic'],
					'description' => $apik['description'],
					'country' => $apik['country'],
					'industry' => $apik['industry'],
					'sector' => $apik['sector'],
					'marketcap' => $apik['marketcap'],
					'employees' => $apik['employees'],
					'contact' => [
						'phone' => $apik['phone'],
						'ceo' => $apik['ceo'],
						'url' => $apik['url'],
						'addr' => $apik['hq_address'],
						'state' => $apik['hq_state'],
						'country' => $apik['hq_country'],
					],
					'similar' => $apik['similar'],
					'tags' => $apik['tags'],
					'upd' => $apik['updated'],
					'active' => $apik['active']
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