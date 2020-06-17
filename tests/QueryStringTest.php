<?php

/**
 * League Uri Query String Parser (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeagueTest\Uri;

use ArrayIterator;
use League\Uri\Exception\InvalidQueryPair;
use League\Uri\Exception\MalformedUriComponent;
use League\Uri\Exception\UnknownEncoding;
use League\Uri\Parser\QueryString;
use PHPUnit\Framework\TestCase;
use TypeError;
use function date_create;
use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;

class QueryStringTest extends TestCase
{
    public function testEncodingThrowsExceptionWithQueryParser(): void
    {
        self::expectException(UnknownEncoding::class);
        QueryString::parse('foo=bar', '&', 42);
    }

    public function testMalformedUriComponentThrowsExceptionWithQueryParser(): void
    {
        self::expectException(MalformedUriComponent::class);
        QueryString::parse("foo=bar\0");
    }

    public function testEncodingThrowsExceptionWithQueryBuilder(): void
    {
        self::expectException(UnknownEncoding::class);
        QueryString::build([['foo', 'bar']], '&', 42);
    }

    public function testBuildThrowsExceptionWithQueryBuilder(): void
    {
        self::expectException(InvalidQueryPair::class);
        QueryString::build([['foo', 'boo' => 'bar']]);
    }

    public function testWrongTypeThrowExceptionParseQuery(): void
    {
        self::expectException(TypeError::class);
        QueryString::parse(['foo=bar'], '&', PHP_QUERY_RFC1738);
    }

    /**
     * @dataProvider extractQueryProvider
     *
     * @param bool|string $query
     */
    public function testExtractQuery($query, array $expectedData): void
    {
        self::assertSame($expectedData, QueryString::extract($query));
    }

    public function extractQueryProvider(): array
    {
        return [
            [
                'query' => null,
                'expected' => [],
            ],
            [
                'query' => false,
                'expected' => ['0' => ''],
            ],
            [
                'query' => '%25car=%25car',
                'expected' => ['%car' => '%car'],
            ],
            [
                'query' => '&&',
                'expected' => [],
            ],
            [
                'query' => true,
                'expected' => ['1' => ''],
            ],
            [
                'query' => false,
                'expected' => ['0' => ''],
            ],
            [
                'query' => 'arr[1=sid&arr[4][2=fred',
                'expected' => [
                    'arr[1' => 'sid',
                    'arr' => ['4' => 'fred'],
                ],
            ],
            [
                'query' => 'arr1]=sid&arr[4]2]=fred',
                'expected' => [
                    'arr1]' => 'sid',
                    'arr' => ['4' => 'fred'],
                ],
            ],
            [
                'query' => 'arr[one=sid&arr[4][two=fred',
                'expected' => [
                    'arr[one' => 'sid',
                    'arr' => ['4' => 'fred'],
                ],
            ],
            [
                'query' => 'first=%41&second=%a&third=%b',
                'expected' => [
                    'first' => 'A',
                    'second' => '%a',
                    'third' => '%b',
                ],
            ],
            [
                'query' => 'arr.test[1]=sid&arr test[4][two]=fred',
                'expected' => [
                    'arr.test' => ['1' => 'sid'],
                    'arr test' => ['4' => ['two' => 'fred']],
                ],
            ],
            [
                'query' => 'foo&bar=&baz=bar&fo.o',
                'expected' => [
                    'foo' => '',
                    'bar' => '',
                    'baz' => 'bar',
                    'fo.o' => '',
                ],
            ],
            [
                'query' => 'foo[]=bar&foo[]=baz',
                'expected' => [
                    'foo' => ['bar', 'baz'],
                ],
            ],
            [
                'query' => 'a%00b=c',
                'expected' => [
                    'ab' => 'c',
                ],
            ],
        ];
    }

    /**
     * @dataProvider parserProvider
     *
     * @param mixed $query scalar or stringable object
     */
    public function testParse($query, string $separator, array $expected, int $encoding): void
    {
        self::assertSame($expected, QueryString::parse($query, $separator, $encoding));
    }

    public function parserProvider(): array
    {
        return [
            'empty separator' => [
                'foo',
                '',
                [['f', null], ['o', null], ['o', null]],
                PHP_QUERY_RFC3986,
            ],
            'stringable object' => [
                new class() {
                    public function __toString()
                    {
                        return 'a=1&a=2';
                    }
                },
                '&',
                [['a', '1'], ['a', '2']],
                PHP_QUERY_RFC3986,
            ],
            'rfc1738 without hexaencoding' => [
                'to+to=foo%2bbar',
                '&',
                [['to to', 'foo+bar']],
                PHP_QUERY_RFC1738,
            ],
            'null value' => [
                null,
                '&',
                [],
                PHP_QUERY_RFC3986,
            ],
            'empty string' => [
                '',
                '&',
                [['', null]],
                PHP_QUERY_RFC3986,
            ],
            'bool value' => [
                false,
                '&',
                [['0', null]],
                PHP_QUERY_RFC1738,
            ],
            'identical keys' => [
                'a=1&a=2',
                '&',
                [['a', '1'], ['a', '2']],
                PHP_QUERY_RFC3986,
            ],
            'no value' => [
                'a&b',
                '&',
                [['a', null], ['b', null]],
                PHP_QUERY_RFC3986,
            ],
            'empty value' => [
                'a=&b=',
                '&',
                [['a', ''], ['b', '']],
                PHP_QUERY_RFC3986,
            ],
            'php array' => [
                'a[]=1&a[]=2',
                '&',
                [['a[]', '1'], ['a[]', '2']],
                PHP_QUERY_RFC3986,
            ],
            'preserve dot' => [
                'a.b=3',
                '&',
                [['a.b', '3']],
                PHP_QUERY_RFC3986,
            ],
            'decode' => [
                'a%20b=c%20d',
                '&',
                [['a b', 'c d']],
                PHP_QUERY_RFC3986,
            ],
            'no key stripping' => [
                'a=&b',
                '&',
                [['a', ''], ['b', null]],
                PHP_QUERY_RFC3986,
            ],
            'no value stripping' => [
                'a=b=',
                '&',
                [['a', 'b=']],
                PHP_QUERY_RFC3986,
            ],
            'key only' => [
                'a',
                '&',
                [['a', null]],
                PHP_QUERY_RFC3986,
            ],
            'preserve falsey 1' => [
                '0',
                '&',
                [['0', null]],
                PHP_QUERY_RFC3986,
            ],
            'preserve falsey 2' => [
                '0=',
                '&',
                [['0', '']],
                PHP_QUERY_RFC3986,
            ],
            'preserve falsey 3' => [
                'a=0',
                '&',
                [['a', '0']],
                PHP_QUERY_RFC3986,
            ],
            'different separator' => [
                'a=0;b=0&c=4',
                ';',
                [['a', '0'], ['b', '0&c=4']],
                PHP_QUERY_RFC3986,
            ],
            'numeric key only' => [
                '42',
                '&',
                [['42', null]],
                PHP_QUERY_RFC3986,
            ],
            'numeric key' => [
                '42=l33t',
                '&',
                [['42', 'l33t']],
                PHP_QUERY_RFC3986,
            ],
            'rfc1738' => [
                '42=l3+3t',
                '&',
                [['42', 'l3 3t']],
                PHP_QUERY_RFC1738,
            ],
        ];
    }

    /**
     * @dataProvider buildProvider
     * @param ?string $expected_rfc1738
     * @param ?string $expected_rfc3986
     */
    public function testBuild(
        iterable $pairs,
        ?string $expected_rfc1738,
        ?string $expected_rfc3986
    ): void {
        self::assertSame($expected_rfc1738, QueryString::build($pairs, '&', PHP_QUERY_RFC1738));
        self::assertSame($expected_rfc3986, QueryString::build($pairs, '&', PHP_QUERY_RFC3986));
    }

    public function buildProvider(): array
    {
        return [
            'empty string' => [
                'pairs' => [],
                'expected_rfc1738' => null,
                'expected_rfc3986' => null,
            ],
            'identical keys' => [
                'pairs' => new ArrayIterator([['a', true] , [true, 'a']]),
                'expected_rfc1738' => 'a=1&1=a',
                'expected_rfc3986' => 'a=1&1=a',
            ],
            'no value' => [
                'pairs' => [['a', null], ['b', null]],
                'expected_rfc1738' => 'a&b',
                'expected_rfc3986' => 'a&b',
            ],
            'empty value' => [
                'pairs' => [['a', ''], ['b', 1.3]],
                'expected_rfc1738' => 'a=&b=1.3',
                'expected_rfc3986' => 'a=&b=1.3',
            ],
            'php array (1)' => [
                'pairs' => [['a[]', '1%a6'], ['a[]', '2']],
                'expected_rfc1738' => 'a%5B%5D=1%25a6&a%5B%5D=2',
                'expected_rfc3986' => 'a%5B%5D=1%25a6&a%5B%5D=2',
            ],
            'php array (2)' => [
                'pairs' => [['module', 'home'], ['action', 'show'], ['page', '😓']],
                'expected_rfc1738' => 'module=home&action=show&page=%F0%9F%98%93',
                'expected_rfc3986' => 'module=home&action=show&page=%F0%9F%98%93',
            ],
            'php array (3)' => [
                'pairs' => [['module', 'home'], ['action', 'v%61lue']],
                'expected_rfc1738' => 'module=home&action=v%61lue',
                'expected_rfc3986' => 'module=home&action=v%61lue',
            ],
            'preserve dot' => [
                'pairs' => [['a.b', '3']],
                'expected_rfc1738' => 'a.b=3',
                'expected_rfc3986' => 'a.b=3',
            ],
            'no key stripping' => [
                'pairs' => [['a', ''], ['b', null]],
                'expected_rfc1738' => 'a=&b',
                'expected_rfc3986' => 'a=&b',
            ],
            'no value stripping' => [
                'pairs' => [['a', 'b=']],
                'expected_rfc1738' => 'a=b=',
                'expected_rfc3986' => 'a=b=',
            ],
            'key only' => [
                'pairs' => [['a', null]],
                'expected_rfc1738' => 'a',
                'expected_rfc3986' => 'a',
            ],
            'preserve falsey 1' => [
                'pairs' => [['0', null]],
                'expected_rfc1738' => '0',
                'expected_rfc3986' => '0',
            ],
            'preserve falsey 2' => [
                'pairs' => [['0', '']],
                'expected_rfc1738' => '0=',
                'expected_rfc3986' => '0=',
            ],
            'preserve falsey 3' => [
                'pairs' => [['0', '0']],
                'expected_rfc1738' => '0=0',
                'expected_rfc3986' => '0=0',
            ],
            'rcf1738' => [
                'pairs' => [['toto', 'foo+bar']],
                'expected_rfc1738' => 'toto=foo%2Bbar',
                'expected_rfc3986' => 'toto=foo+bar',
            ],
        ];
    }

    /**
     * @dataProvider failedBuilderProvider
     */
    public function testBuildQueryThrowsException(iterable $pairs, int $enc_type): void
    {
        self::expectException(InvalidQueryPair::class);
        QueryString::build($pairs, '&', $enc_type);
    }

    public function failedBuilderProvider(): array
    {
        return [
            'The collection can not contain empty pair' => [
                [[]],
                PHP_QUERY_RFC1738,
            ],
            'The pair key must be stringable' => [
                [[date_create(), 'bar']],
                PHP_QUERY_RFC1738,
            ],
            'The pair value must be stringable or null - rfc3986/rfc1738' => [
                [['foo', date_create()]],
                PHP_QUERY_RFC3986,
            ],
            'identical keys with associative array' => [
                new ArrayIterator([['key' => 'a', 'value' => true] , ['key' => 'a', 'value' => '2']]),
                PHP_QUERY_RFC3986,
            ],
            'Object' => [
                [['a[]', (object) '1']],
                PHP_QUERY_RFC1738,
            ],
        ];
    }
}
