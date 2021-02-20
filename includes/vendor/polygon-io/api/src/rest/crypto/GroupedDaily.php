<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class GroupedDaily extends RestResource {
    public function get($date, $locale = 'US', $market = 'CRYPTO', $params = []){
        return $this->_get('/v2/aggs/grouped/locale/'.$locale.'/market/'.$market.'/'.$date, $params);
    }

    protected function mapper($response)
    {
        $response['results'] = array_map(function ($result) {
            return Mappers::snapshotAggV2($result);
        }, $response['results']);
        return $response;
    }
}