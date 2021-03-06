<?php

namespace Claroline\CoreBundle\API\Validator\Resource;

use Claroline\AppBundle\API\ValidatorInterface;
use Claroline\AppBundle\API\ValidatorProvider;
use Claroline\AppBundle\Persistence\ObjectManager;

class ResourceNodeValidator implements ValidatorInterface
{
    /** @var ObjectManager */
    private $om;

    /**
     * UserValidator constructor.
     *
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function getClass()
    {
        return 'Claroline\CoreBundle\Entity\Resource\ResourceNode';
    }

    public function getUniqueFields()
    {
        // we don't put the name as an unique constraint because for existing
        // one we don't throw validation errors, we just generate an unique one
        return [];
    }

    /**
     * @param array  $data
     * @param string $mode
     *
     * @return array
     */
    public function validate($data, $mode, array $options = [])
    {
        $errors = [];

        // implements something cleaner later
        if (ValidatorProvider::UPDATE === $mode && !isset($data['id'])) {
            return $errors;
        }

        // validates the resource type exists
        if (isset($data['meta']) && isset($data['meta']['type'])) {
            $resourceType = $this->om
                ->getRepository('ClarolineCoreBundle:Resource\ResourceType')
                ->findOneBy(['name' => $data['meta']['type']]);

            if (!$resourceType) {
                $errors[] = [
                    'path' => 'meta/type',
                    'message' => sprintf('The type %s does not exist.', $data['meta']['type']),
                ];
            }
        }

        return $errors;
    }
}
