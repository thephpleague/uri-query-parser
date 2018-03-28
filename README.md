Uri Query Parser
=======

[![Build Status](https://img.shields.io/travis/thephpleague/uri-query-parser/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-query-parser)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-query-parser.svg?style=flat-square)](https://github.com/thephpleague/uri-query-parser/releases)

This package contains a userland PHP uri query parser and builder.

```php
<?php

use League\Uri;

$pairs = Uri\query_parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];

$str = Uri\query_build([['module', 'home'], ['action', 'show'], ['page', '😓']]);
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

use League\Uri;

$pairs = Uri\query_parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];
```

The returned array is a collection of key/value pairs. Each pair is represented as an array where the first element is the pair key and the second element the pair value. While the pair key is always a string, the pair value can be a string or the `null` value.

By default `League\Uri\query_parse` will assume that the query separator is the `&` character and that the query string is encoded following [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4). But you can specify the separator character as well as the string encoding algorithm using its other two arguments.


```php
<?php

use League\Uri;

$pairs = Uri\query_parse(
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

use League\Uri;

$pairs = Uri\query_build([
    ['module', 'home'],
    ['action', 'show'],
    ['page', 'toto bar'],
    ['action', 'hide'],
], '|', PHP_QUERY_RFC3986);

// returns 'module=home|action=show|page=toto%20bar|action=hide';
```

Just like with `League\Uri\query_parse`, you can specify the separator and the encoding algorithm to use.

### Extracting PHP variables

While `League\Uri\query_parse` and `League\Uri\query_build` preserves the query string pairs content and order. If you want to extract PHP variables from the query string *à la* `parse_str` but without content mangling you can use `League\Uri\extract_query`. This functions takes the exact same argument as `League\Uri\query_parse`.

```php
<?php

use League\Uri;

$query = 'module=show&arr.test[1]=sid&arr test[4][two]=fred&module=hide';

$params = Uri\extract_query($query, '&', PHP_QUERY_RFC1738);
// returns [
//     'module' = 'hide',
//     'arr.test' => [
//         1 => 'sid',
//     ],
//     'arr test' => [
//         4 => [
//             'two' => 'fred',
//         ]
//     ],
// ];

parse_str($query, $variables);
// $variables contains [
//     'module' = 'hide',
//     'arr_test' => [
//         1 => 'sid',
//         4 => [
//             'two' => 'fred',
//         ],
//     ],
// ];
```

**If the encoding algorithm is unknown, the query string contains invalid characters, or the collection of key/value pairs is malformed the functions will throw an Exception.**

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