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

use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceRights;
use Claroline\CoreBundle\Entity\Role;
use Doctrine\ORM\EntityRepository;

class ResourceRightsRepository extends EntityRepository
{
    /**
     * Returns the maximum rights on a given resource for a set of roles.
     * Used by the ResourceVoter.
     *
     * @param array[string] $rights
     * @param ResourceNode  $resource
     *
     * @return array
     */
    public function findMaximumRights(array $roles, ResourceNode $resource)
    {
        //add the role anonymous for everyone !
        if (!in_array('ROLE_ANONYMOUS', $roles)) {
            $roles[] = 'ROLE_ANONYMOUS';
        }

        $dql = '
            SELECT rrw.mask
            FROM Claroline\CoreBundle\Entity\Resource\ResourceRights rrw
            JOIN rrw.role role
            JOIN rrw.resourceNode resource
            WHERE ';

        $index = 0;

        foreach ($roles as $key => $role) {
            $dql .= 0 !== $index ? ' OR ' : '';
            $dql .= "resource.id = {$resource->getId()} AND role.name = :role{$key}";
            ++$index;
        }

        $query = $this->_em->createQuery($dql);

        foreach ($roles as $key => $role) {
            $query->setParameter("role{$key}", $role);
        }

        $results = $query->getResult();
        $mask = 0;

        foreach ($results as $result) {
            $mask |= $result['mask'];
        }

        return $mask;
    }

    /**
     * Returns the resource types a set of roles is allowed to create in a given
     * directory.
     *
     * @param array        $roles
     * @param ResourceNode $node
     *
     * @return array
     */
    public function findCreationRights(array $roles, ResourceNode $node)
    {
        if (0 === count($roles)) {
            throw new \RuntimeException('Roles cannot be empty');
        }

        $dql = '
            SELECT DISTINCT rType.name
            FROM Claroline\CoreBundle\Entity\Resource\ResourceType AS rType
            JOIN rType.rights right
            JOIN right.role role
            JOIN right.resourceNode resource
            WHERE ';

        $index = 0;

        foreach ($roles as $key => $role) {
            $dql .= 0 !== $index ? ' OR ' : '';
            $dql .= "resource.id = :nodeId AND role.name = :role_{$key}";
            ++$index;
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameter('nodeId', $node->getId());

        foreach ($roles as $key => $role) {
            $query->setParameter('role_'.$key, $role);
        }

        return $query->getArrayResult();
    }

    /**
     * @todo to be removed
     *
     * @return ResourceRights[]
     */
    public function findConfigurableRights(ResourceNode $resource)
    {
        $dql = "
            SELECT rights
            FROM Claroline\CoreBundle\Entity\Resource\ResourceRights rights
            JOIN rights.resourceNode resource
            JOIN rights.role role
            WHERE resource.id = :resourceId
            AND role.name <> :resourceManagerRole
            AND role.type <> :roleType
            ORDER BY role.name
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('resourceId', $resource->getId());
        $query->setParameter(
            'resourceManagerRole',
            'ROLE_WS_MANAGER_'.$resource->getWorkspace()->getGuid()
        );
        $query->setParameter('roleType', Role::USER_ROLE);

        return $query->getResult();
    }

    /**
     * Returns all the resource rights of a resource and its descendants.
     *
     * @param ResourceNode $resource
     *
     * @return array[ResourceRights]
     */
    public function findRecursiveByResource(ResourceNode $resource)
    {
        $dql = "
            SELECT rights, role, resource
            FROM Claroline\CoreBundle\Entity\Resource\ResourceRights rights
            JOIN rights.resourceNode resource
            JOIN rights.role role
            WHERE resource.path LIKE :path
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('path', $resource->getPath().'%');

        return $query->getResult();
    }

    /**
     * Find ResourceRights for each descendant of a resource for a role.
     *
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $resource
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     *
     * @return array
     */
    public function findRecursiveByResourceAndRole(ResourceNode $resource, Role $role)
    {
        $dql = "
            SELECT rights, role, resource
            FROM Claroline\CoreBundle\Entity\Resource\ResourceRights rights
            JOIN rights.resourceNode resource
            JOIN rights.role role
            WHERE resource.path LIKE :path AND role.name = :roleName
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('path', $resource->getPath().'%');
        $query->setParameter('roleName', $role->getName());

        return $query->getResult();
    }

    public function findUserRolesResourceRights(
        ResourceNode $resource,
        array $keys,
        $executeQuery = true
    ) {
        $dql = '
            SELECT rights
            FROM Claroline\CoreBundle\Entity\Resource\ResourceRights rights
            JOIN rights.resourceNode resource
            JOIN rights.role role
            WHERE resource = :resource
            AND role.type = :type
            AND role.translationKey IN (:keys)
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('resource', $resource);
        $query->setParameter('type', Role::USER_ROLE);
        $query->setParameter('keys', $keys);

        return $executeQuery ? $query->getResult() : $query;
    }
}
