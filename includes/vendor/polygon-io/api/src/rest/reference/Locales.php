<?php
namespace PolygonIO\rest\reference;

use PolygonIO\rest\RestResource;

/**
 * Class Locales
 * @package PolygonIO\rest\reference
 */
class Locales extends RestResource {
    protected $route = '/v2/reference/locales';

    public function get() {
        return $this->_get($this->route);
    }
}