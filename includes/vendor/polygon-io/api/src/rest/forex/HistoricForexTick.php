<?php
namespace PolygonIO\rest\forex;

use PolygonIO\rest\RestResource;

class HistoricForexTick extends RestResource {
    protected $defaultParams = [
        'limit' => 100,
    ];

    public function get($from, $to, $date, $params = []) {
        return $this->_get('/v1/historic/forex/'.$from.'/'.$to.'/'.$date, $params);
    }
}