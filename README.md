# BBPHP Criteria Doctrine Adapter

A PHP adapter that converts the validated criteria model into Doctrine Collections `Criteria`, making it easy to apply filters, ordering, and pagination from a domain-level abstraction without coupling repositories to Doctrine-specific code.

## Description

BBPHP Criteria Doctrine Adapter is the persistence adapter for `xsga/bbphp-criteria-core`. It takes the immutable `Criteria` object created by the core library and transforms it into a Doctrine `Common\Collections\Criteria` instance ready to be used with `EntityRepository::matching()`.

This package keeps the concerns cleanly separated:

- the core library validates and structures query criteria,
- this adapter translates those criteria into Doctrine-compatible expressions.

The result is a simple, reusable, and typed query layer that stays independent from business logic and direct SQL assembly.

## Features

- Conversion from `Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria` to Doctrine `Criteria`
- Support for `eq`, `ne`, `lk`, `gt`, `lt`, `ge`, `le`, and `in` operators
- Correct handling of `IN` predicates using pipe-delimited values such as `1|2|3`
- Sort mapping for one or multiple fields
- `limit` and `offset` propagation to Doctrine
- Small, focused adapter with a single responsibility
- Compatible with the core criteria API and Doctrine Collections

## Requirements

- PHP 8.4+
- Composer
- `doctrine/collections` ^2.3
- `xsga/bbphp-criteria-core` ^1.0

## Installation

```bash
composer require xsga/bbphp-criteria-doctrine-adapter
```

## Quick example

```php
use Psr\Log\NullLogger;
use Xsga\BBPHP\Criteria\Adapter\Doctrine\DoctrineCriteriaConverter;
use Xsga\BBPHP\Criteria\Core\Application\Services\CriteriaService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetFiltersService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetOrderService;
use Xsga\BBPHP\Criteria\Core\Domain\Services\GetPaginationService;

$logger = new NullLogger();

$criteriaService = new CriteriaService(
    new GetFiltersService($logger),
    new GetOrderService($logger),
    new GetPaginationService(),
    50
);

$criteria = $criteriaService->getCriteria([
    'filter' => 'status:eq:active,createdAt:ge:2025-01-01:date',
    'order_by' => 'createdAt:DESC',
    'limit' => 10,
    'offset' => 0,
]);

$converter = new DoctrineCriteriaConverter();
$doctrineCriteria = $converter->get($criteria);

$results = $repository->matching($doctrineCriteria);
```

## How the adapter works

The flow is intentionally simple:

```text
Core criteria model
        ↓
DoctrineCriteriaConverter::get($criteria)
        ↓
Doctrine\Common\Collections\Criteria
        ↓
EntityRepository::matching($criteria)
```

### Filters

Each filter of the core domain is converted to a Doctrine `Comparison` expression:

```php
new Comparison('status', '=', 'active');
```

For an `in` filter, the adapter splits pipe-separated values:

```text
filter=id:in:1|2|3
```

which becomes:

```php
new Comparison('id', 'in', ['1', '2', '3']);
```

### Ordering

The order definitions are mapped to Doctrine's expected associative array:

```php
[
    'createdAt' => 'DESC',
    'name' => 'ASC',
]
```

### Pagination

The domain pagination is converted directly into Doctrine `offset` and `limit` values.

## Supported input format

This package relies on the criteria notation defined by the core library.

### Filters

```text
filter=field:operator:value[:type]
```

Examples:

```text
filter=status:eq:active
filter=createdAt:ge:2025-01-01:date
filter=id:in:1|2|3
filter=email:lk:john
```

Multiple filters are combined using AND:

```text
filter=status:eq:active,createdAt:ge:2025-01-01:date
```

### Ordering

```text
order_by=field:type
```

Examples:

```text
order_by=createdAt:DESC
order_by=name:ASC,createdAt:DESC
```

### Pagination

```text
limit=20&offset=0
```

## Internal structure

```text
Src/Xsga/BBPHP-Criteria-Doctrine-Adapter/
├── DoctrineCriteriaConverter.php
├── DoctrineSQLDto.php
└── ...
```

### `DoctrineCriteriaConverter`

The main adapter that transforms a domain-level `Criteria` object into a Doctrine `CollectionsCriteria` instance.

Responsibilities:

- build a `CompositeExpression` from filters
- map each domain filter to a Doctrine comparison
- handle `in` filters as arrays from pipe-delimited values
- transform order definitions to Doctrine order-by input
- set `offset` and `limit`

## Design principles

- Validation remains in the core criteria package
- Persistence adaptation happens in a dedicated adapter
- Immutable domain models
- Explicit operator validation
- Clean separation of concerns

## Related packages

- `xsga/bbphp-criteria-core` — typed criteria model and validation
- `doctrine/collections` — filtering and sorting primitives used by this adapter

## Documentation

- [Doc/BBPHP-Criteria-Doctrine-Adapter.md](Doc/BBPHP-Criteria-Doctrine-Adapter.md)
- [Doc/BBPHP-Criteria-Doctrine-Adapter.es.md](Doc/BBPHP-Criteria-Doctrine-Adapter.es.md)

## License

This project is licensed under the MIT License.
