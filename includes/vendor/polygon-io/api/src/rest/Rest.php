<?php
namespace PolygonIO\rest;
require_once __DIR__.'/../../vendor/autoload.php';

//this isn't in autoload for some reason?
require_once __DIR__.'/common/Mappers.php';

use PolygonIO\rest\crypto\Crypto;
use PolygonIO\rest\forex\Forex;
use PolygonIO\rest\reference\Reference;
use PolygonIO\rest\stocks\Stocks;


class Rest {
    public $reference;
    public $stocks;
    public $forex;
    public $crypto;
    /**
     * Rest constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->reference = new Reference($apiKey);
        $this->stocks = new Stocks($apiKey);
        $this->forex = new Forex($apiKey);
        $this->crypto = new Crypto($apiKey);
    }
}
