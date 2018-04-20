# Changelog

All Notable changes to League Uri Query Parser will be documented in this file

## Next - TBD

### Added

- `League\Uri\query_parse`
- `League\Uri\query_build`
- `League\Uri\query_extract`
- `League\Uri\Parser\QueryBuilder`
- `League\Uri\Parser\QueryParser`
- `League\Uri\Parser\InvalidArgument`
- `League\Uri\Parser\UnknownEncoding`

### Fixed

- `QueryParser` and `QueryBuilder` query structure is changed from the URI component package to preserve query pair order.

### Deprecated

- None

### Remove

- `League\Uri\parse_query`
- `League\Uri\build_query`
- `League\Uri\extract_query`
- `League\Uri\QueryBuilder`
- `League\Uri\QueryParser`
