<?php
namespace PolygonIO\rest\reference;

use PolygonIO\rest\RestResource;

/**
 * Class MarketStatus
 * @package PolygonIO\rest\reference
 */
class MarketStatus extends RestResource {
    protected $route = '/v1/marketstatus/now';

    public function get() {
        return $this->_get($this->route);
    }
}