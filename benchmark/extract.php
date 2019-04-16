<?php

/**
 * League Uri Query String Parser (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../src/Parser/QueryString.php';

$query = 'module=home&action=show&page=3';
for ($i = 0; $i < 100000; ++$i) {
    League\Uri\Parser\QueryString::extract($query);
}
