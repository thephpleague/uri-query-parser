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

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Parser\QueryBuilder;
use League\Uri\Parser\QueryParser;
use const PHP_QUERY_RFC3986;

/**
 * Build a query string from a collection of key/value pairs.
 *
 * @see QueryBuilder::build
 *
 * @param mixed  $pairs     The query pairs
 * @param string $separator The query string separator
 * @param int    $enc_type  The query encoding algorithm
 *
 * @return null|string
 */
function query_build($pairs, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986)
{
    return QueryBuilder::build($pairs, $separator, $enc_type);
}

/**
 * Parse a query string into a collection of key/value pairs.
 *
 * @see QueryParser::parse
 *
 * @param mixed  $query     The query string to parse
 * @param string $separator The query string separator
 * @param int    $enc_type  The query encoding algorithm
 *
 */
function query_parse($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): array
{
    return QueryParser::parse($query, $separator, $enc_type);
}

/**
 * Parse the query string like parse_str without mangling the results.
 *
 * @see QueryParser::extract
 *
 * @param mixed  $query     The query string to parse
 * @param string $separator The query string separator
 * @param int    $enc_type  The query encoding algorithm
 *
 */
function query_extract($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): array
{
    return QueryParser::extract($query, $separator, $enc_type);
}
