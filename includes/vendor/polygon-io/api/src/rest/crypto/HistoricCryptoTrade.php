<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\Mappers;
use PolygonIO\rest\RestResource;

class HistoricCryptoTrade extends RestResource {
    protected $defaultParams = [
        'limit' => 100,
    ];

    public function get($from, $to, $date, $params = []){
        return $this->_get('/v1/historic/crypto/'.$from.'/'.$to.'/'.$date, $params);
    }

    protected function mapper($response)
    {
        $response['ticks'] = array_map(function($tick) {
            return Mappers::cryptoTick($tick);
        }, $response['ticks']);
        return $response;
    }
}