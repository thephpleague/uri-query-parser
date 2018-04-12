<?php

require __DIR__.'/../src/EncodingInterface.php';
require __DIR__.'/../src/Components/Parser/QueryParser.php';
require __DIR__.'/../src/functions.php';

$query = 'module=home&action=show&page=3';
for ($i = 0; $i < 100000; ++$i) {
    League\Uri\query_parse($query);
}
