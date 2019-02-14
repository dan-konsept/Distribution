<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Listener;

use Claroline\CoreBundle\Event\DisplayToolEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bundle\TwigBundle\TwigEngine;

/**
 * @DI\Service
 */
class PortfolioListener
{
    /** @var TwigEngine */
    private $templating;

    /**
     * @DI\InjectParams({
     *     "templating" = @DI\Inject("templating")
     * })
     *
     * @param TwigEngine $templating
     */
    public function __construct(TwigEngine $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @DI\Observe("open_tool_desktop_portfolio")
     *
     * @param DisplayToolEvent $event
     */
    public function onDesktopToolOpen(DisplayToolEvent $event)
    {
        $content = $this->templating->render(
            'ClarolinePortfolioBundle:tool:portfolio.html.twig'
        );
        $event->setContent($content);
        $event->stopPropagation();
    }
}
