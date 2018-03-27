<?php

require __DIR__.'/../src/EncodingInterface.php';
require __DIR__.'/../src/Exception/MalFormedPair.php';
require __DIR__.'/../src/Exception/UnsupportedEncoding.php';
require __DIR__.'/../src/QueryBuilder.php';
require __DIR__.'/../src/functions.php';

$pairs = ['module' => ['home'], 'action' => ['show'], 'page' => [3]];
for ($i = 0; $i < 100000; ++$i) {
    League\Uri\build_query($pairs);
}
