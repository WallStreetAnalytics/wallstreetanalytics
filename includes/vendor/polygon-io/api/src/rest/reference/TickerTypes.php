<?php
namespace PolygonIO\rest\reference;

use PolygonIO\rest\RestResource;

/**
 * Class TickerTypes
 * @package PolygonIO\rest\reference
 */
class TickerTypes extends RestResource {
    protected $route = '/v2/reference/types';

    public function get() {
        return $this->_get($this->route);
    }
}