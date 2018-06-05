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

namespace League\Uri\Parser;

use League\Uri\Exception\InvalidQueryPair;
use League\Uri\Exception\UnknownEncoding;
use Traversable;
use TypeError;

/**
 * A class to build a URI query string from a collection of key/value pairs.
 *
 * @package  League\Uri
 * @author   Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since    1.0.0
 * @see      https://tools.ietf.org/html/rfc3986#section-3.4
 * @internal Use the function League\Uri\query_build instead
 */
final class QueryBuilder
{
    /**
     * @internal
     */
    const ENCODING_LIST = [PHP_QUERY_RFC1738 => 1, PHP_QUERY_RFC3986 => 1];

    /**
     * @internal
     */
    const REGEXP_UNRESERVED_CHAR = '/[^A-Za-z0-9_\-\.~]/';

    /**
     * @var string
     */
    private static $regexpKey;

    /**
     * @var string
     */
    private static $regexpValue;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Build a query string from an associative array.
     *
     * The method expects the return value from Query::parse to build
     * a valid query string. This method differs from PHP http_build_query as:
     *
     *    - it does not modify parameters keys
     *
     * @param mixed  $pairs     Collection of key/value pairs as array
     * @param string $separator query string separator
     * @param int    $enc_type  query encoding type
     *
     * @throws TypeError        If the pairs are not iterable
     * @throws InvalidQueryPair If a pair is invalid
     * @throws UnknownEncoding  If the encoding type is invalid
     *
     * @return null|string
     */
    public static function build($pairs, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986)
    {
        if (!\is_array($pairs) && !$pairs instanceof Traversable) {
            throw new TypeError('the pairs collection must be an array or a Traversable object');
        }

        if (null === (self::ENCODING_LIST[$enc_type] ?? null)) {
            throw new UnknownEncoding(\sprintf('Unknown Encoding: %s', $enc_type));
        }

        $regexpValueSuffix = "!$'()*+,;=:@?/&%";
        $regexpKeySuffix = "!$'()*+,;:@?/%";
        if (PHP_QUERY_RFC1738 === $enc_type) {
            $regexpValueSuffix = '*=&';
            $regexpKeySuffix = '*';
        }

        self::$regexpValue = '/
            (%[A-Fa-f0-9]{2})|
            [^A-Za-z0-9_\-\.~'.\preg_quote(
                \str_replace(
                    \html_entity_decode($separator, ENT_HTML5, 'UTF-8'),
                    '',
                    $regexpValueSuffix
                ),
                '/'
            ).']+/ux';

        self::$regexpKey = '/
            (%[A-Fa-f0-9]{2})|
            [^A-Za-z0-9_\-\.~'.\preg_quote(
                \str_replace(
                    \html_entity_decode($separator, ENT_HTML5, 'UTF-8'),
                    '',
                    $regexpKeySuffix
                ),
                '/'
            ).']+/ux';

        $res = [];
        foreach ($pairs as $pair) {
            $res[] = self::buildPair($pair);
        }

        if (PHP_QUERY_RFC1738 === $enc_type && !empty($res)) {
            $mapper = function (string $pair): string {
                return \str_replace(['+', '%20'], ['%2B', '+'], $pair);
            };

            $res = \array_map($mapper, $res);
        }

        return empty($res) ? null : \implode($separator, $res);
    }

    /**
     * Build a RFC3986 query key/value pair association.
     *
     * @param array $pair
     *
     * @throws InvalidQueryPair If the pair is invalid
     *
     * @return string
     */
    private static function buildPair(array $pair): string
    {
        if ([0, 1] !== \array_keys($pair)) {
            throw new InvalidQueryPair('A pair must be a sequential array starting at `0` and containing two elements.');
        }

        if (!\is_scalar($pair[0])) {
            throw new InvalidQueryPair(\sprintf('A pair key must be a scalar value `%s` given.', \gettype($pair[0])));
        }

        if (\is_bool($pair[0])) {
            $pair[0] = (int) $pair[0];
        }

        if (\is_string($pair[0]) && \preg_match(self::$regexpKey, $pair[0])) {
            $pair[0] = \preg_replace_callback(self::$regexpKey, [QueryBuilder::class, 'encodeMatches'], $pair[0]);
        }

        if (\is_string($pair[1])) {
            if (!\preg_match(self::$regexpValue, $pair[1])) {
                return $pair[0].'='.$pair[1];
            }

            return $pair[0].'='.\preg_replace_callback(self::$regexpValue, [QueryBuilder::class, 'encodeMatches'], $pair[1]);
        }

        if (\is_numeric($pair[1])) {
            return $pair[0].'='.$pair[1];
        }

        if (\is_bool($pair[1])) {
            return $pair[0].'='.(int) $pair[1];
        }

        if (null === $pair[1]) {
            return $pair[0];
        }

        throw new InvalidQueryPair(\sprintf('A pair value must be a scalar value or the null value, `%s` given.', \gettype($pair[1])));
    }

    /**
     * Encode Matches sequence.
     *
     * @param array $matches
     *
     * @return string
     */
    private static function encodeMatches(array $matches): string
    {
        if (\preg_match(self::REGEXP_UNRESERVED_CHAR, \rawurldecode($matches[0]))) {
            return \rawurlencode($matches[0]);
        }

        return $matches[0];
    }
}
