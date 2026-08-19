<?php

declare(strict_types=1);

namespace Xsga\BBPHP\Criteria\Adapter\Doctrine;

use Doctrine\Common\Collections\Criteria as CollectionsCriteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Xsga\BBPHP\Criteria\Core\Domain\Model\Criteria;
use Xsga\BBPHP\Criteria\Core\Domain\Model\Filter;
use Xsga\BBPHP\Criteria\Core\Domain\Operators;

final class DoctrineCriteriaConverter
{
    public function get(Criteria $criteria): CollectionsCriteria
    {
        return new CollectionsCriteria(
            $this->getFilters($criteria),
            $this->getOrderBy($criteria),
            $criteria->pagination()->offset(),
            $criteria->pagination()->limit()
        );
    }

    private function getFilters(Criteria $criteria): ?CompositeExpression
    {
        if (empty($criteria->filters())) {
            return null;
        }

        $filters = array_map(
            fn(Filter $filter): Comparison => match ($filter->operator()) {
                strtoupper(Operators::IN->value) => $this->getComparisonInOperator($filter),
                default => $this->getComparison($filter),
            },
            $criteria->filters()
        );

        return new CompositeExpression(CompositeExpression::TYPE_AND, $filters);
    }

    private function getComparison(Filter $filter): Comparison
    {
        return new Comparison($filter->field(), $filter->operator(), $filter->value());
    }

    private function getComparisonInOperator(Filter $filter): Comparison
    {
        /** @var string $filterValue */
        $filterValue = $filter->value();

        return new Comparison($filter->field(), $filter->operator(), explode('|', $filterValue));
    }

    /** @return array<string,string>|null */
    private function getOrderBy(Criteria $criteria): ?array
    {
        if (empty($criteria->orders())) {
            return null;
        }

        $orders = [];

        foreach ($criteria->orders() as $order) {
            $orders[$order->field()] = $order->type();
        }

        return $orders;
    }
}
