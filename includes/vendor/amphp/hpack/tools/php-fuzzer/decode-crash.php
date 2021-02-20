<?php

require __DIR__ . '/../../vendor/autoload.php';

use Amp\Http\HPack;

(new HPack)->decode(\file_get_contents($argv[1]), 8192);
