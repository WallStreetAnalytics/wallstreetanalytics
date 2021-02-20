<?php
namespace PolygonIO\rest\crypto;

class Crypto {
    public $aggregates;
    public $groupedDaily;
    public $previousClose;
    public $cryptoExchanges;
    public $lastTradeForCryptoPair;
    public $dailyOpenClose;
    public $historicCryptoTrade;
    public $snapshotAllTickers;
    public $snapshotGainersLosers;
    public $snapshotSingleTicker;
    public $snapshotSingleTickerFullBook;

    public function __construct($apiKey)
    {
        $this->previousClose = new PreviousClose($apiKey);
        $this->groupedDaily = new GroupedDaily($apiKey);
        $this->aggregates = new Aggregates($apiKey);
        $this->cryptoExchanges = new CryptoExchanges($apiKey);
        $this->lastTradeForCryptoPair = new LastTradeForCryptoPair($apiKey);
        $this->dailyOpenClose = new DailyOpenClose($apiKey);
        $this->historicCryptoTrade = new HistoricCryptoTrade($apiKey);
        $this->snapshotAllTickers = new SnapshotAllTickers($apiKey);
        $this->snapshotGainersLosers = new SnapshotGainersLosers($apiKey);
        $this->snapshotSingleTicker = new SnapshotSingleTicker($apiKey);
        $this->snapshotSingleTickerFullBook = new SnapshotSingleTickerFullBook($apiKey);
    }
}