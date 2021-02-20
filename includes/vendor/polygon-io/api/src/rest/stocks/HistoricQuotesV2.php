<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\RestResource;

class HistoricQuotesV2 extends RestResource {
    protected $defaultParams = [
        'limit' => 5000
    ];
    public function get($tickerSymbol, $date, $params = null) {
        return $this->_get('/v2/ticks/stocks/nbbo/'.$tickerSymbol.'/'.$date,$params);
    }

    protected function mapper($response)
    {
        if ($response['results']) {
            $response['results'] = array_map(function ($result) {
                $result['ticker'] = $result['T'];
                $result['SIPTimestamp'] = $result['t'];
                $result['participantExchangeTimestamp'] = $result['y'];
                $result['tradeReportingFacilityTimestamp'] = $result['f'];
                $result['sequenceNumber'] = $result['q'];
                $result['conditions'] = $result['c'];
                $result['indicators'] = $result['i'];
                $result['bidPrice'] = $result['p'];
                $result['bidExchangeId'] = $result['x'];
                $result['bidSize'] = $result['s'];
                $result['askPrice'] = $result['p'];
                $result['askExchangeId'] = $result['X'];
                $result['askSize'] = $result['S'];
                $result['tapeWhereTradeOccured'] = $result['z'];
                return $result;
            }, $response['results']);
        }
        return $response;
    }
}