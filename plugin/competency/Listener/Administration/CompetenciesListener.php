<?php

namespace HeVinci\CompetencyBundle\Listener\Administration;

use Claroline\CoreBundle\Event\Tool\OpenToolEvent;
use HeVinci\CompetencyBundle\Manager\CompetencyManager;

/**
 * Defines the listening methods for all the core extension
 * points used in this plugin (tools and widgets).
 */
class CompetenciesListener
{
    /** @var CompetencyManager */
    private $competencyManager;

    /**
     * CompetenciesListener constructor.
     *
     * @param CompetencyManager $competencyManager
     */
    public function __construct(CompetencyManager $competencyManager)
    {
        $this->competencyManager = $competencyManager;
    }

    /**
     * @param OpenToolEvent $event
     */
    public function onDisplayTool(OpenToolEvent $event)
    {
        $this->competencyManager->ensureHasScale();
        $event->setData([]);
        $event->stopPropagation();
    }
}
