<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Controller;

use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Tab\HomeTab;
use Claroline\CoreBundle\Entity\Tab\HomeTabConfig;
use Claroline\CoreBundle\Entity\User;
use Claroline\PortfolioBundle\Entity\Portfolio;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @EXT\Route("/portfolio")
 */
class PortfolioController
{
    /** @var FinderProvider */
    private $finder;
    /** @var ObjectManager */
    private $om;
    /** @var SerializerProvider */
    private $serializer;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * PortfolioController constructor.
     *
     * @DI\InjectParams({
     *     "finder"        = @DI\Inject("claroline.api.finder"),
     *     "om"            = @DI\Inject("claroline.persistence.object_manager"),
     *     "serializer"    = @DI\Inject("claroline.api.serializer"),
     *     "translator"    = @DI\Inject("translator")
     * })
     *
     * @param FinderProvider      $finder
     * @param ObjectManager       $om
     * @param SerializerProvider  $serializer
     * @param TranslatorInterface $translator
     */
    public function __construct(
        FinderProvider $finder,
        ObjectManager $om,
        SerializerProvider $serializer,
        TranslatorInterface $translator
    ) {
        $this->finder = $finder;
        $this->om = $om;
        $this->serializer = $serializer;
        $this->translator = $translator;
    }

    /**
     * @EXT\Route(
     *     "/{id}",
     *     name="claro_portfolio_open"
     * )
     * @EXT\ParamConverter(
     *     "portfolio",
     *     class="ClarolinePortfolioBundle:Portfolio",
     *     options={"mapping": {"id": "uuid"}}
     * )
     * @EXT\ParamConverter("user", converter="current_user", options={"allowAnonymous"=true})
     * @EXT\Method("GET")
     * @EXT\Template("ClarolinePortfolioBundle:portfolio:portfolio.html.twig")
     *
     * @param Portfolio $portfolio
     * @param User|null $user
     *
     * @return array
     */
    public function openAction(Portfolio $portfolio, User $user = null)
    {
        $tabs = $this->finder->search(
            HomeTab::class,
            [
                'filters' => [
                    'type' => 'portfolio',
                    'contextId' => $portfolio->getUuid(),
                ]
            ]
        );

        $tabs = array_filter($tabs['data'], function ($data) {
            return $data !== [];
        });
        $orderedTabs = [];

        foreach ($tabs as $tab) {
            $orderedTabs[$tab['position']] = $tab;
        }
        ksort($orderedTabs);

        if (0 === count($orderedTabs)) {
            $defaultTab = new HomeTab();
            $defaultTab->setType('portfolio');
            $defaultTab->setContextId($portfolio->getUuid());
            $this->om->persist($defaultTab);
            $defaultHomeTabConfig = new HomeTabConfig();
            $defaultHomeTabConfig->setHomeTab($defaultTab);
            $defaultHomeTabConfig->setName($this->translator->trans('home', [], 'platform'));
            $defaultHomeTabConfig->setLongTitle($this->translator->trans('home', [], 'platform'));
            $defaultHomeTabConfig->setLocked(true);
            $defaultHomeTabConfig->setTabOrder(0);
            $this->om->persist($defaultHomeTabConfig);
            $this->om->flush();
            $orderedTabs[] = $this->serializer->serialize($defaultTab);
        }

        return [
            'editable' => $user && $user->getUuid() === $portfolio->getOwner()->getUuid(),
            'administration' => false,
            'context' => [
                'type' => 'portfolio',
                'data' => $this->serializer->serialize($portfolio, [Options::SERIALIZE_MINIMAL]),
            ],
            'tabs' => array_values($orderedTabs),
        ];
    }
}
