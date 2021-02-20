<?php
namespace PolygonIO\rest\crypto;
use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class CryptoTest extends TestCase {

    public function testExportAllMethodsFromCryptoApi() {
        $crypto = new Crypto('fake api key');
        $this->assertInstanceOf(Aggregates::class, $crypto->aggregates);
        $this->assertInstanceOf(GroupedDaily::class, $crypto->groupedDaily);
        $this->assertInstanceOf(PreviousClose::class, $crypto->previousClose);
        $this->assertInstanceOf(CryptoExchanges::class, $crypto->cryptoExchanges);
        $this->assertInstanceOf(LastTradeForCryptoPair::class, $crypto->lastTradeForCryptoPair);
        $this->assertInstanceOf(DailyOpenClose::class, $crypto->dailyOpenClose);
        $this->assertInstanceOf(HistoricCryptoTrade::class, $crypto->historicCryptoTrade);
        $this->assertInstanceOf(SnapshotAllTickers::class, $crypto->snapshotAllTickers);
        $this->assertInstanceOf(SnapshotSingleTicker::class, $crypto->snapshotSingleTicker);
        $this->assertInstanceOf(SnapshotGainersLosers::class, $crypto->snapshotGainersLosers);
        $this->assertInstanceOf(SnapshotSingleTickerFullBook::class, $crypto->snapshotSingleTickerFullBook);
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

        $this->assertPath($requestsContainer, '/v2/aggs/grouped/locale/US/market/CRYPTO/2019-2-2');
    }

    public function testCryptoExchangesGetCall() {
        $requestsContainer = [];

        $cryptoExchanges = new CryptoExchanges('fake-api-key');
        $cryptoExchanges->httpClient = $this->getHttpMock($requestsContainer);

        $cryptoExchanges->get();

        $this->assertPath($requestsContainer, '/v1/meta/crypto-exchanges');
    }


    public function testLastTradeForCryptoPairGetCall() {
        $requestsContainer = [];

        $lastTradeForCryptoPair = new LastTradeForCryptoPair('fake-api-key');
        $lastTradeForCryptoPair->httpClient = $this->getHttpMock($requestsContainer);

        $lastTradeForCryptoPair->get('BTC', 'ETH');

        $this->assertPath($requestsContainer, '/v1/last/crypto/BTC/ETH');
    }

    public function testDailtOpenCloseGetCall() {
        $requestsContainer = [];

        $dailyOpenClose = new DailyOpenClose('fake-api-key');
        $dailyOpenClose->httpClient = $this->getHttpMock($requestsContainer);

        $dailyOpenClose->get('BTC', 'ETH', '2018-2-2');

        $this->assertPath($requestsContainer, '/v1/open-close/crypto/BTC/ETH/2018-2-2');
    }

    public function testHistoricCryptoTradeGetCall() {
        $requestsContainer = [];

        $historicCryptoTrade = new HistoricCryptoTrade('fake-api-key');
        $historicCryptoTrade->httpClient = $this->getHttpMock($requestsContainer, [
            'ticks' => [],
        ]);

        $historicCryptoTrade->get('BTC', 'ETH', '2018-2-2');

        $this->assertPath($requestsContainer, '/v1/historic/crypto/BTC/ETH/2018-2-2');
    }

    public function testSnapshotAllTickersGetCall() {
        $requestsContainer = [];

        $snapshotAllTickers = new SnapshotAllTickers('fake-api-key');
        $snapshotAllTickers->httpClient = $this->getHttpMock($requestsContainer, [
            'tickers' => [],
        ]);

        $snapshotAllTickers->get();

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/global/markets/crypto/tickers');
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
                    'p' => 'p',
                    's' => 's',
                    'x' => 'x',
                    'c' => 'c',
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

        $singleTicker->get('BTC-ETH');

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/global/markets/crypto/tickers/BTC-ETH');
    }

    public function testSnapshotGainersLosersGetCall() {
        $requestsContainer = [];

        $snapshotGainersLosers = new SnapshotGainersLosers('fake-api-key');
        $snapshotGainersLosers->httpClient = $this->getHttpMock($requestsContainer, [
            'tickers' => [],
        ]);

        $snapshotGainersLosers->get();

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/global/markets/crypto/gainers');
    }

    public function testSnapshotSingleTickerFullbookGetCall() {
        $requestsContainer = [];

        $snapshotSingleTickerFullBook = new SnapshotSingleTickerFullBook('fake-api-key');

        $snapshotSingleTickerFullBook->httpClient = $this->getHttpMock($requestsContainer, [
            'data' => [],
        ]);

        $snapshotSingleTickerFullBook->get('BTC-ETH');

        $this->assertPath($requestsContainer, '/v2/snapshot/locale/global/markets/crypto/tickers/BTC-ETH/book');
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