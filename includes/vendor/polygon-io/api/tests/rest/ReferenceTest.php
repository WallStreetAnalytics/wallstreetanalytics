<?php
use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

use PolygonIO\rest\reference\Reference;
use PolygonIO\rest\reference\Tickers;
use PolygonIO\rest\reference\TickerTypes;
use PolygonIO\rest\reference\TickerDetails;
use PolygonIO\rest\reference\TickerNews;
use PolygonIO\rest\reference\Markets;
use PolygonIO\rest\reference\Locales;
use PolygonIO\rest\reference\StockSplits;
use PolygonIO\rest\reference\StockDividends;
use PolygonIO\rest\reference\StockFinancials;
use PolygonIO\rest\reference\MarketStatus;
use PolygonIO\rest\reference\MarketHolidays;

class ReferenceTest extends TestCase {
    public function testExportAllTheMethodsFromReferenceApi() {
        $reference = new Reference('fake-api-key');
        $this->assertInstanceOf(Tickers::class, $reference->tickers);
        $this->assertInstanceOf(TickerTypes::class, $reference->tickerTypes);
        $this->assertInstanceOf(TickerDetails::class, $reference->tickerDetails);
        $this->assertInstanceOf(TickerNews::class, $reference->tickerNews);
        $this->assertInstanceOf(Markets::class, $reference->markets);
        $this->assertInstanceOf(Locales::class, $reference->locales);
        $this->assertInstanceOf(StockSplits::class, $reference->stockSplits);
        $this->assertInstanceOf(StockDividends::class, $reference->stockDividends);
        $this->assertInstanceOf(StockFinancials::class, $reference->stockFinancials);
        $this->assertInstanceOf(MarketStatus::class, $reference->marketStatus);
        $this->assertInstanceOf(MarketHolidays::class, $reference->marketHolidays);
    }

    public function testTickersGetCall() {
        $requestsContainer = [];

        $tickers = new Tickers('fake-api-key');
        $tickers->httpClient = $this->getHttpMock($requestsContainer);

        $tickers->get();

        $this->assertPath($requestsContainer, '/v2/reference/tickers');
    }

    public function testTickerTypesGetCall() {
        $requestsContainer = [];

        $tickerTypes = new TickerTypes('fake-api-key');
        $tickerTypes->httpClient = $this->getHttpMock($requestsContainer);

        $tickerTypes->get();

        $this->assertPath($requestsContainer, '/v2/reference/types');
    }

    public function testTickerDetailsGetCall() {
        $requestsContainer = [];
        $response = [
            'lei' => 'lei_remapped',
            'sic' => 'sic_remapped'
        ];
        $tickerDetails = new TickerDetails('fake-api-key');
        $tickerDetails->httpClient = $this->getHttpMock($requestsContainer, $response);

        $apiResponse = $tickerDetails->get('AAPL');

        $this->assertPath($requestsContainer, '/v1/meta/symbols/AAPL/company');
        $this->assertEquals('lei_remapped', $apiResponse['legalEntityIdentifier']);
        $this->assertEquals('sic_remapped', $apiResponse['standardIndustryClassification']);
    }

    public function testTickerNewsGetCall() {
        $requestsContainer = [];
        $tickerNews = new TickerNews('fake-api-key');
        $tickerNews->httpClient = $this->getHttpMock($requestsContainer);

        $tickerNews->get('AAPL');

        $this->assertPath($requestsContainer, '/v1/meta/symbols/AAPL/news');
    }

    public function testMarketsGetCall() {
        $requestsContainer = [];

        $markets = new Markets('fake-api-key');
        $markets->httpClient = $this->getHttpMock($requestsContainer);

        $markets->get();

        $this->assertPath($requestsContainer, '/v2/reference/markets');
    }

    public function testLocalesGetCall() {
        $requestsContainer = [];

        $locales = new Locales('fake-api-key');
        $locales->httpClient = $this->getHttpMock($requestsContainer);

        $locales->get();

        $this->assertPath($requestsContainer, '/v2/reference/locales');
    }

     public function testStockSplitsCall() {
        $requestsContainer = [];

        $stockSplits = new StockSplits('fake-api-key');
        $stockSplits->httpClient = $this->getHttpMock($requestsContainer);

        $stockSplits->get('AAPL');

        $this->assertPath($requestsContainer, '/v2/reference/splits/AAPL');
    }

    public function testStockDividendsCall() {
        $requestsContainer = [];

        $stockDividends = new StockDividends('fake-api-key');
        $stockDividends->httpClient = $this->getHttpMock($requestsContainer);

        $stockDividends->get('AAPL');

        $this->assertPath($requestsContainer, '/v2/reference/dividends/AAPL');
    }

    public function testStockFinancialsCall() {
        $requestsContainer = [];

        $stockFinancials = new StockFinancials('fake-api-key');
        $stockFinancials->httpClient = $this->getHttpMock($requestsContainer);

        $stockFinancials->get('AAPL');

        $this->assertPath($requestsContainer, '/v2/reference/financials/AAPL');
    }

    public function testMarketStatusGetCall() {
        $requestsContainer = [];

        $marketStatus = new MarketStatus('fake-api-key');
        $marketStatus->httpClient = $this->getHttpMock($requestsContainer);

        $marketStatus->get();

        $this->assertPath($requestsContainer, '/v1/marketstatus/now');
    }

    public function testMarketHolidaysGetCall() {
        $requestsContainer = [];

        $marketHolidays = new MarketHolidays('fake-api-key');
        $marketHolidays->httpClient = $this->getHttpMock($requestsContainer);

        $marketHolidays->get();

        $this->assertPath($requestsContainer, '/v1/marketstatus/upcoming');
    }

    private function getHttpMock(&$requestsContainer, $response=[]) {

        $mock = new MockHandler([
            new Response(200, [], json_encode($response)),
        ]);
        $handler = HandlerStack::create($mock);

        $history = Middleware::history($requestsContainer);
        $handler->push($history);

        return new Client(['handler' => $handler]);
    }

    private function assertPath($requests, $path) {
        $this->assertCount(1, $requests);
        $this->assertEquals($path, $requests[0]['request']->getUri()->getPath());
    }

}