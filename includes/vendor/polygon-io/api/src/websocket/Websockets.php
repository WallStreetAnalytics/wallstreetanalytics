<?php
namespace PolygonIO\websockets;

class Websockets {
    public $stocks;
    public $crypto;
    public $forex;

    public function __construct($apiKey)
    {
        $this->crypto = new WebsocketResource('crypto', $apiKey);
        $this->forex = new WebsocketResource('forex', $apiKey);
        $this->stocks = new WebsocketResource('stocks', $apiKey);
    }
}
