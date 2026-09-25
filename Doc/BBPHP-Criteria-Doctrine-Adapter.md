# BBPHP Criteria Doctrine Adapter

A PHP library that adapts the core criteria model to Doctrine Collections `Criteria`, enabling repositories and services to apply validated filters, ordering, and pagination without coupling the business layer to Doctrine-specific code.

## Description

BBPHP Criteria Doctrine Adapter sits on top of `xsga/bbphp-criteria-core` and provides the infrastructure layer needed to transform a validated domain-level `Criteria` object into a Doctrine `Common\Collections\Criteria` instance.

This package does not execute SQL directly. Instead, it focuses on one responsibility: bridging the query model produced by the core library with Doctrine Collections so it can be consumed by repositories through `matching()` operations.

## Features

- Conversion from `Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria` to Doctrine `Collections\Criteria`
- Support for all core operators, including `eq`, `ne`, `lk`, `gt`, `lt`, `ge`, `le`, and `in`
- Automatic handling of `IN` values using the pipe-separated format (`1|2|3`)
- Support for ordering by one or multiple fields
- Pagination support with `limit` and `offset`
- Clean separation between domain validation and persistence adaptation
- Lightweight adapter with no persistence logic of its own

## Requirements

- PHP 8.4+
- Composer
- `doctrine/collections` ^2.3
- `xsga/bbphp-criteria-core` ^1.0

## Installation

```bash
composer require xsga/bbphp-criteria-doctrine-adapter
```

## Basic usage

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
doctrineCriteria = $converter->get($criteria);
```

This `doctrineCriteria` object can then be consumed by a Doctrine-backed repository:

```php
$results = $repository->matching($doctrineCriteria);
```

## How it works

The flow is intentionally simple:

```text
Core Criteria model
        ↓
DoctrineCriteriaConverter::get($criteria)
        ↓
Doctrine\Common\Collections\Criteria
        ↓
EntityRepository::matching($criteria)
```

### Filters conversion

Each domain `Filter` is transformed into a Doctrine comparison expression. For example:

```php
new Comparison('status', '=', 'active');
```

For `IN` filters, the adapter explodes a pipe-separated value string:

```text
filter=id:in:1|2|3
```

and converts it into:

```php
new Comparison('id', 'in', ['1', '2', '3']);
```

### Ordering conversion

The domain order list is transformed into the associative array expected by Doctrine Collections:

```php
[
    'createdAt' => 'DESC',
    'name' => 'ASC',
]
```

### Pagination conversion

`Pagination` is mapped to Doctrine offset and limit values:

```php
new CollectionsCriteria(
    $expression,
    $orderBy,
    $criteria->pagination()->offset(),
    $criteria->pagination()->limit()
);
```

## Supported input format

This package relies on the formats defined by the core criteria library.

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

Multiple filters are combined with AND:

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

## Supported operators

The adapter works with the operator catalog exposed by the core library:

- `eq` => equals
- `ne` => not equals
- `lk` => like / contains
- `gt` => greater than
- `lt` => less than
- `ge` => greater than or equal
- `le` => less than or equal
- `in` => membership in a list

## Architecture

### Package responsibility

This package is intentionally small and focused:

- It does not define business rules.
- It does not parse query parameters.
- It does not execute queries.
- It does not own validation logic beyond the conversion process itself.

Its role is limited to adapting already-valid `Criteria` objects into Doctrine-compatible criteria.

### Internal structure

```text
Src/Xsga/BBPHP-Criteria-Doctrine-Adapter/
├── DoctrineCriteriaConverter.php
├── DoctrineSQLDto.php
└── ...
```

#### `DoctrineCriteriaConverter`

Main adapter that converts a domain-level `Criteria` object into a Doctrine `CollectionsCriteria` instance.

Responsibilities:

- build the `CompositeExpression` for filters
- convert each filter into a Doctrine `Comparison`
- handle `in` filters by splitting pipe-separated values
- map the order list to Doctrine order-by syntax
- set `offset` and `limit`

#### `DoctrineSQLDto`

A DTO used in the adapter ecosystem when the generated query needs to be represented as a structural SQL payload.

## Design principles

- Domain-first validation in the core library
- Persistence adaptation in a dedicated adapter package
- Immutable criteria models
- Strong typing and explicit operator validation
- Minimal dependency surface

## Related packages

- `xsga/bbphp-criteria-core`: typed criteria model and validation layer
- `doctrine/collections`: filtering and ordering primitives used by the adapter

## Additional documentation

For a deeper reference to the criteria model itself, see the core documentation in the corresponding package and the project documentation in the `Doc` folder.

## License

This project is licensed under the MIT License.
