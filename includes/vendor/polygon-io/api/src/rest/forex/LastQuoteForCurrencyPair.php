<?php
namespace PolygonIO\rest\forex;

use PolygonIO\rest\RestResource;

class LastQuoteForCurrencyPair extends RestResource {
    public function get($from, $to) {
        return $this->_get('/v1/last_quote/currencies/'.$from.'/'.$to);
    }
}