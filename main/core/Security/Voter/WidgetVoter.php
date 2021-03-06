<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Security\Voter;

use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This voter is involved in access decisions for WidgetInstances.
 */
class WidgetVoter implements VoterInterface
{
    private $em;
    private $translator;
    private $wm;

    public function __construct(
        EntityManager $em,
        TranslatorInterface $translator,
        WorkspaceManager $wm
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->wm = $wm;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($object instanceof WidgetInstance) {
            return $this->canUpdate($token, $object, $attributes);
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    private function canUpdate(TokenInterface $token, $object, $attributes)
    {
        $roles = $token->getRoles();

        foreach ($roles as $role) {
            $roleStrings[] = $role->getRole();
        }

        if ($object->isAdmin()) {
            $grantedAdminTools = $this->em->getRepository('ClarolineCoreBundle:Tool\AdminTool')->findByRoles($roles);
            $allowedTools = [];

            foreach ($grantedAdminTools as $grantedAdminTool) {
                $allowedTools[] = $grantedAdminTool->getName();
            }
            if (!in_array('home', $allowedTools)) {
                return VoterInterface::ACCESS_DENIED;
            }

            return VoterInterface::ACCESS_GRANTED;
        } else {
            if ($workspace = $object->getWorkspace()) {
                //if manager: always granted
                if (in_array('ROLE_WS_MANAGER_'.$workspace->getGuid(), $roleStrings)) {
                    return VoterInterface::ACCESS_GRANTED;
                }
                //if we have access to the parameters, always granted aswell
                $tools = $this->em
                    ->getRepository('ClarolineCoreBundle:Tool\Tool')
                    ->findDisplayedByRolesAndWorkspace($roleStrings, $workspace);

                foreach ($tools as $tool) {
                    if ('parameters' === $tool->getName()) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                }

                //else we need to check the masks (c/c from WorkspaceVoter)
                $accesses = $this->wm->getAccesses($token, [$workspace], 'home', 'edit');

                return isset($accesses[$workspace->getId()]) && true === $accesses[$workspace->getId()] ?
                    VoterInterface::ACCESS_GRANTED :
                    VoterInterface::ACCESS_DENIED;
            }

            if ($user = $object->getUser()) {
                if ($user->getId() === $token->getUser()->getId()) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }

            return VoterInterface::ACCESS_DENIED;
        }
    }

    public function supportsAttribute($attribute)
    {
        return true;
    }

    public function supportsClass($class)
    {
        return true;
    }
}
