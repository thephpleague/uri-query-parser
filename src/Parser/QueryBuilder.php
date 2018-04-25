<?php
/**
 * League Uri Query Parser (http://uri.thephpleague.com).
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

namespace League\Uri\Parser;

use League\Uri\EncodingInterface;
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
final class QueryBuilder implements EncodingInterface
{
    /**
     * @internal
     */
    const ENCODING_LIST = [
        self::RFC1738_ENCODING => 1,
        self::RFC3986_ENCODING => 1,
        self::RFC3987_ENCODING => 1,
        self::NO_ENCODING => 1,
    ];

    /**
     * @internal
     */
    const CHARS_LIST = [
        'pattern' => [
            "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\x09",
            "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F", "\x10", "\x11", "\x12", "\x13",
            "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D",
            "\x1E", "\x1F", "\x7F", '#',
        ],
        'replace' => [
            '%00', '%01', '%02', '%03', '%04', '%05', '%06', '%07', '%08', '%09',
            '%0A', '%0B', '%0C', '%0D', '%0E', '%0F', '%10', '%11', '%12', '%13',
            '%14', '%15', '%16', '%17', '%18', '%19', '%1A', '%1B', '%1C', '%1D',
            '%1E', '%1F', '%7F', '%23',
        ],
    ];

    /**
     * @internal
     */
    const REGEXP_UNRESERVED_CHAR = '/[^A-Za-z0-9_\-\.~]/';

    /**
     * @var callable
     */
    private $encoder;

    /**
     * @var string
     */
    private $regexp;

    /**
     * @var string[]
     */
    private $pattern;

    /**
     * @var string[]
     */
    private $replace;
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
     * @throws InvalidArgument If a query pair is malformed
     *
     * @return null|string
     */
    public function build($pairs, string $separator = '&', int $enc_type = self::RFC3986_ENCODING)
    {
        if (!\is_array($pairs) && !$pairs instanceof Traversable) {
            throw new TypeError('the pairs collection must be an array or a Traversable object');
        }

        if (!isset(self::ENCODING_LIST[$enc_type])) {
            throw new UnknownEncoding(\sprintf('Unsupported or Unknown Encoding: %s', $enc_type));
        }

        $method = 'buildRawPair';
        if (self::RFC3986_ENCODING == $enc_type) {
            $subdelim = \str_replace(\html_entity_decode($separator, ENT_HTML5, 'UTF-8'), '', "!$'()*+,;=:@?/&%");
            $this->regexp = '/(%[A-Fa-f0-9]{2})|[^A-Za-z0-9_\-\.~'.\preg_quote($subdelim, '/').']+/u';
            $method = 'buildRFC3986Pair';
        } elseif (self::RFC1738_ENCODING == $enc_type) {
            $subdelim = \str_replace(\html_entity_decode($separator, ENT_HTML5, 'UTF-8'), '', "!$'()*+,;=:@?/&%");
            $this->regexp = '/(%[A-Fa-f0-9]{2})|[^A-Za-z0-9_\-\.~'.\preg_quote($subdelim, '/').']+/u';
            $method = 'buildRFC1738Pair';
        } elseif (self::RFC3987_ENCODING == $enc_type) {
            $method = 'buildRFC3987Pair';
            $this->pattern = self::CHARS_LIST['pattern'];
            $this->pattern[] = $separator;
            $this->replace = self::CHARS_LIST['replace'];
            $this->replace[] = \rawurlencode($separator);
        }

        $res = [];
        foreach ($pairs as $pair) {
            $res[] = $this->$method($this->filterPair($pair));
        }

        return empty($res) ? null : \implode($separator, $res);
    }

    /**
     * Encode Matches sequence.
     *
     * @param array $matches
     *
     * @return string
     */
    private function encodeMatches(array $matches): string
    {
        if (\preg_match(self::REGEXP_UNRESERVED_CHAR, \rawurldecode($matches[0]))) {
            return \rawurlencode($matches[0]);
        }

        return $matches[0];
    }

    /**
     * Filter the submitted pair.
     *
     * @param array $pair
     *
     * @throws InvalidArgument if the pair is invalid
     *
     * @return array
     */
    private function filterPair(array $pair): array
    {
        if (\array_keys($pair) !== [0, 1]) {
            throw new InvalidArgument('A pair must be a sequential array starting at `0` and containing two elements.');
        }

        if (!\is_scalar($pair[0])) {
            throw new InvalidArgument(\sprintf('A pair key must be a scalar value `%s` given.', \gettype($pair[0])));
        }

        if (\is_bool($pair[0])) {
            $pair[0] = (int) $pair[0];
        }

        $pair[0] = (string) $pair[0];
        if (null === $pair[1]) {
            return $pair;
        }

        if (\is_bool($pair[1])) {
            $pair[1] = (int) $pair[1];
        }

        if (\is_scalar($pair[1])) {
            $pair[1] = (string) $pair[1];

            return $pair;
        }

        throw new InvalidArgument(\sprintf('A pair value must be a scalar value or the null value, `%s` given.', \gettype($pair[1])));
    }

    /**
     * Build a RFC3986 query key/value pair association.
     *
     * @param array $pair
     *
     * @throws InvalidArgument If the pair is invalid
     *
     * @return string
     */
    private function buildRFC3986Pair(array $pair): string
    {
        if (\preg_match($this->regexp, $pair[0])) {
            $pair[0] = \preg_replace_callback($this->regexp, [$this, 'encodeMatches'], $pair[0]);
        }

        if (null === $pair[1]) {
            return $pair[0];
        }

        if (!\preg_match($this->regexp, $pair[1])) {
            return $pair[0].'='.$pair[1];
        }

        return $pair[0].'='.\preg_replace_callback($this->regexp, [$this, 'encodeMatches'], $pair[1]);
    }

    /**
     * Build a RFC1738 query key/value pair association.
     *
     * @param array $pair
     *
     * @throws InvalidArgument If the pair is invalid
     *
     * @return string
     */
    private function buildRFC1738Pair(array $pair): string
    {
        $str = $this->buildRFC3986Pair($this->filterPair($pair));
        if (\strpos($str, '+') !== false || \strpos($str, '~') !== false) {
            return \str_replace(['+', '~'], ['%2B', '%7E'], $str);
        }

        return $str;
    }

    /**
     * Build a RFC3987 query key/value pair association.
     *
     * @param array $pair
     *
     * @throws InvalidArgument If the pair is invalid
     *
     * @return string
     */
    private function buildRFC3987Pair(array $pair): string
    {
        $pair[0] = \str_replace($this->pattern, $this->replace, $pair[0]);
        if (null === $pair[1]) {
            return $pair[0];
        }

        return $pair[0].'='.\str_replace($this->pattern, $this->replace, $pair[1]);
    }

    /**
     * Build a raw query key/value pair association.
     *
     * @param array $pair
     *
     * @throws InvalidArgument If the pair is invalid
     *
     * @return string
     */
    private function buildRawPair(array $pair): string
    {
        if (null === $pair[1]) {
            return $pair[0];
        }

        return $pair[0].'='.$pair[1];
    }
}
