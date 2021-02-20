<?php

use Amp\ByteStream\StreamException;
use Amp\Loop;
use Amp\Websocket;

require __DIR__ . '/../vendor/autoload.php';

const AGENT = 'amphp/websocket';

Loop::run(function () {
    $errors = 0;

    $options = (new Websocket\Options)
        ->withMaximumMessageSize(32 * 1024 * 1024)
        ->withMaximumFrameSize(32 * 1024 * 1024)
        ->withValidateUtf8(true);

    /** @var Websocket\Connection $connection */
    $connection = yield Websocket\connect('ws://127.0.0.1:9001/getCaseCount');
    /** @var Websocket\Message $message */
    $message = yield $connection->receive();
    $cases = (int) yield $message->buffer();

    echo "Going to run {$cases} test cases." . PHP_EOL;

    for ($i = 1; $i < $cases; $i++) {
        $connection = yield Websocket\connect('ws://127.0.0.1:9001/getCaseInfo?case=' . $i . '&agent=' . AGENT);
        $message = yield $connection->receive();
        $info = \json_decode(yield $message->buffer(), true);

        print $info['id'] . ' ' . str_repeat('-', 80 - strlen($info['id']) - 1) . PHP_EOL;
        print wordwrap($info['description'], 80, PHP_EOL) . ' ';

        $connection = yield Websocket\connect('ws://127.0.0.1:9001/runCase?case=' . $i . '&agent=' . AGENT, null, null, $options);

        try {
            while ($message = yield $connection->receive()) {
                $content = yield $message->buffer();

                if ($message->isBinary()) {
                    yield $connection->sendBinary($content);
                } else {
                    yield $connection->send($content);
                }
            }
        } catch (Websocket\ClosedException $e) {
            // ignore
        } catch (AssertionError $e) {
            print 'Assertion error: ' . $e->getMessage() . PHP_EOL;
            $connection->close();
        } catch (Error $e) {
            print 'Error: ' . $e->getMessage() . PHP_EOL;
            $connection->close();
        } catch (StreamException $e) {
            print 'Stream exception: ' . $e->getMessage() . PHP_EOL;
            $connection->close();
        }

        $connection = yield Websocket\connect('ws://127.0.0.1:9001/getCaseStatus?case=' . $i . '&agent=' . AGENT);
        $message = yield $connection->receive();
        print ($result = \json_decode(yield $message->buffer(), true)['behavior']);

        if ($result === 'FAILED') {
            $errors++;
        }

        print PHP_EOL . PHP_EOL;
    }

    $connection = yield Websocket\connect('ws://127.0.0.1:9001/updateReports?agent=' . AGENT);
    $connection->close();

    Loop::stop();

    if ($errors) {
        exit(1);
    }
});
