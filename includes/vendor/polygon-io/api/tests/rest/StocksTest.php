<?php
use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

use PolygonIO\rest\stocks\Stocks;
use PolygonIO\rest\stocks\Exchanges;
use PolygonIO\rest\stocks\HistoricTrades;
use PolygonIO\rest\stocks\HistoricTradesV2;
use PolygonIO\rest\stocks\HistoricQuotes;
use PolygonIO\rest\stocks\HistoricQuotesV2;
use PolygonIO\rest\stocks\LastTradeForSymbol;
use PolygonIO\rest\stocks\LastQuoteForSymbol;
use PolygonIO\rest\stocks\DailyOpenClose;
use PolygonIO\rest\stocks\ConditionMappings;
use PolygonIO\rest\stocks\SnapshotAllTickers;
use PolygonIO\rest\stocks\SnapshotSingleTicker;
use PolygonIO\rest\stocks\SnapshotGainersLosers;
use PolygonIO\rest\stocks\PreviousClose;
use PolygonIO\rest\stocks\Aggregates;
use PolygonIO\rest\stocks\GroupedDaily;

class StocksTest extends TestCase {

    public function testExportAllMethodsFromStocksApi() {
        $stocks = new Stocks('fake api key');
        $this->assertInstanceOf(Exchanges::class, $stocks->exchanges);
        $this->assertInstanceOf(HistoricTrades::class, $stocks->historicTrades);
        $this->assertInstanceOf(HistoricTradesV2::class, $stocks->historicTradesV2);
        $this->assertInstanceOf(HistoricQuotes::class, $stocks->historicQuotes);
        $this->assertInstanceOf(HistoricQuotesV2::class, $stocks->historicQuotesV2);
        $this->assertInstanceOf(LastTradeForSymbol::class, $stocks->lastTradeForSymbol);
        $this->assertInstanceOf(LastQuoteForSymbol::class, $stocks->lastQuoteForSymbol);
        $this->assertInstanceOf(DailyOpenClose::class, $stocks->dailyOpenClose);
        $this->assertInstanceOf(ConditionMappings::class, $stocks->conditionMappings);
        $this->assertInstanceOf(SnapshotAllTickers::class, $stocks->snapshotAllTickers);
        $this->assertInstanceOf(SnapshotSingleTicker::class, $stocks->snapshotSingleTicker);
        $this->assertInstanceOf(SnapshotGainersLosers::class, $stocks->snapshotGainersLosers);
        $this->assertInstanceOf(PreviousClose::class, $stocks->previousClose);
        $this->assertInstanceOf(Aggregates::class, $stocks->aggregates);
        $this->assertInstanceOf(GroupedDaily::class, $stocks->groupedDaily);
    }

    public function testExchangesGetCall() {
        $requestsContainer = [];

        $exchanges = new Exchanges('fake-api-key');
        $exchanges->httpClient = $this->getHttpMock($requestsContainer);

        $exchanges->get();

        $this->assertPath($requestsContainer, '/v1/meta/exchanges');
    }

    public function testHistoricTradesGetCall() {
        $requestsContainer = [];

        $historicTrades = new HistoricTrades('fake-api-key');
        $historicTrades->httpClient = $this->getHttpMock($requestsContainer, [
            'ticks' => [],
        ]);

        $historicTrades->get('AAPL', '2019-2-2');

        $this->assertPath($requestsContainer, '/v1/historic/trades/AAPL/2019-2-2');

    }

    public function testHistoricTradesV2GetCall() {
        $requestsContainer = [];

        $historicTradesV2 = new HistoricTradesV2('fake-api-key');
        $historicTradesV2->httpClient = $this->getHttpMock($requestsContainer, [
            'ticks' => [],
        ]);

        $historicTradesV2->get('AAPL', '2019-2-2');

        $this->assertPath($requestsContainer, '/v2/ticks/stocks/trades/AAPL/2019-2-2');

    }

    public function testHistoricQuotesGetCall() {
        $requestsContainer = [];

        $historicQuotes = new HistoricQuotes('fake-api-key');
        $historicQuotes->httpClient = $this->getHttpMock($requestsContainer, [
            'ticks' => [],
        ]);

        $historicQuotes->get('AAPL', '2019-2-2');

        $this->assertPath($requestsContainer, '/v1/historic/quotes/AAPL/2019-2-2');
    }

    public function testHistoricQuotesV2GetCall() {
        $requestsContainer = [];

        $historicQuotesV2 = new HistoricQuotesV2('fake-api-key');
        $historicQuotesV2->httpClient = $this->getHttpMock($requestsContainer, [
            'results' => [],
        ]);

        $historicQuotesV2->get('AAPL', '2019-2-2');

        $this->assertPath($requestsContainer, '/v2/ticks/stocks/nbbo/AAPL/2019-2-2');
    }


