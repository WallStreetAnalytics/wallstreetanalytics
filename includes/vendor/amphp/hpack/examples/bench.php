<?php

require __DIR__ . '/../vendor/autoload.php';

$root = __DIR__ . "/../vendor/http2jp/hpack-test-case";
$paths = \glob("$root/*/*.json");

foreach ($paths as $path) {
    if (\basename(\dirname($path)) === "raw-data") {
        continue;
    }

    $data = \json_decode(\file_get_contents($path));
    $cases = [];
    foreach ($data->cases as $case) {
        foreach ($case->headers as &$header) {
            $header = (array) $header;
            $header = [\key($header), \current($header)];
        }
        $cases[$case->seqno] = [\hex2bin($case->wire), $case->headers];
    }

    $tests[] = $cases;
}

$minDuration = \PHP_INT_MAX;
$minOps = \PHP_INT_MAX;

for ($i = 0; $i < 10; $i++) {
    $start = \microtime(true);
    $ops = 0;

    foreach ($tests as $test) {
        $hpack = new Amp\Http\Internal\HPackNative;
        foreach ($cases as [$input, $output]) {
            $headers = $hpack->decode($input, 4096);
            $hpack->encode($headers);

            if ($headers !== $output) {
                print 'Invalid headers' . \PHP_EOL;
                exit(1);
            }

            $ops++;
        }
    }

    $duration = \microtime(true) - $start;
    $minDuration = \min($minDuration, $duration);
    $minOps = \min($ops, $minOps);
}

print "$minOps in $minDuration seconds" . \PHP_EOL;
