<?php
namespace PolygonIO\websockets;

use \Amp\Websocket;

class WebsocketResource {
    public $SOCKET_URI;
    private $apiKey;

    public function __construct($topic, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->SOCKET_URI = 'wss://socket.polygon.io:443/'.$topic;
    }

    public function connect($subscriptions, $onMessageCallback) {
        \Amp\Loop::run(function () use ($onMessageCallback, $subscriptions) {
            /** @var Websocket\Connection $connection */
            $connection = yield Websocket\connect($this->SOCKET_URI);
            yield $connection->send('{"action":"auth", "params":"'.$this->apiKey.'"}');
            yield $connection->send(json_encode([
                "action" => "subscribe",
	            "params" => $subscriptions,
            ]));
            /** @var Websocket\Message $message */
            while ($message = yield $connection->receive()) {
                $payload = yield $message->buffer();
                $onMessageCallback(json_decode($payload));
            }
        });
    }
}