<?php

set_time_limit (0);

require_once './Services/LogParserService.php';

$logParser = new LogParserService($argv[1]);

$output = $logParser->readFile();

echo PHP_EOL . json_encode($output, JSON_PRETTY_PRINT) . PHP_EOL;