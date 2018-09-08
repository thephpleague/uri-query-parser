<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license https://github.com/thephpleague/uri-query-parser/blob/master/LICENSE (MIT License)
 * @version 1.0.0
 * @link    https://github.com/thephpleague/uri-query-parser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require __DIR__.'/../src/Parser/QueryParser.php';
require __DIR__.'/../src/functions.php';

$query = 'module=home&action=show&page=3';
for ($i = 0; $i < 100000; ++$i) {
    League\Uri\query_parse($query);
}
