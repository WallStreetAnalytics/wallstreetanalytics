<?php
//Specific for LowFloat and HighShortInterest scraper...
//Use this file as a reference when adapting other similar APIs to this software's required format
namespace Stocks;
include(__DIR__.'/api.config.php');

use \GuzzleHttp\Client;
use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Handler\CurlMultiHandler;

class lowfloat extends StocksAPI {
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
		$url = 'https://www.lowfloat.com/';
		try{
			$send = $this->guzzle->request('GET', $url);
		}catch(\Exception $e){
			return false;
		}
		
		$dom = new \DOMDocument();
		@$dom->loadHTML($send->getBody());
		$domr = new \DomXPath($dom);
		$stocks = $domr->query('//table[@class="stocks"]//tr');
		$iteration = 0;
		foreach($stocks as $row){
			$detailsouter = $domr->query('.//td[@class!="tblhdr"]', $row);
			if($detailsouter->length == 0){
				continue;
			}
			if(is_object($detailsouter)){
				for($i = 0; $i <= 7; $i++){
					switch($i){
						case 0:
							$result[$iteration]['sym'] = $detailsouter->item($i)->textContent;
						break;
						case 1:
							$result[$iteration]['name'] = $detailsouter->item($i)->textContent;
						break;
						case 2:
							$result[$iteration]['x'] = $detailsouter->item($i)->textContent;
						break;
						case 3:
							$result[$iteration]['shortInt'] = $detailsouter->item($i)->textContent;
						break;
						case 4:
							$result[$iteration]['float'] = $detailsouter->item($i)->textContent;
						break;
						case 5:
							$result[$iteration]['outstanding'] = $detailsouter->item($i)->textContent;
						break;
						case 6:
							$result[$iteration]['industry'] = $detailsouter->item($i)->textContent;
							$iteration++;
						break;
					}
				}
			}
			break;
		}
		
