<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gaetan
 * Date: 26/06/13
 * Time: 15:52
 * To change this template use File | Settings | File Templates.
 */

namespace Icap\LessonBundle\Entity;

use Claroline\CoreBundle\Entity\Model\UuidTrait;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="icap__lesson")
 * @ORM\HasLifecycleCallbacks()
 */
class Lesson extends AbstractResource
{
    use UuidTrait;

    /**
     * @ORM\OneToOne(targetEntity="Icap\LessonBundle\Entity\Chapter", cascade={"all"})
     * @ORM\JoinColumn(name="root_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    public function __construct()
    {
        $this->refreshUuid();
    }

    /**
     * @param mixed $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @ORM\PostPersist
     */
    public function createRoot(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $rootLesson = $this->buildRoot();

        $em->getRepository('IcapLessonBundle:Chapter')->persistAsFirstChild($rootLesson);
        $em->flush();
    }

    public function buildRoot()
    {
        $rootLesson = $this->getRoot();

        if (!$rootLesson) {
            $rootLesson = new Chapter();
            $rootLesson->setLesson($this);
            $rootLesson->setTitle('root_'.$this->getId());
            $this->setRoot($rootLesson);
        }

        return $rootLesson;
    }
}
