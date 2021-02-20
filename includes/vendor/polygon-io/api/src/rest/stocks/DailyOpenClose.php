<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\RestResource;

class DailyOpenClose extends RestResource {
    public function get($tickerSymbol, $date) {
        return $this->_get('/v1/open-close/'.$tickerSymbol.'/'.$date);
    }
}