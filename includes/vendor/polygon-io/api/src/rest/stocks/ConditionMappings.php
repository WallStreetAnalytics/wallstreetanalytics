<?php
namespace PolygonIO\rest\stocks;

use PolygonIO\rest\RestResource;

class ConditionMappings extends RestResource {
    public function get($tickTypes = 'trades') {
        return $this->_get('/v1/meta/conditions/'.$tickTypes);
    }
}