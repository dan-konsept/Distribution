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
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Repository\Exception\MissingSelectClauseException;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Builder for DQL queries on AbstractResource entities.
 */
class ResourceQueryBuilder
{
    private $joinSingleRelatives;
    private $resultAsArray;
    private $leftJoinRights;
    private $leftJoinLogs;
    private $leftJoinPlugins;
    private $selectClause;
    private $whereClause;
    private $orderClause;
    private $groupByClause;
    private $joinClause;
    private $parameters;
    private $fromClause;
    private $joinRelativesClause;
    private $leftJoinRoles;
    private $bundles;

    public function init()
    {
        $eol = PHP_EOL;
        $this->fromClause = "FROM Claroline\CoreBundle\Entity\Resource\ResourceNode node{$eol}";
        $this->joinSingleRelatives = true;
        $this->resultAsArray = false;
        $this->leftJoinRights = false;
        $this->leftJoinLogs = false;
        $this->leftJoinPlugins = false;
        $this->selectClause = null;
        $this->whereClause = null;
        $this->orderClause = null;
        $this->groupByClause = null;
        $this->joinClause = '';
        $this->parameters = [];
        $this->leftJoinRoles = false;
        $this->bundles = [];

        $this->joinRelativesClause = "JOIN node.creator creator{$eol}".
            "JOIN node.resourceType resourceType{$eol}";
    }

    public function setBundles(array $bundles)
    {
        $this->bundles = $bundles;
        $this->leftJoinPlugins = true;
        //look at the getDql() method to see where it come from
        $this->addWhereClause('(CONCAT(p.vendorName, p.bundleName) IN (:bundles) OR rtp.plugin is NULL)');
        $this->parameters[':bundles'] = $bundles;
    }

    /**
     * Selects nodes as entities.
     *
     * @param bool   $joinSingleRelatives Whether the creator, type and icon must be joined to the query
     * @param string $class
     *
     * @return ResourceQueryBuilder
     */
    public function selectAsEntity($joinSingleRelatives = false, $class = null)
    {
        $this->init();
        $eol = PHP_EOL;

        if ($class) {
            $this->selectClause = 'SELECT resource'.PHP_EOL;
            $this->fromClause = "FROM {$class} resource{$eol} JOIN resource.resourceNode node{$eol}";
        } else {
            $this->selectClause = 'SELECT node'.PHP_EOL;
        }

        $this->joinSingleRelatives = $joinSingleRelatives;

        return $this;
    }

    /**
     * Filters nodes belonging to a given workspace.
     *
     * @param Workspace $workspace
     *
     * @return ResourceQueryBuilder
     */
    public function whereInWorkspace(Workspace $workspace)
    {
        $this->addWhereClause('node.workspace = :workspace_id');
        $this->parameters[':workspace_id'] = $workspace->getId();

        return $this;
    }

    /**
     * Filters nodes that are the immediate children of a given node.
     *
     * @param ResourceNode $parent
     *
     * @return ResourceQueryBuilder
     */
    public function whereParentIs(ResourceNode $parent)
    {
        $this->addWhereClause('node.parent = :ar_parentId');
        $this->parameters[':ar_parentId'] = $parent->getId();

        return $this;
    }

    /**
     * Filters nodes whose path begins with a given path.
     *
     * @param string $path
     * @param bool   $includeGivenPath
     *
     * @return ResourceQueryBuilder
     */
    public function wherePathLike($path, $includeGivenPath = true)
    {
        $this->addWhereClause('node.path LIKE :pathlike');
        $this->parameters[':pathlike'] = $path.'%';

        if (!$includeGivenPath) {
            $this->addWhereClause('node.path <> :path');
            $this->parameters[':path'] = $path;
        }

        return $this;
    }

    /**
     * Filters the nodes that don't have a parent (roots).
     *
     * @return ResourceQueryBuilder
     */
    public function whereParentIsNull()
    {
        $this->addWhereClause('node.parent IS NULL');

        return $this;
    }

    public function whereMimeTypeIs($mimeType)
    {
        $this->addWhereClause('node.mimeType LIKE :mimeType');
        $this->parameters[':mimeType'] = $mimeType;

        return $this;
    }

    /**
     * Filters nodes that are published.
     *
     * @param  $user (not typing because we don't want anon. to crash everything)
     *
     * @return ResourceQueryBuilder
     */
    public function whereIsAccessible($user)
    {
        $currentDate = new \DateTime();
        $clause = '(
            creator.id = :creatorId
            OR (
                node.published = true
                AND (node.accessibleFrom IS NULL OR node.accessibleFrom <= :currentdate)
                AND (node.accessibleUntil IS NULL OR node.accessibleUntil >= :currentdate)
            )
        )';
        $this->addWhereClause($clause);
        $this->parameters[':creatorId'] = ('anon.' === $user) ? -1 : $user->getId();
        $this->parameters[':currentdate'] = $currentDate->format('Y-m-d H:i:s');

