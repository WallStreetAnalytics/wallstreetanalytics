<?php
namespace PolygonIO\rest\crypto;

use PolygonIO\rest\RestResource;

class CryptoExchanges extends RestResource {
    public $route = '/v1/meta/crypto-exchanges';
    public function get() {
        return $this->_get($this->route);
    }
}