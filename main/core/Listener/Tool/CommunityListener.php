<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener\Tool;

use Claroline\CoreBundle\API\Serializer\ParametersSerializer;
use Claroline\CoreBundle\API\Serializer\User\ProfileSerializer;
use Claroline\CoreBundle\Event\Tool\OpenToolEvent;
use Claroline\CoreBundle\Manager\UserManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommunityListener
{
    /** @var AuthorizationCheckerInterface */
    private $authorization;
    /** @var ParametersSerializer */
    private $parametersSerializer;
    /** @var ProfileSerializer */
    private $profileSerializer;
    /** @var UserManager */
    private $userManager;

    /**
     * CommunityListener constructor.
     *
     * @param AuthorizationCheckerInterface $authorization
     * @param ParametersSerializer          $parametersSerializer
     * @param ProfileSerializer             $profileSerializer
     * @param UserManager                   $userManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorization,
        ParametersSerializer $parametersSerializer,
        ProfileSerializer $profileSerializer,
        UserManager $userManager
    ) {
        $this->authorization = $authorization;
        $this->parametersSerializer = $parametersSerializer;
        $this->profileSerializer = $profileSerializer;
        $this->userManager = $userManager;
    }

    /**
     * Displays users on Workspace.
     *
     * @param OpenToolEvent $event
     */
    public function onDisplayWorkspace(OpenToolEvent $event)
    {
        $event->setData([
            'profile' => $this->profileSerializer->serialize(),
            'parameters' => $this->parametersSerializer->serialize()['profile'],
            'restrictions' => [
                // TODO: computes rights more accurately
                'hasUserManagementAccess' => $this->authorization->isGranted('ROLE_ADMIN'),
            ],
            'usersLimitReached' => $this->userManager->hasReachedLimit(),
        ]);

        $event->stopPropagation();
    }

    /**
     * @param OpenToolEvent $event
     */
    public function onDisplayDesktop(OpenToolEvent $event)
    {
        $event->setData([
            'profile' => $this->profileSerializer->serialize(),
            'parameters' => $this->parametersSerializer->serialize()['profile'],
            'restrictions' => [],
            'usersLimitReached' => $this->userManager->hasReachedLimit(),
        ]);

        $event->stopPropagation();
    }
}
