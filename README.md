Uri Query Parser
=======

[![Build Status](https://img.shields.io/travis/thephpleague/uri-query-parser/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-query-parser)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-query-parser.svg?style=flat-square)](https://github.com/thephpleague/uri-query-parser/releases)

# THIS PACKAGE IS ON MAINTENANCE MODE SINCE 2019-10-18, FOR ANY NEW PROJECT PLEASE CONSIDER USING [league/uri-components v2+](https://github.com/thephpleague/uri-components)

This package contains a userland PHP uri query parser and builder.

```php
<?php

use League\Uri\Parser\QueryString;

$pairs = QueryString::parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];

$str = QueryString::build($pairs, '|');
// returns 'module=home|action=show|page=😓'
```

System Requirements
-------

You need:

- **PHP >= 7.1.3** but the latest stable version of PHP is recommended

Installation
--------

```bash
$ composer require league/uri-query-parser
```

Documentation
--------

**The parsing/building algorithms preserve pairs order and uses the same algorithm used by JavaScript [UrlSearchParams](https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/URLSearchParams)**

### Parsing the URI query string

Parsing a query string is easy.

```php
<?php

use League\Uri\Parser\QueryString;

$pairs = QueryString::parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];
```

#### Description

```php
<?php

public static function QueryString::parse($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): array;
```

The returned array is a collection of key/value pairs. Each pair is represented as an array where the first element is the pair key and the second element the pair value. While the pair key is always a string, the pair value can be a string or the `null` value.

The `League\Uri\QueryString::parse` parameters are

- `$query` can be the `null` value, any scalar or object which is stringable;
- `$separator` is a string; by default it is the `&` character;
- `$enc_type` is one of PHP's constant `PHP_QUERY_RFC3968` or `PHP_QUERY_RFC1738` which represented the supported encoding algoritm
    - If you specify `PHP_QUERY_RFC3968` decoding will be done using [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) rules;
    - If you specify `PHP_QUERY_RFC1738` decoding will be done using [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) rules;

Here's a simple example showing how to use all the given parameters:

```php
<?php

use League\Uri\QueryString;

$pairs = QueryString::parse(
    'module=home:action=show:page=toto+bar&action=hide',
    ':',
    PHP_QUERY_RFC1738
);
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', 'toto bar'],
//     ['action', 'hide'],
// ];
```

### Building the URI query string

To convert back the collection of key/value pairs into a valid query string or the `null` value you can use the `QueryString::build` function.

```php
<?php

use League\Uri\Parser\QueryString;

$pairs = QueryString::build([
    ['module', 'home'],
    ['action', 'show'],
    ['page', 'toto bar'],
    ['action', 'hide'],
], '|', PHP_QUERY_RFC3986);

// returns 'module=home|action=show|page=toto%20bar|action=hide';
```

#### Description

```php
<?php

public static function QueryString::build(iterable $pairs, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): ?string;
```

The `QueryString::build` :

- accepts any iterable structure containing a collection of key/pair pairs as describe in the returned array of the QueryString::parse` function.

Just like with `QueryString::parse`, you can specify the separator and the encoding algorithm to use.

- the function returns the `null` value if an empty array or collection is given as input.

### Extracting PHP variables

```php
<?php

public static function QueryString::extract($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986): array;
public static function QueryString::convert(iterable $pairs): array;
```

`QueryString::parse` and `QueryString::build` preserve the query string pairs content and order. If you want to extract PHP variables from the query string *à la* `parse_str` you can use:

- The `QueryString::extract` method which takes the same parameters as `League\Uri\QueryString::parse`
- The `QueryString::convert` method which takes the result of `League\Uri\QueryString::parse`

both methods, however, do not allow parameters key mangling in the returned array like  `parse_str`;

```php
<?php

use League\Uri\Parser\QueryString;

$query = 'module=show&arr.test[1]=sid&arr test[4][two]=fred&+module+=hide';

$params = QueryString::extract($query, '&', PHP_QUERY_RFC1738);
// $params contains [
//     'module' = 'show',
//     'arr.test' => [
//         1 => 'sid',
//     ],
//     'arr test' => [
//         4 => [
//             'two' => 'fred',
//         ]
//     ],
//     ' module ' => 'hide',
// ];

parse_str($query, $variables);
// $variables contains [
//     'module' = 'show',
//     'arr_test' => [
//         1 => 'sid',
//         4 => [
//             'two' => 'fred',
//         ],
//     ],
//     'module_' = 'hide',
// ];
```

### Exceptions

All exceptions extends the `League\Uri\Parser\InvalidUriComponent` marker class which extends PHP's `InvalidArgumentException` class.

- If the query string is invalid a `League\Uri\Exception\MalformedUriComponent` exception is thrown.
- If the query pair is invalid a `League\Uri\Parser\InvalidQueryPair` exception is thrown.
- If the encoding algorithm is unknown or invalid a `League\Uri\Parser\UnknownEncoding` exception is thrown.

```php
<?php

use League\Uri\Exception\InvalidUriComponent;
use League\Uri\Parser\QueryString;

try {
    QueryString::extract('foo=bar', '&', 42);
} catch (InvalidUriComponent $e) {
    //$e is an instanceof League\Uri\Parser\UnknownEncoding
}
```

Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

Testing
-------

The library has a has a :

- a [PHPUnit](https://phpunit.de) test suite
- a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).

To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

Security
-------

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

Credits
-------

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/thephpleague/uri-query-parser/contributors)

License
-------

The MIT License (MIT). Please see [License File](LICENSE) for more information.