        return $this;
    }

    /**
     * Orders nodes by name.
     *
     * @return ResourceQueryBuilder
     */
    public function orderByName()
    {
        $this->orderClause = 'ORDER BY node.name'.PHP_EOL;

        return $this;
    }

    /**
     * Orders nodes by index.
     *
     * @return ResourceQueryBuilder
     */
    public function orderByIndex()
    {
        $this->orderClause = 'ORDER BY node.index'.PHP_EOL;

        return $this;
    }

    /**
     * Groups nodes by id.
     *
     * @return ResourceQueryBuilder
     */
    public function groupById()
    {
        $this->groupByClause = 'GROUP BY node.id'.PHP_EOL;

        return $this;
    }

    public function groupByResourceUserTypeAndIcon()
    {
        $this->groupByClause = '
            GROUP BY node.id,
                     node.parent,
                     previous.id,
                     next.id,
                     creator.username,
                     resourceType.name
        '.PHP_EOL;

        return $this;
    }

    /**
     * Returns the dql query string.
     *
     * @return string
     *
     * @throws MissingSelectClauseException if no select method was previously called
     */
    public function getDql()
    {
        if (null === $this->selectClause) {
            throw new MissingSelectClauseException('Select clause is missing');
        }

        $eol = PHP_EOL;
        $joinRelatives = $this->joinSingleRelatives ? $this->joinRelativesClause : '';
        if ($this->leftJoinPlugins) {
            $joinRelatives .= " LEFT JOIN node.resourceType rtp{$eol}
            LEFT JOIN rtp.plugin p{$eol}";
        }
        $joinRoles = $this->leftJoinRoles ?
            "LEFT JOIN node.workspace workspace{$eol}".
            "LEFT JOIN workspace.roles role{$eol}" :
            '';
        $joinRights = $this->leftJoinRights ?
            "LEFT JOIN node.rights rights{$eol}".
            "JOIN rights.role rightRole{$eol}" :
            '';
        $joinLogs = $this->leftJoinLogs ?
            "JOIN node.logs log{$eol}".
            "JOIN log.resourceNode log_node{$eol}" :
            '';
        $dql =
            $this->selectClause.
            $this->fromClause.
            $joinRelatives.
            $joinRoles.
            $joinRights.
            $joinLogs.
            $this->joinClause.
            $this->whereClause.
            $this->groupByClause.
            $this->orderClause;

        return $dql;
    }

    /**
     * Returns the parameters used when building the query.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Adds a statement to the query "WHERE" clause.
     *
     * @param string $clause
     */
    public function addWhereClause($clause)
    {
        if (null === $this->whereClause) {
            $this->whereClause = "WHERE {$clause}".PHP_EOL;
        } else {
            $this->whereClause = $this->whereClause."AND {$clause}".PHP_EOL;
        }
    }

    /**
     * Adds a statement to the query "JOIN" clause.
     *
     * @param string $clause
     */
    public function addJoinClause($clause)
    {
        $this->joinClause = $clause.PHP_EOL;
    }

    /**
     * Filters nodes that are bound to any of the given roles.
     *
     * @param array[string|Role] $roles
     *
     * @return \Claroline\CoreBundle\Repository\ResourceQueryBuilder
     */
    public function whereHasRoleIn(array $roles)
    {
        $managerRoles = [];
        $otherRoles = [];

        foreach ($roles as $role) {
            $roleName = $role instanceof Role ? $role->getRole() : $role;

            if (preg_match('/^ROLE_WS_MANAGER_/', $roleName)) {
                $managerRoles[] = $roleName;
            } else {
                $otherRoles[] = $roleName;
            }
        }

        $eol = PHP_EOL;

        if (count($otherRoles) > 0 && 0 === count($managerRoles)) {
            $this->leftJoinRights = true;
            $clause = "{$eol}({$eol}";
            $clause .= "rightRole.name IN (:roles){$eol}";
            $this->parameters[':roles'] = $otherRoles;
            $clause .= "AND{$eol}BIT_AND(rights.mask, 1) = 1{$eol})";
            $this->addWhereClause($clause);
        } elseif (0 === count($otherRoles) && count($managerRoles) > 0) {
            $this->leftJoinRoles = true;
            $clause = "{$eol}({$eol}";
            $clause .= "role.name IN (:roles){$eol}";
            $this->parameters[':roles'] = $managerRoles;
            $this->addWhereClause($clause.')');
        } elseif (count($otherRoles) > 0 && count($managerRoles) > 0) {
            $this->leftJoinRoles = true;
            $this->leftJoinRights = true;
            $clause = "{$eol}({$eol}({$eol}";
            $clause .= "rightRole.name IN (:otherroles){$eol}";
            $this->parameters[':otherroles'] = $otherRoles;
            $clause .= "AND{$eol}BIT_AND(rights.mask, 1) = 1{$eol}){$eol}";
            $clause .= "OR{$eol}";
            $clause .= "role.name IN (:managerroles){$eol}";
            $this->parameters[':managerroles'] = $managerRoles;
            $this->addWhereClause($clause.')');
        }

        return $this;
    }

    /**
     * Filters nodes by active value.
     *
     * @param bool $active
     *
     * @return ResourceQueryBuilder
     */
    public function whereActiveIs($active)
    {
        $this->addWhereClause('node.active = :active');
        $this->parameters[':active'] = $active;

        return $this;
    }

    /**
     * Filters nodes by ids.
     *
     * @param array $ids
     *
     * @return ResourceQueryBuilder
     */
    public function whereIdIn($ids)
    {
        $this->addWhereClause('node.id IN (:ids)');
        $this->parameters[':ids'] = $ids;

        return $this;
    }
}
