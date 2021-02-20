<?php
namespace PolygonIO\rest\forex;

class Forex {
    public $aggregates;
    public $groupedDaily;
    public $previousClose;
    public $historicForexTick;
    public $realTimeCurrencyConversion;
    public $lastQuoteForCurrencyPair;
    public $snapshotAllTickers;
    public $snapshotGainersLosers;

    public function __construct($apiKey)
    {
        $this->groupedDaily = new GroupedDaily($apiKey);
        $this->aggregates = new Aggregates($apiKey);
        $this->previousClose = new PreviousClose($apiKey);
        $this->historicForexTick = new HistoricForexTick($apiKey);
        $this->realTimeCurrencyConversion = new RealTimeCurrencyConversion($apiKey);
        $this->lastQuoteForCurrencyPair = new LastQuoteForCurrencyPair($apiKey);
        $this->snapshotAllTickers = new SnapshotAllTickers($apiKey);
        $this->snapshotGainersLosers = new SnapshotGainersLosers($apiKey);
    }
}