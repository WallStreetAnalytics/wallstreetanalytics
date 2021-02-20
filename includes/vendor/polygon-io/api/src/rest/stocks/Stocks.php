<?php
namespace PolygonIO\rest\stocks;


class Stocks
{
    public $exchanges;
    public $historicTrades;
    public $historicTradesV2;
    public $historicQuotes;
    public $historicQuotesV2;
    public $lastTradeForSymbol;
    public $lastQuoteForSymbol;
    public $dailyOpenClose;
    public $conditionMappings;
    public $snapshotAllTickers;
    public $snapshotSingleTicker;
    public $snapshotGainersLosers;
    public $previousClose;
    public $aggregates;
    public $groupedDaily;

    public function __construct($apiKey)
    {
        $this->exchanges = new Exchanges($apiKey);
        $this->historicTrades = new HistoricTrades($apiKey);
        $this->historicTradesV2 = new HistoricTradesV2($apiKey);
        $this->historicQuotes = new HistoricQuotes($apiKey);
        $this->historicQuotesV2 = new HistoricQuotesV2($apiKey);
        $this->lastTradeForSymbol = new LastTradeForSymbol($apiKey);
        $this->lastQuoteForSymbol = new LastQuoteForSymbol($apiKey);
        $this->dailyOpenClose = new DailyOpenClose($apiKey);
        $this->conditionMappings = new ConditionMappings($apiKey);
        $this->snapshotAllTickers = new SnapshotAllTickers($apiKey);
        $this->snapshotSingleTicker = new SnapshotSingleTicker($apiKey);
        $this->snapshotGainersLosers = new SnapshotGainersLosers($apiKey);
        $this->previousClose = new PreviousClose($apiKey);
        $this->aggregates = new Aggregates($apiKey);
        $this->groupedDaily = new GroupedDaily($apiKey);
    }
}