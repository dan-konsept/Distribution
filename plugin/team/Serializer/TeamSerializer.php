<?php

namespace Claroline\TeamBundle\Serializer;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\API\Serializer\Resource\ResourceNodeSerializer;
use Claroline\CoreBundle\API\Serializer\User\RoleSerializer;
use Claroline\CoreBundle\API\Serializer\Workspace\WorkspaceSerializer;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Repository\ResourceNodeRepository;
use Claroline\CoreBundle\Repository\WorkspaceRepository;
use Claroline\TeamBundle\Entity\Team;
use Claroline\TeamBundle\Manager\TeamManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class TeamSerializer
{
    use SerializerTrait;

    /** @var ObjectManager */
    private $om;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var TeamManager */
    private $teamManager;
    /** @var TokenStorage */
    private $tokenStorage;

    /** @var ResourceNodeSerializer */
    private $resourceNodeSerializer;
    /** @var RoleSerializer */
    private $roleSerializer;
    /** @var WorkspaceSerializer */
    private $workspaceSerializer;

    /** @var ResourceNodeRepository */
    private $resourceNodeRepo;
    /** @var WorkspaceRepository */
    private $workspaceRepo;

    /**
     * TeamSerializer constructor.
     *
     * @param ObjectManager          $om
     * @param ResourceManager        $resourceManager
     * @param TeamManager            $teamManager
     * @param TokenStorage           $tokenStorage
     * @param ResourceNodeSerializer $resourceNodeSerializer
     * @param RoleSerializer         $roleSerializer
     * @param WorkspaceSerializer    $workspaceSerializer
     */
    public function __construct(
        ObjectManager $om,
        ResourceManager $resourceManager,
        TeamManager $teamManager,
        TokenStorage $tokenStorage,
        ResourceNodeSerializer $resourceNodeSerializer,
        RoleSerializer $roleSerializer,
        WorkspaceSerializer $workspaceSerializer
    ) {
        $this->om = $om;
        $this->resourceManager = $resourceManager;
        $this->teamManager = $teamManager;
        $this->tokenStorage = $tokenStorage;
        $this->resourceNodeSerializer = $resourceNodeSerializer;
        $this->roleSerializer = $roleSerializer;
        $this->workspaceSerializer = $workspaceSerializer;

        $this->resourceNodeRepo = $om->getRepository('Claroline\CoreBundle\Entity\Resource\ResourceNode');
        $this->workspaceRepo = $om->getRepository('Claroline\CoreBundle\Entity\Workspace\Workspace');
    }

    public function getName()
    {
        return 'team';
    }

    /**
     * @param Team $team
     *
     * @return array
     */
    public function serialize(Team $team)
    {
        $users = $team->getRole() ? $team->getRole()->getUsers()->toArray() : $team->getUsers()->toArray();

        return [
            'id' => $team->getUuid(),
            'name' => $team->getName(),
            'description' => $team->getDescription(),
            'workspace' => $this->workspaceSerializer->serialize($team->getWorkspace(), [Options::SERIALIZE_MINIMAL]),
            'maxUsers' => $team->getMaxUsers(),
            'countUsers' => count($users),
            'selfRegistration' => $team->isSelfRegistration(),
            'selfUnregistration' => $team->isSelfUnregistration(),
            'directory' => $team->getDirectory() ?
                $this->resourceNodeSerializer->serialize($team->getDirectory()->getResourceNode(), [Options::SERIALIZE_MINIMAL]) :
                null,
            'publicDirectory' => $team->isPublic(),
            'deletableDirectory' => $team->isDirDeletable(),
            'role' => $team->getRole() ?
                $this->roleSerializer->serialize($team->getRole(), [Options::SERIALIZE_MINIMAL]) :
                null,
            'teamManagerRole' => $team->getTeamManagerRole() ?
                $this->roleSerializer->serialize($team->getTeamManagerRole(), [Options::SERIALIZE_MINIMAL]) :
                null,
        ];
    }

    /**
     * @param array $data
     * @param Team  $team
     *
     * @return Team
     */
    public function deserialize($data, Team $team, array $options = [])
    {
        // TODO : rewrite. persist/flush are not allowed in serializers
        $this->om->startFlushSuite();

        if (!in_array(Options::REFRESH_UUID, $options)) {
            $team->setUuid($data['id']);
        }

        $this->sipe('name', 'setName', $data, $team);
        $this->sipe('description', 'setDescription', $data, $team);
        $this->sipe('selfRegistration', 'setSelfRegistration', $data, $team);
        $this->sipe('selfUnregistration', 'setSelfUnregistration', $data, $team);
        $this->sipe('publicDirectory', 'setIsPublic', $data, $team);
        $this->sipe('deletableDirectory', 'setDirDeletable', $data, $team);

        if (isset($data['workspace']['id'])) {
            /** @var Workspace $workspace */
            $workspace = $this->workspaceRepo->findOneBy(['uuid' => $data['workspace']['id']]);

            if ($workspace) {
                $team->setWorkspace($workspace);
            }
        }

        // Checks and creates role for team members & team manager if needed.
        $teamRole = $team->getRole();
        $teamManagerRole = $team->getTeamManagerRole();

        if (empty($teamRole)) {
            $teamRole = $this->teamManager->createTeamRole($team);
            $team->setRole($teamRole);
        }
        $maxUsers = !empty($data['maxUsers']) ? $data['maxUsers'] : null;
        $team->setMaxUsers($maxUsers);
        $teamRole->setMaxUsers($maxUsers);
        $this->om->persist($teamRole);

        if (empty($teamManagerRole)) {
            $teamManagerRole = $this->teamManager->createTeamRole($team, true);
            $team->setTeamManagerRole($teamManagerRole);
        }

        // Checks and creates team directory
        $directory = $team->getDirectory();
        $user = $this->tokenStorage->getToken()->getUser();

        if (empty($directory) && 'anon.' !== $user) {
            if (isset($data['createPublicDirectory']) && $data['createPublicDirectory']) {
                $defaultResource = isset($data['defaultResource']['id']) ?
                  $this->resourceNodeRepo->findOneBy(['uuid' => $data['defaultResource']['id']]) :
                  null;
                $creatableResources = isset($data['creatableResources']) ?
                  $data['creatableResources'] :
                  [];
                $directory = $this->teamManager->createTeamDirectory(
                  $team,
                  $user,
                  $defaultResource,
                  $creatableResources
              );
                $team->setDirectory($directory);
                $this->teamManager->initializeTeamRights($team);
            }
        } elseif ('anon.' !== $user) {
            $this->teamManager->updateTeamDirectoryPerms($team);
        }

        $this->om->persist($team);
        $this->om->endFlushSuite();

        return $team;
    }
}
