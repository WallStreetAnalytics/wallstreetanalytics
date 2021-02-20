<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class SnapshotSingleTicker extends RestResource {
    public function get($tickerSymbol) {
        return $this->_get('/v2/snapshot/locale/global/markets/crypto/tickers/'.$tickerSymbol);
    }

    protected function mapper($response)
    {
        $response['ticker'] = Mappers::snapshotCryptoTicker($response['ticker']);
        return $response;
    }
}