<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class SnapshotAllTickers extends RestResource {
    public function get() {
        return $this->_get('/v2/snapshot/locale/global/markets/crypto/tickers');
    }

    protected function mapper($response)
    {
        $response['tickers'] = array_map(function ($ticker) {
            return Mappers::snapshotCryptoTicker($ticker);
        }, $response['tickers']);
        return $response;
    }
}