<?php
namespace PolygonIO\rest;

class Mappers {
    public static function quoteV1 ($tick) {
        $tick['condition'] = $tick['c'];
        $tick['bidExchange'] = $tick['bE'];
        $tick['askExchange'] = $tick['aE'];
        $tick['askPrice'] = $tick['aP'];
        $tick['buyPrice'] = $tick['bP'];
        $tick['bidSize'] = $tick['bS'];
        $tick['askSize'] = $tick['aS'];
        $tick['timestamp'] = $tick['t'];
        return $tick;
    }

    public static function snapshotQuote ($q) {
        $q['bidPrice'] = $q['p'];
        $q['bidSize'] = $q['s'];
        $q['askPrice'] = $q['P'];
        $q['askSize'] = $q['S'];
        $q['lastUpdateTimestam'] = $q['t'];
        return $q;
    }

    public static function tradeV1 ($tick) {
        $tick['condition1'] = $tick['c1'];
        $tick['condition2'] = $tick['c2'];
        $tick['condition3'] = $tick['c3'];
        $tick['condition4'] = $tick['c4'];
        $tick['exchange'] = $tick['e'];
        $tick['price'] = $tick['p'];
        $tick['size'] = $tick['s'];
        $tick['timestamp'] = $tick['t'];
        return $tick;
    }

    public static function snapshotAgg ($snap) {
        $snap['close'] =  $snap['c'];
        $snap['high'] =  $snap['h'];
        $snap['low'] = $snap['l'];
        $snap['open'] = $snap['o'];
        $snap['volume'] = $snap['v'];
        return $snap;
    }

    public static function snapshotAggV2 ($snap) {
        $snap['tickerSymbol'] = $snap['T'];
        $snap['volume'] = $snap['v'];
        $snap['open'] = $snap['o'];
        $snap['close'] = $snap['c'];
        $snap['high'] = $snap['h'];
        $snap['low'] = $snap['l'];
        $snap['timestamp'] = $snap['t'];
        $snap['numberOfItems'] = $snap['n'];
        return $snap;
    }

    public static function snapshotTicker ($snap) {
        $snap['day'] = Mappers::snapshotAgg($snap['day']);
        $snap['lastTrade'] = Mappers::tradeV1($snap['lastTrade']);
        $snap['lastQuote'] = Mappers::snapshotQuote($snap['lastQuote']);
        $snap['min'] = Mappers::snapshotAgg($snap['min']);
        $snap['prevDay'] = Mappers::snapshotAgg($snap['prevDay']);
        return $snap;
    }

    public static function snapshotCryptoTicker ($snap) {
        $snap['day'] = Mappers::snapshotAgg($snap['day']);
        $snap['lastTrade'] = Mappers::cryptoTick($snap['lastTrade']);
        $snap['min'] = Mappers::snapshotAgg($snap['min']);
        $snap['prevDay'] = Mappers::snapshotAgg($snap['prevDay']);
        return $snap;
    }

    public static function cryptoTick($tick) {
        $tick['price'] = $tick['p'];
        $tick['size'] = $tick['s'];
        $tick['exchange'] = $tick['x'];
        $tick['conditions'] = $tick['c'];
        $tick['timestamp'] = $tick['t'];
        return $tick;
    }

    public static function cryptoSnapshotBookItem ($item) {
        $item['price'] = $item['p'];
        return $item;
    }
}