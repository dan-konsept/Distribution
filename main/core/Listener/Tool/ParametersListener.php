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

use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Tool\Tool;
use Claroline\CoreBundle\Event\Tool\OpenToolEvent;
use Claroline\CoreBundle\Manager\Tool\ToolManager;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ParametersListener
{
    /** @var FinderProvider */
    private $finder;

    /** @var ObjectManager */
    private $om;

    /** @var SerializerProvider */
    private $serializer;

    /** @var TwigEngine */
    private $templating;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var ToolManager */
    private $toolManager;

    /**
     * ToolListener constructor.
     *
     * @param FinderProvider        $finder
     * @param ObjectManager         $om
     * @param SerializerProvider    $serializer
     * @param TwigEngine            $templating
     * @param TokenStorageInterface $tokenStorage
     * @param ToolManager           $toolManager
     */
    public function __construct(
        FinderProvider $finder,
        ObjectManager $om,
        SerializerProvider $serializer,
        TwigEngine $templating,
        TokenStorageInterface $tokenStorage,
        ToolManager $toolManager
    ) {
        $this->finder = $finder;
        $this->om = $om;
        $this->serializer = $serializer;
        $this->templating = $templating;
        $this->tokenStorage = $tokenStorage;
        $this->toolManager = $toolManager;
    }

    /**
     * @param OpenToolEvent $event
     */
    public function onDisplayDesktopParameters(OpenToolEvent $event)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ('anon.' === $user) {
            throw new AccessDeniedException();
        }
        $toolsRolesConfig = $this->toolManager->getUserDesktopToolsConfiguration($user);
        /** @var Tool[] $desktopTools */
        $desktopTools = $this->finder->get(Tool::class)->find(
            ['isDisplayableInDesktop' => true],
            ['property' => 'name', 'direction' => 1]
        );

        $orderedTools = $this->toolManager->computeUserOrderedTools($user, $toolsRolesConfig);
        $toolsConfig = [];

        foreach ($orderedTools as $orderedTool) {
            $toolName = $orderedTool->getTool()->getName();
            $toolsConfig[$toolName] = [
                'visible' => $orderedTool->isVisibleInDesktop(),
                'locked' => $orderedTool->isLocked(),
            ];
        }

        $event->setData([
            'tools' => array_map(function (Tool $tool) {
                return $this->serializer->serialize($tool);
            }, $desktopTools),
            'toolsConfig' => 0 < count($toolsConfig) ? $toolsConfig : new \stdClass(),
        ]);
    }
}
