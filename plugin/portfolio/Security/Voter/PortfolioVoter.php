<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Security\Voter;

use Claroline\CoreBundle\Security\Voter\AbstractVoter;
use Claroline\PortfolioBundle\Entity\Portfolio;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @DI\Service
 * @DI\Tag("security.voter")
 */
class PortfolioVoter extends AbstractVoter
{
    public function checkPermission(TokenInterface $token, $object, array $attributes, array $options)
    {
        switch ($attributes[0]) {
            case self::CREATE:
            case self::OPEN:
            case self::VIEW:
                return 'anon.' !== $token->getUser() ?
                    VoterInterface::ACCESS_GRANTED :
                    VoterInterface::ACCESS_DENIED;
            case self::EDIT:
            case self::DELETE:
                return $this->checkEdit($token, $object);
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    public function getClass()
    {
        return Portfolio::class;
    }

    public function getSupportedActions()
    {
        return[self::OPEN, self::VIEW, self::CREATE, self::EDIT, self::DELETE];
    }

    private function checkEdit(TokenInterface $token, Portfolio $porfolio)
    {
        $user = $token->getUser();

        if ('anon.' !== $user && $user->getUuid() === $porfolio->getOwner()->getUuid()) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}
