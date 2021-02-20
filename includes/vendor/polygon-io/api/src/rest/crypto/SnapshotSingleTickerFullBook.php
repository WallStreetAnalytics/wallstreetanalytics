<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class SnapshotSingleTickerFullBook extends RestResource {
    public function get($tickerSymbol) {
        return $this->_get('/v2/snapshot/locale/global/markets/crypto/tickers/'.$tickerSymbol.'/book');
    }

    protected function mapper($response)
    {
        if(array_key_exists('asks', $response['data'])) {
            $response['data']['asks'] = array_merge(function($ask) {
                return Mappers::cryptoSnapshotBookItem($ask);
            }, $response['data']['asks']);
        }
        if (array_key_exists('bids', $response['data'])) {
            $response['data']['bids'] = array_merge(function($bid) {
                return Mappers::cryptoSnapshotBookItem($bid);
            }, $response['data']['bids']);
        }
        return $response;
    }
}