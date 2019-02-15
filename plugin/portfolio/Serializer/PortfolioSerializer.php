<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Serializer;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\User;
use Claroline\PortfolioBundle\Entity\Portfolio;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.serializer.portfolio")
 * @DI\Tag("claroline.serializer")
 */
class PortfolioSerializer
{
    use SerializerTrait;

    /** @var SerializerProvider */
    private $serializer;

    private $groupRepo;
    private $userRepo;

    /**
     * PortfolioSerializer constructor.
     *
     * @DI\InjectParams({
     *     "om"         = @DI\Inject("claroline.persistence.object_manager"),
     *     "serializer" = @DI\Inject("claroline.api.serializer")
     * })
     *
     * @param ObjectManager      $om
     * @param SerializerProvider $serializer
     */
    public function __construct(ObjectManager $om, SerializerProvider $serializer)
    {
        $this->serializer = $serializer;

        $this->groupRepo = $om->getRepository(Group::class);
        $this->userRepo = $om->getRepository(User::class);
    }

    /**
     * @param Portfolio $portfolio
     * @param array     $options
     *
     * @return array
     */
    public function serialize(Portfolio $portfolio, array $options = [])
    {
        $serialized = [
            'id' => $portfolio->getUuid(),
            'title' => $portfolio->getTitle(),
            'meta' => [
                'slug' => $portfolio->getSlug(),
                'visibility' => $portfolio->getVisibility(),
                'owner' => $this->serializer->serialize($portfolio->getOwner(), [Options::SERIALIZE_MINIMAL]),
            ],
        ];

        if (!in_array(Options::SERIALIZE_MINIMAL, $options)) {
            $serialized['meta']['users'] = array_map(function (User $user) use ($options) {
                return $this->serializer->serialize($user, array_merge($options, [Options::SERIALIZE_MINIMAL]));
            }, $portfolio->getUsers()->toArray());
            $serialized['meta']['groups'] = array_map(function (Group $group) use ($options) {
                return $this->serializer->serialize($group, array_merge($options, [Options::SERIALIZE_MINIMAL]));
            }, $portfolio->getGroups()->toArray());
        }

        return $serialized;
    }

    /**
     * @param array     $data
     * @param Portfolio $portfolio
     *
     * @return Portfolio
     */
    public function deserialize($data, Portfolio $portfolio)
    {
        $this->sipe('id', 'setUuid', $data, $portfolio);
        $this->sipe('title', 'setTitle', $data, $portfolio);
        $this->sipe('meta.slug', 'setSlug', $data, $portfolio);
        $this->sipe('meta.visibility', 'setVisibility', $data, $portfolio);

        if (isset($data['meta']['owner']['id']) && !$portfolio->getOwner()) {
            $owner = $this->userRepo->findOneBy(['uuid' => $data['meta']['owner']['id']]);
            $portfolio->setOwner($owner);
        }
        $portfolio->emptyUsers();
        $portfolio->emptyGroups();

        if (isset($data['meta']['users'])) {
            foreach ($data['meta']['users'] as $userData) {
                $user = $this->userRepo->findOneBy(['uuid' => $userData['id']]);

                if (!empty($user)) {
                    $portfolio->addUser($user);
                }
            }
        }
        if (isset($data['meta']['groups'])) {
            foreach ($data['meta']['groups'] as $groupData) {
                $group = $this->groupRepo->findOneBy(['uuid' => $groupData['id']]);

                if (!empty($group)) {
                    $portfolio->addGroup($group);
                }
            }
        }

        return $portfolio;
    }
}
