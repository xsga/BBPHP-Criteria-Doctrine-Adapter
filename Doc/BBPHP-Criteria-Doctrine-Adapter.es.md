# BBPHP Criteria Doctrine Adapter

Una librería PHP que adapta el modelo de criterios principal a `Criteria` de Doctrine Collections, permitiendo que repositorios y servicios apliquen filtros validados, ordenación y paginación sin acoplar la capa de negocio al código específico de Doctrine.

## Descripción

BBPHP Criteria Doctrine Adapter se sitúa sobre `xsga/bbphp-criteria-core` y aporta la capa de infraestructura necesaria para transformar un objeto `Criteria` validado a nivel de dominio en una instancia de `Criteria` de Doctrine.

Este paquete no ejecuta SQL directamente. Su responsabilidad es única: actuar como puente entre el modelo de consulta producido por la librería base y Doctrine Collections para que pueda ser consumido por repositorios mediante `matching()`.

## Funcionalidades

- Conversión desde `Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria` a `Criteria` de Doctrine
- Compatibilidad con todos los operadores del core, incluidos `eq`, `ne`, `lk`, `gt`, `lt`, `ge`, `le` e `in`
- Manejo automático de filtros `IN` con formato separado por pipes (`1|2|3`)
- Soporte para ordenación por uno o varios campos
- Soporte para paginación con `limit` y `offset`
- Separación clara entre validación del dominio y adaptación de persistencia
- Adaptador ligero y sin lógica de persistencia propia

## Requisitos

- PHP 8.4+
- Composer
- `doctrine/collections` ^2.3
- `xsga/bbphp-criteria-core` ^1.0

## Instalación

```bash
composer require xsga/bbphp-criteria-doctrine-adapter
```

## Uso básico

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
```

Este objeto `doctrineCriteria` puede consumirse luego desde un repositorio basado en Doctrine:

```php
$results = $repository->matching($doctrineCriteria);
```

## Cómo funciona

El flujo es deliberadamente sencillo:

```text
Modelo de criterios del core
        ↓
DoctrineCriteriaConverter::get($criteria)
        ↓
Doctrine\Common\Collections\Criteria
        ↓
EntityRepository::matching($criteria)
```

### Conversión de filtros

Cada `Filter` del dominio se transforma en una expresión de comparación de Doctrine. Por ejemplo:

```php
new Comparison('status', '=', 'active');
```

En el caso de filtros `IN`, el adaptador divide el valor separado por pipes:

```text
filter=id:in:1|2|3
```

y lo convierte en:

```php
new Comparison('id', 'in', ['1', '2', '3']);
```

### Conversión de ordenación

La lista de órdenes del dominio se transforma en el array asociativo que espera Doctrine Collections:

```php
[
    'createdAt' => 'DESC',
    'name' => 'ASC',
]
```

### Conversión de paginación

La `Pagination` se mapea a los valores de `offset` y `limit` de Doctrine:

```php
new CollectionsCriteria(
    $expression,
    $orderBy,
    $criteria->pagination()->offset(),
    $criteria->pagination()->limit()
);
```

## Formato de entrada soportado

Este paquete depende de los formatos definidos por la librería central de criterios.

### Filtros

```text
filter=campo:operador:valor[:tipo]
```

Ejemplos:

```text
filter=status:eq:active
filter=createdAt:ge:2025-01-01:date
filter=id:in:1|2|3
filter=email:lk:john
```

Los filtros múltiples se combinan con AND:

```text
filter=status:eq:active,createdAt:ge:2025-01-01:date
```

### Ordenación

```text
order_by=campo:tipo
```

Ejemplos:

```text
order_by=createdAt:DESC
order_by=name:ASC,createdAt:DESC
```

### Paginación

```text
limit=20&offset=0
```

## Operadores soportados

El adaptador trabaja con el catálogo de operadores expuesto por la librería core:

- `eq` => igual
- `ne` => distinto
- `lk` => like / contiene
- `gt` => mayor que
- `lt` => menor que
- `ge` => mayor o igual que
- `le` => menor o igual que
- `in` => pertenece a una lista

## Arquitectura

### Responsabilidad del paquete

Este paquete es intencionadamente pequeño y enfocado:

- No define reglas de negocio.
- No parsea parámetros de query string.
- No ejecuta consultas.
- No posee lógica de validación más allá del proceso de conversión.

Su rol se limita a adaptar objetos `Criteria` ya validados hacia un formato compatible con Doctrine.

### Estructura interna

```text
Src/Xsga/BBPHP-Criteria-Doctrine-Adapter/
├── DoctrineCriteriaConverter.php
├── DoctrineSQLDto.php
└── ...
```

#### `DoctrineCriteriaConverter`

Adaptador principal que convierte un objeto `Criteria` de dominio en una instancia de `CollectionsCriteria` de Doctrine.

Responsabilidades:

- construir la `CompositeExpression` para filtros
- convertir cada filtro en una `Comparison` de Doctrine
- manejar filtros `in` dividiendo valores separados por pipes
- mapear la lista de ordenación al formato de `orderBy` de Doctrine
- establecer `offset` y `limit`

#### `DoctrineSQLDto`

DTO utilizado en el ecosistema del adaptador cuando la query generada necesita representarse como un payload SQL estructurado.

## Principios de diseño

- Validación orientada al dominio en la librería core
- Adaptación de persistencia en un paquete dedicado
- Modelos de criterios inmutables
- Tipado fuerte y validación explícita de operadores
- Superficie de dependencias mínima

## Paquetes relacionados

- `xsga/bbphp-criteria-core`: modelo tipado de criterios y capa de validación
- `doctrine/collections`: primitivas de filtrado y ordenación utilizadas por el adaptador

## Documentación adicional

Para consultar una referencia más profunda sobre el modelo de criterios, revisa la documentación del paquete core y la documentación del proyecto en la carpeta `Doc`.

## Licencia

Este proyecto está licenciado bajo la MIT License.
