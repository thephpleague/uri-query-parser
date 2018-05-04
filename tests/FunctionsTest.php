<?php

namespace LeagueTest\Uri;

use ArrayIterator;
use League\Uri;
use League\Uri\EncodingInterface;
use League\Uri\Parser\InvalidQueryPair;
use League\Uri\Parser\InvalidQueryString;
use League\Uri\Parser\UnknownEncoding;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group query
 * @group function
 */
class FunctionsTest extends TestCase
{
    public function testEncodingThrowsExceptionWithQueryParser()
    {
        $this->expectException(UnknownEncoding::class);
        Uri\query_parse('foo=bar', '&', 42);
    }

    public function testInvalidQueryStringThrowsExceptionWithQueryParser()
    {
        $this->expectException(InvalidQueryString::class);
        Uri\query_parse("foo=bar\0");
    }

    public function testEncodingThrowsExceptionWithQueryBuilder()
    {
        $this->expectException(UnknownEncoding::class);
        Uri\query_build([['foo', 'bar']], '&', 42);
    }

    public function testBuildThrowsExceptionWithQueryBuilder()
    {
        $this->expectException(InvalidQueryPair::class);
        Uri\query_build([['foo', 'boo' => 'bar']]);
    }

    public function testWrongTypeThrowExceptionParseQuery()
    {
        $this->expectException(TypeError::class);
        Uri\query_parse(['foo=bar'], '&', PHP_QUERY_RFC1738);
    }

    /**
     * @dataProvider extractQueryProvider
     *
     * @param string $query
     * @param array  $expectedData
     */
    public function testExtractQuery($query, $expectedData)
    {
        $this->assertSame($expectedData, Uri\query_extract($query));
    }

