<?php
namespace PolygonIO\rest\reference;

use PolygonIO\rest\RestResource;

/**
 * Class TickerNews
 * @package PolygonIO\rest\reference
 */
class TickerNews extends RestResource {
    protected $defaultParams = [
        'perPage' => 50,
        'page' => 1,
    ];

    /**
     * @param $tickerSymbol
     * @param $params
     * @return mixed
     */
    public function get($tickerSymbol, $params = []) {
        return $this->_get('/v1/meta/symbols/'.$tickerSymbol.'/news', $params);
    }
}