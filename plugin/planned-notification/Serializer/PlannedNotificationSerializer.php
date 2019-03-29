<?php

namespace Claroline\PlannedNotificationBundle\Serializer;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\API\Serializer\User\RoleSerializer;
use Claroline\CoreBundle\API\Serializer\Workspace\WorkspaceSerializer;
use Claroline\CoreBundle\Entity\Role;
use Claroline\PlannedNotificationBundle\Entity\PlannedNotification;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.serializer.planned_notification")
 * @DI\Tag("claroline.serializer")
 */
class PlannedNotificationSerializer
{
    use SerializerTrait;

    private $messageRepo;
    private $roleRepo;
    private $workspaceRepo;

    /**
     * PlannedNotificationSerializer constructor.
     *
     * @DI\InjectParams({
     *     "om"                  = @DI\Inject("claroline.persistence.object_manager"),
     *     "workspaceSerializer" = @DI\Inject("claroline.serializer.workspace"),
     *     "roleSerializer"      = @DI\Inject("claroline.serializer.role"),
     *     "messageSerializer"   = @DI\Inject("claroline.serializer.planned_notification.message")
     * })
     *
     * @param ObjectManager $om
     */
    public function __construct(
        ObjectManager $om,
        WorkspaceSerializer $workspaceSerializer,
        RoleSerializer $roleSerializer,
        MessageSerializer $messageSerializer
    ) {
        $this->messageRepo = $om->getRepository('Claroline\PlannedNotificationBundle\Entity\Message');
        $this->roleRepo = $om->getRepository('Claroline\CoreBundle\Entity\Role');
        $this->workspaceRepo = $om->getRepository('Claroline\CoreBundle\Entity\Workspace\Workspace');

        $this->workspaceSerializer = $workspaceSerializer;
        $this->roleSerializer = $roleSerializer;
        $this->messageSerializer = $messageSerializer;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return '#/plugin/planned-notification/planned-notification.json';
    }

    /**
     * @param PlannedNotification $notification
     *
     * @return array
     */
    public function serialize(PlannedNotification $notification, array $options)
    {
        return [
            'id' => $notification->getUuid(),
            'message' => $this->messageSerializer->serialize($notification->getMessage()),
            'workspace' => $this->workspaceSerializer->serialize($notification->getWorkspace(), [Options::SERIALIZE_MINIMAL]),
            'parameters' => [
                'action' => $notification->getAction(),
                'interval' => $notification->getInterval(),
                'byMail' => $notification->isByMail(),
                'byMessage' => $notification->isByMessage(),
            ],
            'roles' => array_map(function (Role $role) {
                return $this->roleSerializer->serialize($role, [Options::SERIALIZE_MINIMAL]);
            }, $notification->getRoles()->toArray()),
        ];
    }

    /**
     * @param array               $data
     * @param PlannedNotification $notification
     *
     * @return PlannedNotification
     */
    public function deserialize($data, PlannedNotification $notification, array $options)
    {
        if (!in_array(Options::GENERATE_UUID, $options)) {
            $notification->setUuid($data['id']);
        }

        $this->sipe('parameters.action', 'setAction', $data, $notification);
        $this->sipe('parameters.interval', 'setInterval', $data, $notification);
        $this->sipe('parameters.byMail', 'setByMail', $data, $notification);
        $this->sipe('parameters.byMessage', 'setByMessage', $data, $notification);

        if (isset($data['message']['id'])) {
            $message = $this->messageRepo->findOneBy(['uuid' => $data['message']['id']]);

            if (!empty($message)) {
                $notification->setMessage($message);
            }
        }
        if (isset($data['workspace']['uuid'])) {
            $workspace = $this->workspaceRepo->findOneBy(['uuid' => $data['workspace']['uuid']]);

            if (!empty($workspace)) {
                $notification->setWorkspace($workspace);
            }
        }
        $notification->emptyRoles();

        if (isset($data['roles'])) {
            foreach ($data['roles'] as $roleData) {
                $role = $this->roleRepo->findOneBy(['uuid' => $roleData['id']]);

                if (!empty($role)) {
                    $notification->addRole($role);
                }
            }
        }

        return $notification;
    }
}
