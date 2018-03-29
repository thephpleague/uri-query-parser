# Changelog

All Notable changes to League Uri Query Parser will be documented in this file

## Next - TBD

### Added

- `League\Uri\query_parse`
- `League\Uri\query_build`
- `League\Uri\query_extract`
- `League\Uri\ExceptionInterface`
- `League\Uri\Exception\MalformedPair`
- `League\Uri\Exception\MalformedQuery`
- `League\Uri\Exception\UnsupportedEncoding`

### Fixed

- `QueryParser` and `QueryBuilder` query structure is changed from the URI component package to preserve query pair order

### Deprecated

- None

### Remove

- `League\Uri\parse_query`
- `League\Uri\build_query`
- `League\Uri\extract_query`