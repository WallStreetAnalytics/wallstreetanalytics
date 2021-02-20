<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class SnapshotAllTickers extends RestResource {
    public function get() {
        return $this->_get('/v2/snapshot/locale/us/markets/stocks/tickers');
    }

    protected function mapper($response)
    {
        $response['tickers'] = array_map(function ($ticker) {
            return Mappers::snapshotTicker($ticker);
        }, $response['tickers']);
        return $response;
    }
}