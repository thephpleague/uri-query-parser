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

use League\Uri\Exception\MalformedUriComponent;
use League\Uri\Exception\UnknownEncoding;
use TypeError;
use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;
use function array_key_exists;
use function explode;
use function is_array;
use function is_scalar;
use function method_exists;
use function preg_match;
use function preg_replace_callback;
use function rawurldecode;
use function sprintf;
use function str_replace;
use function strpos;
use function strtoupper;
use function substr;

/**
 * A class to parse a URI query string.
 *
 * @package  League\Uri
 * @author   Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since    1.0.0
 * @see      https://tools.ietf.org/html/rfc3986#section-3.4
 * @internal Use the function League\Uri\query_parse and League\Uri\query_extract instead
 */
final class QueryParser
{
    /**
     * @internal
     */
    const ENCODING_LIST = [PHP_QUERY_RFC1738 => 1, PHP_QUERY_RFC3986 => 1];

    /**
     * @internal
     */
    const REGEXP_INVALID_CHARS = '/[\x00-\x1f\x7f]/';

    /**
     * @internal
     */
    const REGEXP_ENCODED_PATTERN = ',%[A-Fa-f0-9]{2},';

    /**
     * @internal
     */
    const REGEXP_DECODED_PATTERN = ',%2[D|E]|3[0-9]|4[1-9|A-F]|5[0-9|A|F]|6[1-9|A-F]|7[0-9|E],i';

    /**
     * Parse a query string into a collection of key/value pairs.
     *
     * @param mixed  $query     The query string to parse
     * @param string $separator The query string separator
     * @param int    $enc_type  The query encoding algorithm
     *
     * @throws TypeError             If the query string is a resource, an array or an object without the `__toString` method
     * @throws MalformedUriComponent If the query string is invalid
     * @throws UnknownEncoding       If the encoding type is invalid
     *
     * @return array
     */
    public static function parse($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): array
    {
        if (!isset(self::ENCODING_LIST[$enc_type])) {
            throw new UnknownEncoding(sprintf('Unknown Encoding: %s', $enc_type));
        }

        if (null === $query) {
            return [];
        }

        if (!is_scalar($query) && !method_exists($query, '__toString')) {
            throw new TypeError(sprintf('The query must be a scalar, a stringable object or the `null` value, `%s` given', gettype($query)));
        }

        if (is_bool($query)) {
            return [[$query ? '1' : '0', null]];
        }

        $query = (string) $query;
        if ('' === $query) {
            return [['', null]];
        }

        if (preg_match(self::REGEXP_INVALID_CHARS, $query)) {
            throw new MalformedUriComponent(sprintf('Invalid query string: %s', $query));
        }

        if (PHP_QUERY_RFC1738 === $enc_type) {
            $query = str_replace('+', ' ', $query);
        }

        return array_map([QueryParser::class, 'parsePair'], explode($separator, $query));
    }

    /**
     * Parse a query string pair.
     *
     * @param string $pair The query string pair
     *
     * @return array
     */
    private static function parsePair(string $pair): array
    {
        list($key, $value) = explode('=', $pair, 2) + [1 => null];

        if (preg_match(self::REGEXP_ENCODED_PATTERN, $key)) {
            $key = preg_replace_callback(self::REGEXP_ENCODED_PATTERN, [QueryParser::class, 'decodeMatch'], $key);
        }

        if (null === $value) {
            return [$key, $value];
        }

        if (preg_match(self::REGEXP_ENCODED_PATTERN, $value)) {
            $value = preg_replace_callback(self::REGEXP_ENCODED_PATTERN, [QueryParser::class, 'decodeMatch'], $value);
        }

        return [$key, $value];
    }

    /**
     * Decode a match string.
     *
     * @param array $matches
     *
     * @return string
     */
    private static function decodeMatch(array $matches): string
    {
        if (preg_match(self::REGEXP_DECODED_PATTERN, $matches[0])) {
            return strtoupper($matches[0]);
        }

        return rawurldecode($matches[0]);
    }

    /**
     * Returns the store PHP variables as elements of an array.
     *
     * The result is similar as PHP parse_str when used with its
     * second argument with the difference that variable names are
     * not mangled.
     *
     * @see http://php.net/parse_str
     * @see https://wiki.php.net/rfc/on_demand_name_mangling
     *
     * @param mixed  $query     The query string to parse
     * @param string $separator The query string separator
     * @param int    $enc_type  The query encoding algorithm
     *
     * @return array
     */
    public static function extract($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): array
    {
        return array_reduce(self::parse($query, $separator, $enc_type), [QueryParser::class, 'extractPhpVariable'], []);
    }

    /**
     * Parse a query pair like parse_str without mangling the results array keys.
     *
     * <ul>
     * <li>empty name are not saved</li>
     * <li>If the value from name is duplicated its corresponding value will
     * be overwritten</li>
     * <li>if no "[" is detected the value is added to the return array with the name as index</li>
     * <li>if no "]" is detected after detecting a "[" the value is added to the return array with the name as index</li>
     * <li>if there's a mismatch in bracket usage the remaining part is dropped</li>
     * <li>“.” and “ ” are not converted to “_”</li>
     * <li>If there is no “]”, then the first “[” is not converted to becomes an “_”</li>
     * <li>no whitespace trimming is done on the key value</li>
     * </ul>
     *
     * @see https://php.net/parse_str
     * @see https://wiki.php.net/rfc/on_demand_name_mangling
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic1.phpt
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic2.phpt
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic3.phpt
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic4.phpt
     *
     * @param array        $data  the submitted array
     * @param array|string $name  the pair key
     * @param string       $value the pair value
     *
     * @return array
     */
    private static function extractPhpVariable(array $data, $name, string $value = ''): array
    {
        if (is_array($name)) {
            list($name, $value) = $name;
            $value = rawurldecode((string) $value);
        }

        if ('' === $name) {
            return $data;
        }

        if (false === ($left_bracket_pos = strpos($name, '['))) {
            $data[$name] = $value;

            return $data;
        }

        if (false === ($right_bracket_pos = strpos($name, ']', $left_bracket_pos))) {
            $data[$name] = $value;

            return $data;
        }

        $key = substr($name, 0, $left_bracket_pos);
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            $data[$key] = [];
        }

        $index = substr($name, $left_bracket_pos + 1, $right_bracket_pos - $left_bracket_pos - 1);
        if ('' === $index) {
            $data[$key][] = $value;

            return $data;
        }

        $remaining = substr($name, $right_bracket_pos + 1);
        if ('[' !== substr($remaining, 0, 1) || false === strpos($remaining, ']', 1)) {
            $remaining = '';
        }

        $data[$key] = self::extractPhpVariable($data[$key], $index.$remaining, $value);

        return $data;
    }
}
