<?php
namespace PolygonIO;
require_once __DIR__.'/../vendor/autoload.php';

use PolygonIO\rest\Rest;
use PolygonIO\websockets\Websockets;

public class PolygonIO {
    public $api_key;
    public $rest;
    public $websockets;

    /**
     * Polygon constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->api_key = $apiKey;
        $this->rest = new Rest($apiKey);
        $this->websockets = new Websockets($apiKey);
    }
}

?>