<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class SnapshotSingleTicker extends RestResource {
    public function get($tickerSymbol) {
        return $this->_get('/v2/snapshot/locale/us/markets/stocks/tickers/'.$tickerSymbol);
    }

    protected function mapper($response)
    {
        $response['ticker'] = Mappers::snapshotTicker($response['ticker']);
        return $response;
    }
}