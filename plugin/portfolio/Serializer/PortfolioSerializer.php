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

        return $portfolio;
    }
}