    public function testLastTradeForSymbolGetCall() {
        $requestsContainer = [];

        $lastTradeForSymbol = new LastTradeForSymbol('fake-api-key');
        $lastTradeForSymbol->httpClient = $this->getHttpMock($requestsContainer);

        $lastTradeForSymbol->get('AAPL');

        $this->assertPath($requestsContainer, '/v1/last/stocks/AAPL');
    }

    public function testLastQuoteForSymbolGetCall() {
        $requestsContainer = [];

        $lastTradeForSymbol = new LastQuoteForSymbol('fake-api-key');
        $lastTradeForSymbol->httpClient = $this->getHttpMock($requestsContainer);

        $lastTradeForSymbol->get('AAPL');

        $this->assertPath($requestsContainer, '/v1/last_quote/stocks/AAPL');
    }

    public function testDailyOpenCloseGetCall() {
        $requestsContainer = [];

        $dailyOpenClose = new DailyOpenClose('fake-api-key');
        $dailyOpenClose->httpClient = $this->getHttpMock($requestsContainer);

        $dailyOpenClose->get('AAPL', '2019-2-2');

        $this->assertPath($requestsContainer, '/v1/open-close/AAPL/2019-2-2');
    }

    public function testConditionMappingsGetCall() {
        $requestsContainer = [];

        $conditionMappings = new ConditionMappings('fake-api-key');
        $conditionMappings->httpClient = $this->getHttpMock($requestsContainer);

        $conditionMappings->get();

        $this->assertPath($requestsContainer, '/v1/meta/conditions/trades');
    }

    public function testSnapshotAllTickersGetCall() {
        $requestsContainer = [];

        $snapshotAllTickers = new SnapshotAllTickers('fake-api-key');
        $snapshotAllTickers->httpClient = $this->getHttpMock($requestsContainer, [
            'tickers' => [],
        ]);

        $snapshotAllTickers->get();

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/us/markets/stocks/tickers');
    }

    public function testSnapshotSingleTickerGetCall() {
        $requestsContainer = [];

        $singleTicker = new SnapshotSingleTicker('fake-api-key');

        $singleTicker->httpClient = $this->getHttpMock($requestsContainer, [
            'ticker' => [
                'day' => [
                    'c' => 'c',
                    'h' => 'h',
                    'l' => 'l',
                    'o' => 'o',
                    'v' => 'v',
                ],
                'lastTrade' => [
                    'c1' => 'c1',
                    'c2' => 'c2',
                    'c3' => 'c3',
                    'c4' => 'c4',
                    'e' => 'e',
                    'p' => 'p',
                    's' => 's',
                    't' => 't',
                ],
                'lastQuote' => [
                    'p' => 'p',
                    's' => 's',
                    'S' => 'S',
                    'P' => 'P',
                    't' => 't',
                ],
                'min' => [
                    'c' => 'c',
                    'h' => 'h',
                    'l' => 'l',
                    'o' => 'o',
                    'v' => 'v',
                ],
                'prevDay' => [
                    'c' => 'c',
                    'h' => 'h',
                    'l' => 'l',
                    'o' => 'o',
                    'v' => 'v',
                ],
            ],
        ]);

        $singleTicker->get('AAPL');

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/us/markets/stocks/tickers/AAPL');
    }

    public function testSnapshotGainersLosersGetCall() {
        $requestsContainer = [];

        $snapshotGainersLosers = new SnapshotGainersLosers('fake-api-key');
        $snapshotGainersLosers->httpClient = $this->getHttpMock($requestsContainer, [
            'tickers' => [],
        ]);

        $snapshotGainersLosers->get();

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/us/markets/stocks/gainers');
    }

    public function testPreviousCloseGetCall() {
        $requestsContainer = [];

        $previousClose = new PreviousClose('fake-api-key');
        $previousClose->httpClient = $this->getHttpMock($requestsContainer, [
            'results' => [],
        ]);

        $previousClose->get('AAPL');

        $this->assertPath($requestsContainer, '/v2/aggs/ticker/AAPL/prev');
    }

    public function testAggregatesCloseGetCall() {
        $requestsContainer = [];

        $previousClose = new Aggregates('fake-api-key');
        $previousClose->httpClient = $this->getHttpMock($requestsContainer, [
            'results' => [],
        ]);

        $previousClose->get('AAPL', 1, '2018-2-2', '2019-2-2');

        $this->assertPath($requestsContainer, '/v2/aggs/ticker/AAPL/range/1/days/2018-2-2/2019-2-2');
    }

    public function testGroupedDailyGetCall() {
        $requestsContainer = [];

        $groupedDaily = new GroupedDaily('fake-api-key');
        $groupedDaily->httpClient = $this->getHttpMock($requestsContainer, [
            'results' => [],
        ]);

        $groupedDaily->get('2019-2-2');

        $this->assertPath($requestsContainer, '/v2/aggs/grouped/locale/US/market/STOCKS/2019-2-2');
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