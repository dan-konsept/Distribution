<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeVinci\CompetencyBundle\Controller\API;

use Claroline\AppBundle\Controller\AbstractCrudController;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Library\Security\Collection\ResourceCollection;
use HeVinci\CompetencyBundle\Entity\Ability;
use HeVinci\CompetencyBundle\Manager\CompetencyManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @EXT\Route("/ability")
 */
class AbilityController extends AbstractCrudController
{
    /** @var AuthorizationCheckerInterface */
    protected $authorization;

    /** @var CompetencyManager */
    private $manager;

    /**
     * @param AuthorizationCheckerInterface $authorization
     * @param CompetencyManager             $manager
     */
    public function __construct(AuthorizationCheckerInterface $authorization, CompetencyManager $manager)
    {
        $this->authorization = $authorization;
        $this->manager = $manager;
    }

    public function getName()
    {
        return 'ability';
    }

    public function getClass()
    {
        return Ability::class;
    }

    public function getIgnore()
    {
        return ['create', 'update', 'deleteBulk', 'get', 'exist', 'copyBulk', 'schema', 'find'];
    }

    /**
     * @EXT\Route(
     *     "/node/{node}/abilities/fetch",
     *     name="apiv2_competency_resource_abilities_list"
     * )
     * @EXT\ParamConverter(
     *     "node",
     *     class="ClarolineCoreBundle:Resource\ResourceNode",
     *     options={"mapping": {"node": "uuid"}}
     * )
     *
     * @param ResourceNode $node
     *
     * @return JsonResponse
     */
    public function resourceAbilitiesFetchAction(ResourceNode $node)
    {
        $abilities = $this->finder->fetch(
            Ability::class,
            ['resources' => [$node->getUuid()]]
        );
        $serialized = array_map(function (Ability $ability) {
            return $this->serializer->serialize($ability);
        }, $abilities);

        return new JsonResponse($serialized);
    }

    /**
     * @EXT\Route(
     *     "/node/{node}/ability/{ability}/associate",
     *     name="apiv2_competency_resource_ability_associate"
     * )
     * @EXT\ParamConverter(
     *     "node",
     *     class="ClarolineCoreBundle:Resource\ResourceNode",
     *     options={"mapping": {"node": "uuid"}}
     * )
     * @EXT\ParamConverter(
     *     "ability",
     *     class="HeVinciCompetencyBundle:Ability",
     *     options={"mapping": {"ability": "uuid"}}
     * )
     * @EXT\Method("POST")
     *
     * @param ResourceNode $node
     * @param Ability      $ability
     *
     * @return JsonResponse
     */
    public function resourceAbilityAssociateAction(ResourceNode $node, Ability $ability)
    {
        $this->checkResourceAccess($node, 'EDIT');

        $associatedNodes = $this->manager->associateAbilityToResources($ability, [$node]);
        $data = 0 < count($associatedNodes) ?
            $this->serializer->serialize($ability) :
            null;

        return new JsonResponse($data);
    }

    /**
     * @EXT\Route(
     *     "/node/{node}/ability/{ability}/dissociate",
     *     name="apiv2_competency_resource_ability_dissociate"
     * )
     * @EXT\ParamConverter(
     *     "node",
     *     class="ClarolineCoreBundle:Resource\ResourceNode",
     *     options={"mapping": {"node": "uuid"}}
     * )
     * @EXT\ParamConverter(
     *     "ability",
     *     class="HeVinciCompetencyBundle:Ability",
     *     options={"mapping": {"ability": "uuid"}}
     * )
     * @EXT\Method("DELETE")
     *
     * @param ResourceNode $node
     * @param Ability      $ability
     *
     * @return JsonResponse
     */
    public function resourceAbilityDissociateAction(ResourceNode $node, Ability $ability)
    {
        $this->checkResourceAccess($node, 'EDIT');

        $this->manager->dissociateAbilityFromResources($ability, [$node]);

        return new JsonResponse();
    }

    /**
     * @param ResourceNode $node
     * @param string       $rights
     */
    private function checkResourceAccess(ResourceNode $node, $rights = 'OPEN')
    {
        $collection = new ResourceCollection([$node]);

        if (!$this->authorization->isGranted($rights, $collection)) {
            throw new AccessDeniedException();
        }
    }
}