		if(!empty($result[0]['sym'])){
			return true;
		}else{
			return false;
		}
	}
	
	protected function getLowFloatOtcbb() {	
		$url = 'https://www.lowfloat.com/all_with_otcbb/';
		try{
			$send = $this->guzzle->request('GET', $url);
		}catch(\Exception $e){
			return false;
		}
		
		$dom = new \DOMDocument();
		@$dom->loadHTML($send->getBody());
		$domr = new \DomXPath($dom);
		$pagecount = $domr->query('//p[@class="nav"]')->item(0)->textContent;
		preg_match('/.*?-\ (.*?)\ of\ (.*?)\ .*/',$pagecount,$pagination);
		$resultsperpage = $pagination[1];
		$totalresults = $pagination[2];
		$pages = ceil($totalresults/$resultsperpage);
		for($x = 1; $x <= $pages; $x++){
			$url = 'https://www.lowfloat.com/all_with_otcbb/'.$x;
			try{
				$response[] = $this->guzzle->getAsync($url);
			}catch(\Exception $e){
				continue;
			}
		}
		$iteration = 0;
		unset($dom);
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		$result['status'] = 'ok';
		foreach($response as $responsess){
			$dom = new \DOMDocument();
			if(!is_object($responsess['value'])){
				continue;
			}
			@$dom->loadHTML($responsess['value']->getBody());
			$domr = new \DomXPath($dom);
			$stocks = $domr->query('//table[@class="stocks"]//tr');
			foreach($stocks as $row){
				$detailsouter = $domr->query('.//td[@class!="tblhdr"]', $row);
				if($detailsouter->length == 0){
					continue;
				}
				if(is_object($detailsouter)){
					for($i = 0; $i <= 7; $i++){
						switch($i){
							case 0:
								$result['results'][$iteration]['sym'] = $detailsouter->item($i)->textContent;
							break;
							case 1:
								$result['results'][$iteration]['name'] = $detailsouter->item($i)->textContent;
							break;
							case 2:
								$result['results'][$iteration]['x'] = $detailsouter->item($i)->textContent;
							break;
							case 3:
								$result['results'][$iteration]['shortInt'] = $detailsouter->item($i)->textContent;
							break;
							case 4:
								$result['results'][$iteration]['float'] = $detailsouter->item($i)->textContent;
							break;
							case 5:
								$result['results'][$iteration]['outstanding'] = $detailsouter->item($i)->textContent;
							break;
							case 6:
								$result['results'][$iteration]['industry'] = $detailsouter->item($i)->textContent;
								$iteration++;
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	
	protected function getLowFloat() {	
		$url = 'https://www.lowfloat.com/all/';
		try{
			$send = $this->guzzle->request('GET', $url);
		}catch(\Exception $e){
			return false;
		}
		
		$dom = new \DOMDocument();
		@$dom->loadHTML($send->getBody());
		$domr = new \DomXPath($dom);
		$pagecount = $domr->query('//p[@class="nav"]')->item(0)->textContent;
		preg_match('/.*?-\ (.*?)\ of\ (.*?)\ .*/',$pagecount,$pagination);
		$resultsperpage = $pagination[1];
		$totalresults = $pagination[2];
		$pages = ceil($totalresults/$resultsperpage);
		for($x = 1; $x <= $pages; $x++){
			$url = 'https://www.lowfloat.com/all/'.$x;
			try{
				$response[] = $this->guzzle->getAsync($url);
			}catch(\Exception $e){
				continue;
			}
		}
		$iteration = 0;
		unset($dom);
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		$result['status'] = 'ok';
		foreach($response as $responsess){
			$dom = new \DOMDocument();
			if(!is_object($responsess['value'])){
				continue;
			}
			@$dom->loadHTML($responsess['value']->getBody());
			$domr = new \DomXPath($dom);
			$stocks = $domr->query('//table[@class="stocks"]//tr');
			foreach($stocks as $row){
				$detailsouter = $domr->query('.//td[@class!="tblhdr"]', $row);
				if($detailsouter->length == 0){
					continue;
				}
				if(is_object($detailsouter)){
					for($i = 0; $i <= 7; $i++){
						switch($i){
							case 0:
								$result['results'][$iteration]['sym'] = $detailsouter->item($i)->textContent;
							break;
							case 1:
								$result['results'][$iteration]['name'] = $detailsouter->item($i)->textContent;
							break;
							case 2:
								$result['results'][$iteration]['x'] = $detailsouter->item($i)->textContent;
							break;
							case 3:
								$result['results'][$iteration]['shortInt'] = $detailsouter->item($i)->textContent;
							break;
							case 4:
								$result['results'][$iteration]['float'] = $detailsouter->item($i)->textContent;
							break;
							case 5:
								$result['results'][$iteration]['outstanding'] = $detailsouter->item($i)->textContent;
							break;
							case 6:
								$result['results'][$iteration]['industry'] = $detailsouter->item($i)->textContent;
								$iteration++;
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	
	
	protected function getHighShort() {	
		
		$url = 'https://www.highshortinterest.com/all/';
		try{
			$send = $this->guzzle->request('GET', $url);
		}catch(\Exception $e){
			return false;
		}
		
		$dom = new \DOMDocument();
		@$dom->loadHTML($send->getBody());
		$domr = new \DomXPath($dom);
		$pagecount = $domr->query('//p[@class="nav"]')->item(0)->textContent;
		preg_match('/.*?-\ (.*?)\ of\ (.*?)\ .*/',$pagecount,$pagination);
		$resultsperpage = $pagination[1];
		$totalresults = $pagination[2];
		$pages = ceil($totalresults/$resultsperpage);
		for($x = 1; $x <= $pages; $x++){
			$url = 'https://www.highshortinterest.com/all/'.$x;
			try{
				$response[] = $this->guzzle->getAsync($url);
			}catch(\Exception $e){
				continue;
			}
		}
		$iteration = 0;
		unset($dom);
		$response = \GuzzleHttp\Promise\Utils::settle($response)->wait();
		foreach($response as $responsess){
			$dom = new \DOMDocument();
			if(!is_object($responsess['value'])){
				continue;
			}
			@$dom->loadHTML($responsess['value']->getBody());
			$domr = new \DomXPath($dom);
			$stocks = $domr->query('//table[@class="stocks"]//tr');
			$result['status'] = 'ok';
			foreach($stocks as $row){
				$detailsouter = $domr->query('.//td[@class!="tblhdr"]', $row);
				if($detailsouter->length == 0){
					continue;
				}
				if(is_object($detailsouter)){
					for($i = 0; $i <= 7; $i++){
						switch($i){
							case 0:
								$result['results'][$iteration]['sym'] = $detailsouter->item($i)->textContent;
							break;
							case 1:
								$result['results'][$iteration]['name'] = $detailsouter->item($i)->textContent;
							break;
							case 2:
								$result['results'][$iteration]['x'] = $detailsouter->item($i)->textContent;
							break;
							case 3:
								$result['results'][$iteration]['shortInt'] = $detailsouter->item($i)->textContent;
							break;
							case 4:
								$result['results'][$iteration]['float'] = $detailsouter->item($i)->textContent;
							break;
							case 5:
								$result['results'][$iteration]['outstanding'] = $detailsouter->item($i)->textContent;
							break;
							case 6:
								$result['results'][$iteration]['industry'] = $detailsouter->item($i)->textContent;
								$iteration++;
							break;
						}
					}
				}
			}
		}
		return $result;
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
			case 'lowfloat':
				return [
						'all' => $this->getLowFloat($params) ?: ['status' => 'error'], 
						'allWithOTCBB' => $this->getLowFloatOtcbb($params)  ?: ['status' => 'error']
					];
			break;
			case 'highshort':
				return $this->getHighShort($params);
			break;
			default:
				makeJSONerrorAndExit('Invalid customOperation',['availableCustomOperations' => ['lowfloat','highshort']]);
			break;
		}
		return $result;
	}
}

?>