<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\RestResource;

class LastQuoteForSymbol extends RestResource {
    public function get($tickerSymbol) {
        return $this->_get('/v1/last_quote/stocks/'.$tickerSymbol);
    }
}