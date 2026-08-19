<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Adapter\Doctrine;

final class DoctrineSQLDto
{
    public string $sql = '';
    public array $params = [];
}
