<?php
namespace PolygonIO\rest\forex;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class PreviousClose extends RestResource {
    public function get($tickerSymbol, $params = []){
        return $this->_get('/v2/aggs/ticker/'.$tickerSymbol.'/prev', $params);
    }

    protected function mapper($response)
    {
        $response['results'] = array_map(function ($result) {
            return Mappers::snapshotAggV2($result);
        }, $response['results']);
        return $response;
    }
}