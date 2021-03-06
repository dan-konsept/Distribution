<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository;

use Claroline\CoreBundle\Entity\Organization\Organization;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Repository for AbstractResource entities. The methods of this class may return
 * entities either as objects or as as arrays (see their respective documentation).
 */
class ResourceNodeRepository extends MaterializedPathRepository implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;
    /** @var ResourceQueryBuilder */
    private $builder;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $bundles = $this->container->get('claroline.manager.plugin_manager')->getEnabled(true);
        $this->builder = new ResourceQueryBuilder();
        $this->builder->setBundles($bundles);
    }

    public function search(string $search, int $nbResults)
    {
        return $this->createQueryBuilder('n')
            ->where('UPPER(n.name) LIKE :search')
            ->andWhere('n.active = true')
            ->andWhere('n.published = true')
            ->andWhere('n.hidden = false')
            ->setFirstResult(0)
            ->setMaxResults($nbResults)
            ->setParameter('search', '%'.strtoupper($search).'%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|int $id          The id or guid of the node
     * @param null       $lockMode
     * @param null       $lockVersion
     *
     * @return ResourceNode|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $qb = $this->createQueryBuilder('n');

        if (preg_match('/^\d+$/', $id)) {
            $qb->where('n.id = :id');
        } else {
            $qb->where('n.uuid = :id');
        }

        return $qb
            ->getQuery()
            ->setParameter('id', $id)
            ->getOneOrNullResult();
    }

    /**
     * Returns the root directory of a workspace.
     *
     * @param Workspace $workspace
     *
     * @return ResourceNode
     */
    public function findWorkspaceRoot(Workspace $workspace)
    {
        $this->builder->selectAsEntity()
            ->whereInWorkspace($workspace)
            ->whereParentIsNull();
        $query = $this->_em->createQuery($this->builder->getDql());
        $query->setParameters($this->builder->getParameters());

        /** @var ResourceNode[] $results */
        $results = $query->getResult();

        //in case something was messed up at some point
        if (1 === count($results)) {
            return $results[0];
        }

        //we find the one with the most children as a restoration trick
        $maxChildren = 0;
        $toReturn = 1 < count($results) ? $results[0] : null;

        foreach ($results as $result) {
            $count = count($result->getChildren());
            if ($count > $maxChildren) {
                $maxChildren = $count;
                $toReturn = $result;
            }
        }

        return $toReturn;
    }

    /**
     * Returns the descendants of a resource.
     *
     * @param ResourceNode $resource           The resource node to start with
     * @param bool         $includeStartNode   Whether the given resource should be included in the result
     * @param string       $filterResourceType A resource type to filter the results
     *
     * @return array[ResourceNode]
     */
    public function findDescendants(
        ResourceNode $resource,
        $includeStartNode = false,
        $filterResourceType = null
    ) {
        $this->builder->selectAsEntity(true)
            ->wherePathLike($resource->getPath(), $includeStartNode);

        if ($filterResourceType) {
            $this->builder->whereTypeIn([$filterResourceType]);
        }

        $query = $this->_em->createQuery($this->builder->getDql());
        $query->setParameters($this->builder->getParameters());

        return $query->getResult();
    }

    /**
     * Returns the immediate children of a resource that are viewable by any of the given roles.
     *
     * @param ResourceNode $parent           The id of the parent of the requested children
     * @param array        $roles            [string] $roles  An array of roles
     * @param User         $user             the user opening
     * @param bool         $withLastOpenDate with the last opened node (with the last opened date)
     * @param bool         $canAdministrate
     *
     * @throws \RuntimeException
     *
     * @return array[array] An array of resources represented as arrays
     */
    public function findChildren(ResourceNode $parent, array $roles, $user, $withLastOpenDate = false, $canAdministrate = false)
    {
        // if we usurpate a role, then it's like we're anonymous.
        if (in_array('ROLE_USURPATE_WORKSPACE_ROLE', $roles)) {
            $user = 'anon.';
        }

        if (0 === count($roles)) {
            throw new \RuntimeException('Roles cannot be empty');
        }

        $returnedArray = [];

        $isWorkspaceManager = $this->isWorkspaceManager($parent, $roles);
        //check if manager of the workspace.
        //if it's true, show every children
        if ($isWorkspaceManager) {
            $this->builder->selectAsArray()
                ->whereParentIs($parent)
                ->whereActiveIs(true)
                ->orderByIndex();
            $query = $this->_em->createQuery($this->builder->getDql());
            $query->setParameters($this->builder->getParameters());
            $items = $query->iterate(null, AbstractQuery::HYDRATE_ARRAY);

            foreach ($items as $key => $item) {
                $item[$key]['mask'] = 65535;
                $returnedArray[] = $item[$key];
            }
            //otherwise only show visible children
        } else {
            $this->builder->selectAsArray(true)
                ->whereParentIs($parent)
                ->whereActiveIs(true)
                ->whereHasRoleIn($roles);
            if (!$canAdministrate) {
                $this->builder->whereIsAccessible($user);
            }

            $query = $this->_em->createQuery($this->builder->getDql());
            $query->setParameters($this->builder->getParameters());

            $children = $query->getArrayResult();
            $childrenWithMaxRights = [];

            foreach ($children as $child) {
                if (!isset($childrenWithMaxRights[$child['id']])) {
                    $childrenWithMaxRights[$child['id']] = $child;
                }

                foreach ($childrenWithMaxRights as $id => $childMaxRights) {
                    if ($id === $child['id']) {
                        $childrenWithMaxRights[$id]['mask'] |= $child['mask'];
                    }
                }
            }

            $returnedArray = [];

            foreach ($childrenWithMaxRights as $childMaxRights) {
                $returnedArray[] = $childMaxRights;
            }
        }

        //now we get the last open date for nodes.
        //We can't do one request because of the left join + max combination

        if ($withLastOpenDate && 'anon.' !== $user) {
            $this->builder->selectAsArray(false, true)
                ->whereParentIs($parent)
                ->addLastOpenDate($user)
                ->groupById();

            if (!$isWorkspaceManager) {
                $this->builder->whereHasRoleIn($roles)->whereIsAccessible($user);
            }

            $query = $this->_em->createQuery($this->builder->getDql());
            $query->setParameters($this->builder->getParameters());
            $items = $query->getArrayResult();

            foreach ($returnedArray as $key => $returnedElement) {
                foreach ($items as $item) {
                    if ($item['id'] === $returnedElement['id']) {
                        $returnedArray[$key]['last_opened'] = $item['last_opened'];
                    }
                }
            }
        }

        //and now we order by index
        usort($returnedArray, function ($a, $b) {
            if ($a['index_dir'] === $b['index_dir']) {
                return 0;
            }

            return ($a['index_dir'] < $b['index_dir']) ? -1 : 1;
        });

        return $returnedArray;
    }

    /**
     * Returns an array of different file types with the number of resources that
     * belong to this type.
     *
     * @param int            $max
     * @param Organization[] $organizations
     *
     * @return array
     */
    public function findMimeTypesWithMostResources($max, array $organizations = [])
    {
        $qb = $this->createQueryBuilder('resource');
        $qb->select('resource.mimeType AS type, COUNT(resource.id) AS total')
            ->where($qb->expr()->isNotNull('resource.mimeType'))
            ->groupBy('resource.mimeType')
            ->orderBy('total', 'DESC');

        if (!empty($organizations)) {
            $qb->leftJoin('resource.workspace', 'ws')
                ->leftJoin('ws.organizations', 'o')
                ->andWhere('o IN (:organizations)')
                ->setParameter('organizations', $organizations);
        }

        if ($max > 1) {
            $qb->setMaxResults($max);
        }

        return $qb->getQuery()->getResult();
    }

    public function findLastIndex(ResourceNode $node)
    {
        $dql = '
            SELECT MAX(node.index)
            FROM Claroline\CoreBundle\Entity\Resource\ResourceNode node
            where node.parent = :node';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('node', $node->getId());

        return $query->getSingleScalarResult();
    }

    /**
     * @param string $name
     * @param array  $extraData
     * @param bool   $executeQuery
     *
     * @return QueryBuilder|array
     */
    public function findByName($name, $extraData = [], $executeQuery = true)
    {
        $name = strtoupper($name);
        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->createQueryBuilder('resourceNode');
        $queryBuilder->where($queryBuilder->expr()->like('UPPER(resourceNode.name)', ':name'));

        if (0 < count($extraData)) {
            foreach ($extraData as $key => $extra) {
                $queryBuilder
                    ->andWhere(sprintf('resourceNode.%s = :%s', $key, $key))
                    ->setParameter(sprintf(':%s', $key), $extra);
            }
        }

        $queryBuilder
            ->orderBy('resourceNode.name', 'ASC')
            ->setParameter(':name', "%{$name}%");

        return $executeQuery ? $queryBuilder->getQuery()->getResult() : $queryBuilder;
    }

    private function isWorkspaceManager(ResourceNode $node, array $roles)
    {
        $roleNames = [];

        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $roleNames[] = $role->getRole();
            } else {
                $roleNames[] = $role;
            }
        }

        $isWorkspaceManager = false;
        $ws = $node->getWorkspace();
        $managerRole = 'ROLE_WS_MANAGER_'.$ws->getUuid();

        if (in_array($managerRole, $roleNames)) {
            $isWorkspaceManager = true;
        }

        if (in_array('ROLE_ADMIN', $roleNames)) {
            $isWorkspaceManager = true;
        }

        return $isWorkspaceManager;
    }

    public function countActiveResources(array $workspaces = [], array $organizations = []): int
    {
        $qb = $this->createQueryBuilder('node')
            ->select('COUNT(node)')
            ->where('node.active = true');

        if (!empty($workspaces)) {
            $qb
                ->andWhere('node.workspace IN (:workspaces)')
                ->setParameter('workspaces', $workspaces);
        }

        if (!empty($organizations)) {
            $qb
                ->join('node.workspace', 'w')
                ->andWhere('w.organization IN (:organizations)')
                ->setParameter('organizations', $organizations);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
