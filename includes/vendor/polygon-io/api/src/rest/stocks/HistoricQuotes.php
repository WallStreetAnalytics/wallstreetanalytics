<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\RestResource;

class HistoricQuotes extends RestResource {
    protected $defaultParams = [
        'limit' => 100
    ];
    public function get($tickerSymbol, $date) {
        return $this->_get('/v1/historic/quotes/'.$tickerSymbol.'/'.$date);
    }

    protected function mapper($response)
    {
        $response['ticks'] = array_map(function ($tick) {
            $tick['condition'] = $tick['c'];
            $tick['bidExchange'] = $tick['bE'];
            $tick['askExchange'] = $tick['aE'];
            $tick['askPrice'] = $tick['aP'];
            $tick['buyPrice'] = $tick['bP'];
            $tick['bidSize'] = $tick['bS'];
            $tick['askSize'] = $tick['aS'];
            $tick['timestamp'] = $tick['t'];
            return $tick;
        }, $response['ticks']);
        return $response;
    }
}