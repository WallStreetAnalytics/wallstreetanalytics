<?php
namespace PolygonIO\rest\reference;

/**
 * Class Reference
 * @package PolygonIO\rest\reference
 */
class Reference {
    public $tickers;
    public $tickerTypes;
    public $tickerDetails;
    public $tickerNews;
    public $markets;
    public $locales;
    public $stockSplits;
    public $stockDividends;
    public $stockFinancials;
    public $marketStatus;
    public $marketHolidays;

    /**
     * Reference constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->tickers = new Tickers($apiKey);
        $this->tickerTypes = new TickerTypes($apiKey);
        $this->tickerDetails = new TickerDetails($apiKey);
        $this->tickerNews = new TickerNews($apiKey);
        $this->markets = new Markets($apiKey);
        $this->locales = new Locales($apiKey);
        $this->stockSplits = new StockSplits($apiKey);
        $this->stockDividends = new StockDividends($apiKey);
        $this->stockFinancials = new StockFinancials($apiKey);
        $this->marketStatus = new MarketStatus($apiKey);
        $this->marketHolidays = new MarketHolidays($apiKey);
    }
}