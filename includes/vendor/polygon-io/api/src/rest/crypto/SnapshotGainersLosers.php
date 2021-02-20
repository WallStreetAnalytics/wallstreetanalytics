<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class SnapshotGainersLosers extends RestResource {
    public function get($direction = 'gainers') {
        return $this->_get('/v2/snapshot/locale/global/markets/crypto/'.$direction);
    }

    protected function mapper($response)
    {
        $response['tickers'] = array_map(function ($ticker) {
            return Mappers::snapshotTicker($ticker);
        }, $response['tickers']);
        return $response;
    }
}