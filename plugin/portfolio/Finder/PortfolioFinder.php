<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Finder;

use Claroline\AppBundle\API\Finder\AbstractFinder;
use Claroline\PortfolioBundle\Entity\Portfolio;
use Doctrine\ORM\QueryBuilder;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.api.finder.portfolio")
 * @DI\Tag("claroline.finder")
 */
class PortfolioFinder extends AbstractFinder
{
    public function getClass()
    {
        return Portfolio::class;
    }

    public function configureQueryBuilder(
        QueryBuilder $qb,
        array $searches = [],
        array $sortBy = null,
        array $options = ['count' => false, 'page' => 0, 'limit' => -1]
    ) {
        $ownerJoin = false;

        foreach ($searches as $filterName => $filterValue) {
            switch ($filterName) {
                case 'owner':
                    if (!$ownerJoin) {
                        $qb->join('obj.owner', 'o');
                        $ownerJoin = true;
                    }
                    $qb->andWhere("o.uuid = :{$filterName}");
                    $qb->setParameter($filterName, $filterValue);
                    break;
                case 'owner.name':
                    if (!$ownerJoin) {
                        $qb->join('obj.owner', 'o');
                        $ownerJoin = true;
                    }
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->like(
                            "CONCAT(CONCAT(UPPER(o.firstName), ' '), UPPER(o.lastName))",
                            ':name'
                        ),
                        $qb->expr()->like(
                            "CONCAT(CONCAT(UPPER(o.lastName), ' '), UPPER(o.firstName))",
                            ':name'
                        )
                    ));
                    $qb->setParameter('name', '%'.strtoupper($filterValue).'%');
                    break;
                case 'owner.firstName':
                    if (!$ownerJoin) {
                        $qb->join('obj.owner', 'o');
                        $ownerJoin = true;
                    }
                    $qb->andWhere('UPPER(o.firstName) LIKE :firstName');
                    $qb->setParameter('firstName', '%'.strtoupper($filterValue).'%');
                    break;
                case 'owner.lastName':
                    if (!$ownerJoin) {
                        $qb->join('obj.owner', 'o');
                        $ownerJoin = true;
                    }
                    $qb->andWhere('UPPER(o.lastName) LIKE :lastName');
                    $qb->setParameter('lastName', '%'.strtoupper($filterValue).'%');
                    break;
                case 'meta.slug':
                    $qb->andWhere('UPPER(obj.slug) LIKE :slug');
                    $qb->setParameter('slug', '%'.strtoupper($filterValue).'%');
                    break;
                default:
                    if (is_bool($filterValue)) {
                        $qb->andWhere("obj.{$filterName} = :{$filterName}");
                        $qb->setParameter($filterName, $filterValue);
                    } else {
                        $qb->andWhere("UPPER(obj.{$filterName}) LIKE :{$filterName}");
                        $qb->setParameter($filterName, '%'.strtoupper($filterValue).'%');
                    }
            }
        }
        if (!is_null($sortBy) && isset($sortBy['property']) && isset($sortBy['direction'])) {
            $sortByProperty = $sortBy['property'];
            $sortByDirection = 1 === $sortBy['direction'] ? 'ASC' : 'DESC';

            switch ($sortByProperty) {
                case 'meta.slug':
                    $qb->orderBy('obj.slug', $sortByDirection);
                    break;
                case 'meta.visibility':
                    $qb->orderBy('obj.visibility', $sortByDirection);
                    break;
                case 'owner.firstName':
                    if (!$ownerJoin) {
                        $qb->join('obj.owner', 'o');
                    }
                    $qb->orderBy('o.firstName', $sortByDirection);
                    break;
                case 'owner.lastName':
                    if (!$ownerJoin) {
                        $qb->join('obj.owner', 'o');
                    }
                    $qb->orderBy('o.lastName', $sortByDirection);
                    break;
            }
        }

        return $qb;
    }
}
