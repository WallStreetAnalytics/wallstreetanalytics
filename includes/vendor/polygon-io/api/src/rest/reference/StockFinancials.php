<?php
namespace PolygonIO\rest\reference;

use PolygonIO\rest\RestResource;

class StockFinancials extends RestResource {
    protected $defaultParams = [
       'limit' => 5,
    ];

    /**
     * @param $tickerSymbol
     * @param $params
     * @return mixed
     */
    public function get($tickerSymbol, $params = []) {
        return $this->_get('/v2/reference/financials/'.$tickerSymbol, $params);
    }
}