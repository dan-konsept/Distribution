<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeVinci\CompetencyBundle\Serializer;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use HeVinci\CompetencyBundle\Entity\Ability;
use HeVinci\CompetencyBundle\Entity\Competency;
use HeVinci\CompetencyBundle\Entity\CompetencyAbility;
use HeVinci\CompetencyBundle\Entity\Level;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.serializer.competency_ability")
 * @DI\Tag("claroline.serializer")
 */
class CompetencyAbilitySerializer
{
    use SerializerTrait;

    /** @var ObjectManager */
    private $om;
    private $abilityRepo;
    private $competencyRepo;
    private $levelRepo;

    /**
     * CompetencyAbilitySerializer constructor.
     *
     * @DI\InjectParams({
     *     "om"                = @DI\Inject("claroline.persistence.object_manager"),
     *     "abilitySerializer" = @DI\Inject("claroline.serializer.ability"),
     *     "levelSerializer"   = @DI\Inject("claroline.serializer.competency.scale.level")
     * })
     *
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om, AbilitySerializer $abilitySerializer, LevelSerializer $levelSerializer)
    {
        $this->om = $om;
        $this->abilitySerializer = $abilitySerializer;
        $this->levelSerializer = $levelSerializer;

        $this->abilityRepo = $om->getRepository(Ability::class);
        $this->competencyRepo = $om->getRepository(Competency::class);
        $this->levelRepo = $om->getRepository(Level::class);
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return '#/plugin/competency/competency_ability.json';
    }

    /**
     * @param CompetencyAbility $competencyAbility
     * @param array             $options
     *
     * @return array
     */
    public function serialize(CompetencyAbility $competencyAbility, array $options = [])
    {
        $serialized = [
            'id' => $competencyAbility->getUuid(),
            'level' => $this->levelSerializer->serialize($competencyAbility->getLevel(), [Options::SERIALIZE_MINIMAL]),
            'competency' => [
                'id' => $competencyAbility->getCompetency()->getUuid(),
            ],
            'ability' => $this->abilitySerializer->serialize($competencyAbility->getAbility(), [Options::SERIALIZE_MINIMAL]),
        ];

        return $serialized;
    }

    /**
     * @param array             $data
     * @param CompetencyAbility $competencyAbility
     *
     * @return CompetencyAbility
     */
    public function deserialize($data, CompetencyAbility $competencyAbility)
    {
        $this->sipe('id', 'setUuid', $data, $competencyAbility);

        $competency = isset($data['competency']['id']) ?
            $this->competencyRepo->findOneBy(['uuid' => $data['competency']['id']]) :
            null;
        $competencyAbility->setCompetency($competency);

        $level = isset($data['level']['id']) ?
            $this->levelRepo->findOneBy(['uuid' => $data['level']['id']]) :
            null;
        $competencyAbility->setLevel($level);

        $ability = isset($data['ability']['id']) ?
            $this->abilityRepo->findOneBy(['uuid' => $data['ability']['id']]) :
            null;

        if (!$ability) {
            $ability = new Ability();
        }
        $this->sipe('ability.id', 'setUuid', $data, $ability);
        $this->sipe('ability.name', 'setName', $data, $ability);
        $this->sipe('ability.minResourceCount', 'setMinResourceCount', $data, $ability);
        $this->sipe('ability.minEvaluatedResourceCount', 'setMinEvaluatedResourceCount', $data, $ability);
        $this->om->persist($ability);
        $competencyAbility->setAbility($ability);

        return $competencyAbility;
    }
}
