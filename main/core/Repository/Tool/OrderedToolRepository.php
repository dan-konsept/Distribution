<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository\Tool;

use Claroline\CoreBundle\Entity\Tool\OrderedTool;
use Claroline\CoreBundle\Entity\Tool\Tool;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Manager\PluginManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class OrderedToolRepository extends ServiceEntityRepository
{
    /** @var array */
    private $bundles;

    public function __construct(RegistryInterface $registry, PluginManager $manager)
    {
        $this->bundles = $manager->getEnabled(true);

        parent::__construct($registry, OrderedTool::class);
    }

    public function findOneByNameAndWorkspace($name, Workspace $workspace = null)
    {
        $query = $this->_em->createQuery('
            SELECT ot
                FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
                JOIN ot.tool t
                WHERE ot.workspace = :workspace
                AND t.name = :name
                ORDER BY ot.order
        ');
        $query->setParameter('workspace', $workspace);
        $query->setParameter('name', $name);

        return $query->getOneOrNullResult();
    }

    /**
     * Returns the workspace ordered tools accessible to some given roles.
     *
     * @param Workspace $workspace
     * @param array     $roles
     * @param int       $type
     *
     * @return OrderedTool[]
     */
    public function findByWorkspaceAndRoles(
        Workspace $workspace,
        array $roles,
        $type = 0
    ) {
        if (0 === count($roles)) {
            return [];
        }

        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            JOIN ot.rights r
            JOIN r.role rr
            WHERE ot.workspace = :workspace
            AND ot.type = :type
            AND rr.name IN (:roleNames)
            AND BIT_AND(r.mask, 1) = 1
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspace', $workspace);
        $query->setParameter('roleNames', $roles);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $query->getResult();
    }

    public function incWorkspaceOrderedToolOrderForRange(
        Workspace $workspace,
        $fromOrder,
        $toOrder,
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            UPDATE Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            SET ot.order = ot.order + 1
            WHERE ot.workspace = :workspace
            AND ot.type = :type
            AND ot.order >= :fromOrder
            AND ot.order < :toOrder
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspace', $workspace);
        $query->setParameter('fromOrder', $fromOrder);
        $query->setParameter('toOrder', $toOrder);
        $query->setParameter('type', $type);

        return $executeQuery ? $query->execute() : $query;
    }

    public function decWorkspaceOrderedToolOrderForRange(
        Workspace $workspace,
        $fromOrder,
        $toOrder,
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            UPDATE Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            SET ot.order = ot.order - 1
            WHERE ot.workspace = :workspace
            AND ot.type = :type
            AND ot.order > :fromOrder
            AND ot.order <= :toOrder
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspace', $workspace);
        $query->setParameter('fromOrder', $fromOrder);
        $query->setParameter('toOrder', $toOrder);
        $query->setParameter('type', $type);

        return $executeQuery ? $query->execute() : $query;
    }

    /**
     * @param User $user
     * @param int  $type
     * @param bool $executeQuery
     *
     * @return \Doctrine\ORM\Query|OrderedTool[]
     */
    public function findDisplayableDesktopOrderedToolsByUser(
        User $user,
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.workspace IS NULL
            AND ot.type = :type
            AND ot.user = :user
            AND t.isDisplayableInDesktop = true
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('user', $user);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findConfigurableDesktopOrderedToolsByUser(
        User $user,
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.workspace IS NULL
            AND ot.type = :type
            AND ot.user = :user
            AND t.isDisplayableInDesktop = true
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('user', $user);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $executeQuery ? $query->getResult() : $query;
    }

    /**
     * @param int  $type
     * @param bool $executeQuery
     *
     * @return \Doctrine\ORM\Query|OrderedTool[]
     */
    public function findDisplayableDesktopOrderedToolsByTypeForAdmin(
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.workspace IS NULL
            AND ot.user IS NULL
            AND ot.type = :type
            AND t.isDisplayableInDesktop = true
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findConfigurableDesktopOrderedToolsByTypeForAdmin(
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.workspace IS NULL
            AND ot.user IS NULL
            AND ot.type = :type
            AND t.isDisplayableInDesktop = true
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findLockedConfigurableDesktopOrderedToolsByTypeForAdmin(
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.workspace IS NULL
            AND ot.user IS NULL
            AND ot.type = :type
            AND ot.locked = true
            AND t.isDisplayableInDesktop = true
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findOrderedToolsByToolAndUser(
        Tool $tool,
        User $user,
        $type = 0,
        $executeQuery = true
    ) {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.tool = :tool
            AND ot.user = :user
            AND ot.workspace IS NULL
            AND ot.type = :type
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('tool', $tool);
        $query->setParameter('user', $user);
        $query->setParameter('type', $type);
        $query->setParameter('bundles', $this->bundles);

        return $executeQuery ? $query->getResult() : $query;
    }

    /**
     * Returns the ordered tools locked by admin.

     * @param int $orderedToolType
     *
     * @return OrderedTool[]
     */
    public function findOrderedToolsLockedByAdmin($orderedToolType = 0)
    {
        $dql = '
            SELECT ot
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot
            JOIN ot.tool t
            LEFT JOIN t.plugin p
            WHERE ot.user IS NULL
            AND ot.workspace IS NULL
            AND ot.type = :type
            AND ot.locked = true
            AND t.isDisplayableInDesktop = true
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot.order
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('type', $orderedToolType);
        $query->setParameter('bundles', $this->bundles);

        return $query->getResult();
    }

    public function findDuplicatedOldOrderedToolsByUsers()
    {
        $dql = '
            SELECT ot1
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot1
            JOIN ot1.tool t
            LEFT JOIN t.plugin p
            WHERE ot1.user IS NOT NULL
            AND EXISTS (
                SELECT ot2
                FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot2
                WHERE ot1.tool = ot2.tool
                AND ot1.user = ot2.user
            )
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot1.id
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('bundles', $this->bundles);

        return $query->getResult();
    }

    public function findDuplicatedOldOrderedToolsByWorkspaces()
    {
        $dql = '
            SELECT ot1
            FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot1
            JOIN ot1.tool t
            LEFT JOIN t.plugin p
            WHERE ot1.workspace IS NOT NULL
            AND EXISTS (
                SELECT ot2
                FROM Claroline\CoreBundle\Entity\Tool\OrderedTool ot2
                WHERE ot1.tool = ot2.tool
                AND ot1.workspace = ot2.workspace
            )
            AND (
                CONCAT(p.vendorName, p.bundleName) IN (:bundles)
                OR t.plugin is NULL
            )
            ORDER BY ot1.id
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('bundles', $this->bundles);

        return $query->getResult();
    }
}
