<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\RestResource;

class LastTradeForSymbol extends RestResource {
    public function get($tickerSymbol) {
        return $this->_get('/v1/last/stocks/'.$tickerSymbol);
    }
}