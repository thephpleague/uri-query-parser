<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version 1.0.0
 * @link    https://github.com/thephpleague/uri-query-parser
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Parser\QueryBuilder;
use League\Uri\Parser\QueryParser;

/**
 * Build a query string from a collection of key/value pairs.
 *
 * @see QueryBuilder::build
 *
 * @param mixed  $pairs     The query pairs
 * @param int    $enc_type  The query encoding type
 * @param string $separator The query string separator
 *
 * @return null|string
 */
function query_build($pairs, int $enc_type = PHP_QUERY_RFC3986, string $separator = '&')
{
    return QueryBuilder::build($pairs, $enc_type, $separator);
}

/**
 * Parse a query string into a collection of key/value pairs.
 *
 * @see QueryParser::parse
 *
 * @param mixed  $query     The query string to parse
 * @param int    $enc_type  The query encoding algorithm
 * @param string $separator The query string separator
 *
 * @return array
 */
function query_parse($query, int $enc_type = PHP_QUERY_RFC3986, string $separator = '&'): array
{
    return QueryParser::parse($query, $enc_type, $separator);
}

/**
 * Parse the query string like parse_str without mangling the results.
 *
 * @see QueryParser::extract
 *
 * @param mixed  $query     The query string to parse
 * @param int    $enc_type  The query encoding algorithm
 * @param string $separator The query string separator
 *
 * @return array
 */
function query_extract($query, int $enc_type = PHP_QUERY_RFC3986, string $separator = '&'): array
{
    return QueryParser::extract($query, $enc_type, $separator);
}
