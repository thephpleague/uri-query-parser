Uri Query Parser
=======

[![Build Status](https://img.shields.io/travis/thephpleague/uri-query-parser/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-query-parser)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-query-parser.svg?style=flat-square)](https://github.com/thephpleague/uri-query-parser/releases)

This package contains a userland PHP uri query parser and builder.

```php
<?php

use function League\Uri\query_parse;
use function League\Uri\query_build;

$pairs = query_parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];

$str = query_build([['module', 'home'], ['action', 'show'], ['page', '😓']]);
// returns 'module=home&action=show&page=😓'
```

System Requirements
-------

You need:

- **PHP >= 7.0** but the latest stable version of PHP is recommended

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

use function League\Uri\query_parse;

$pairs = query_parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];
```

The returned array is a collection of key/value pairs. Each pair is represented as an array where the first element is the pair key and the second element the pair value. While the pair key is always a string, the pair value can be a string or the `null` value.

The `League\Uri\query_parse` :

- accepts the `null` value, any scalar or object which is stringable;

By default the function

- assumes that the query separator is the `&` character;
- assumes that the query string is encoded following [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4);

But you can specify the separator character as well as the string encoding algorithm using its other two arguments.


```php
<?php

use function League\Uri\query_parse;

$pairs = query_parse(
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

To convert back the collection of key/value pairs into a valid query string you can use the `League\Uri\query_build` function.

```php
<?php

use function League\Uri\query_build;

$pairs = query_build([
    ['module', 'home'],
    ['action', 'show'],
    ['page', 'toto bar'],
    ['action', 'hide'],
], '|', PHP_QUERY_RFC3986);

// returns 'module=home|action=show|page=toto%20bar|action=hide';
```

The `League\Uri\query_build` :

- accepts any iterable structure containing a collection of key/pair pairs as describe in the returned array of the `League\Uri\query_parse` function.

Just like with `League\Uri\query_parse`, you can specify the separator and the encoding algorithm to use.

### Extracting PHP variables

`League\Uri\query_parse` and `League\Uri\query_build` preserve the query string pairs content and order. If you want to extract PHP variables from the query string *à la* `parse_str` you can use `League\Uri\query_extract`. The function:

- takes the same paramters as `League\Uri\query_parse`
- does not allow parameters key mangling in the returned array;

```php
<?php

use function League\Uri\query_extract;

$query = 'module=show&arr.test[1]=sid&arr test[4][two]=fred&+module+=hide';

$params = query_extract($query, '&', PHP_QUERY_RFC1738);
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

- If the query string is invalid a `League\Uri\Parser\InvalidQueryString` exception is thrown.
- If the query pair is invalid a `League\Uri\Parser\InvalidQueryPair` exception is thrown.
- If the encoding algorithm is unknown or invalid a `League\Uri\Parser\UnknownEncoding` exception is thrown.

All exceptions extends the `League\Uri\Parser\InvalidUriComponent` marker class which extends PHP's `InvalidArgumentException` class.

```php
<?php

use League\Uri\Parser\InvalidUriComponent;
use function League\Uri\query_extract;

try {
	query_extract('foo=bar', '&', 42);
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