    public function extractQueryProvider()
    {
        return [
            [
                'query' => '&&',
                'expected' => [],
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
        ];
    }

    /**
     * @dataProvider parserProvider
     * @param string $query
     * @param string $separator
     * @param array  $expected
     * @param int    $encoding
     */
    public function testParse($query, $separator, $expected, $encoding)
    {
        $this->assertSame($expected, Uri\query_parse($query, $separator, $encoding));
    }

    public function parserProvider()
    {
        return [
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
                [['to to', 'foo bar']],
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
     * @param array  $pairs
     * @param string $expected_rfc1738
     * @param string $expected_rfc3986
     * @param string $expected_rfc3987
     * @param string $expected_no_encoding
     */
    public function testBuild(
        $pairs,
        $expected_rfc1738,
        $expected_rfc3986,
        $expected_rfc3987,
        $expected_no_encoding
    ) {
        $this->assertSame($expected_rfc1738, Uri\query_build($pairs, '&', PHP_QUERY_RFC1738));
        $this->assertSame($expected_rfc3986, Uri\query_build($pairs, '&', PHP_QUERY_RFC3986));
        $this->assertSame($expected_rfc3987, Uri\query_build($pairs, '&', EncodingInterface::RFC3987_ENCODING));
        $this->assertSame($expected_no_encoding, Uri\query_build($pairs, '&', EncodingInterface::NO_ENCODING));
    }

    public function buildProvider()
    {
        return [
            'empty string' => [
                'pairs' => [],
                'expected_rfc1738' => null,
                'expected_rfc3986' => null,
                'expected_rfc3987' => null,
                'expected_no_encoding' => null,
            ],
            'identical keys' => [
                'pairs' => new ArrayIterator([['a', true] , [true, 'a']]),
                'expected_rfc1738' => 'a=1&1=a',
                'expected_rfc3986' => 'a=1&1=a',
                'expected_rfc3987' => 'a=1&1=a',
                'expected_no_encoding' => 'a=1&1=a',
            ],
            'no value' => [
                'pairs' => [['a', null], ['b', null]],
                'expected_rfc1738' => 'a&b',
                'expected_rfc3986' => 'a&b',
                'expected_rfc3987' => 'a&b',
                'expected_no_encoding' => 'a&b',
            ],
            'empty value' => [
                'pairs' => [['a', ''], ['b', 1.3]],
                'expected_rfc1738' => 'a=&b=1.3',
                'expected_rfc3986' => 'a=&b=1.3',
                'expected_rfc3987' => 'a=&b=1.3',
                'expected_no_encoding' => 'a=&b=1.3',
            ],
            'php array (1)' => [
                'pairs' => [['a[]', '1%a6'], ['a[]', '2']],
                'expected_rfc1738' => 'a%5B%5D=1%25a6&a%5B%5D=2',
                'expected_rfc3986' => 'a%5B%5D=1%25a6&a%5B%5D=2',
                'expected_rfc3987' => 'a[]=1%a6&a[]=2',
                'expected_no_encoding' => 'a[]=1%a6&a[]=2',
            ],
            'php array (2)' => [
                'pairs' => [['module', 'home'], ['action', 'show'], ['page', '😓']],
                'expected_rfc1738' => 'module=home&action=show&page=%F0%9F%98%93',
                'expected_rfc3986' => 'module=home&action=show&page=%F0%9F%98%93',
                'expected_rfc3987' => 'module=home&action=show&page=😓',
                'expected_no_encoding' => 'module=home&action=show&page=😓',
            ],
            'php array (3)' => [
                'pairs' => [['module', 'home'], ['action', 'v%61lue']],
                'expected_rfc1738' => 'module=home&action=v%61lue',
                'expected_rfc3986' => 'module=home&action=v%61lue',
                'expected_rfc3987' => 'module=home&action=v%61lue',
                'expected_no_encoding' => 'module=home&action=v%61lue',
            ],
            'preserve dot' => [
                'pairs' => [['a.b', '3']],
                'expected_rfc1738' => 'a.b=3',
                'expected_rfc3986' => 'a.b=3',
                'expected_rfc3987' => 'a.b=3',
                'expected_no_encoding' => 'a.b=3',
            ],
            'no key stripping' => [
                'pairs' => [['a', ''], ['b', null]],
                'expected_rfc1738' => 'a=&b',
                'expected_rfc3986' => 'a=&b',
                'expected_rfc3987' => 'a=&b',
                'expected_no_encoding' => 'a=&b',
            ],
            'no value stripping' => [
                'pairs' => [['a', 'b=']],
                'expected_rfc1738' => 'a=b=',
                'expected_rfc3986' => 'a=b=',
                'expected_rfc3987' => 'a=b=',
                'expected_no_encoding' => 'a=b=',
            ],
            'key only' => [
                'pairs' => [['a', null]],
                'expected_rfc1738' => 'a',
                'expected_rfc3986' => 'a',
                'expected_rfc3987' => 'a',
                'expected_no_encoding' => 'a',
            ],
            'preserve falsey 1' => [
                'pairs' => [['0', null]],
                'expected_rfc1738' => '0',
                'expected_rfc3986' => '0',
                'expected_rfc3987' => '0',
                'expected_no_encoding' => '0',
            ],
            'preserve falsey 2' => [
                'pairs' => [['0', '']],
                'expected_rfc1738' => '0=',
                'expected_rfc3986' => '0=',
                'expected_rfc3987' => '0=',
                'expected_no_encoding' => '0=',
            ],
            'preserve falsey 3' => [
                'pairs' => [['0', '0']],
                'expected_rfc1738' => '0=0',
                'expected_rfc3986' => '0=0',
                'expected_rfc3987' => '0=0',
                'expected_no_encoding' => '0=0',
            ],
            'rcf1738' => [
                'pairs' => [['toto', 'foo+bar']],
                'expected_rfc1738' => 'toto=foo%2Bbar',
                'expected_rfc3986' => 'toto=foo+bar',
                'expected_rfc3987' => 'toto=foo+bar',
                'expected_no_encoding' => 'toto=foo+bar',
            ],
        ];
    }

    public function testBuildQueryThrowsExceptionOnWrongType()
    {
        $this->expectException(TypeError::class);
        Uri\query_build(\date_create());
    }

    /**
     * @dataProvider failedBuilderProvider
     *
     * @param mixed $pairs
     * @param int   $enc_type
     */
    public function testBuildQueryThrowsException($pairs, $enc_type)
    {
        $this->expectException(InvalidQueryPair::class);
        Uri\query_build($pairs, '&', $enc_type);
    }

    public function failedBuilderProvider()
    {
        return [
            'The collection can not contain empty pair' => [
                [[]],
                PHP_QUERY_RFC1738,
            ],
            'The pair key must be stringable' => [
                [[\date_create(), 'bar']],
                PHP_QUERY_RFC1738,
            ],
            'The pair value must be stringable or null - rfc3986/rfc1738' => [
                [['foo', \date_create()]],
                PHP_QUERY_RFC3986,
            ],
            'The pair value must be stringable or null - rfc3987' => [
                [['foo', \date_create()]],
                EncodingInterface::RFC3987_ENCODING,
            ],
            'The pair value must be stringable or null - raw' => [
                [['foo', \date_create()]],
                EncodingInterface::NO_ENCODING,
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
