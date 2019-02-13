<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Controller\API;

use Claroline\AppBundle\Controller\AbstractCrudController;
use Claroline\CoreBundle\Entity\User;
use Claroline\PortfolioBundle\Entity\Portfolio;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @EXT\Route("/portfolio")
 */
class PortfolioController extends AbstractCrudController
{
    public function getName()
    {
        return 'portfolio';
    }

    public function getClass()
    {
        return Portfolio::class;
    }

    public function getIgnore()
    {
        return ['exist', 'copyBulk', 'schema', 'find'];
    }

    /**
     * @EXT\Route(
     *     "/my/list",
     *     name="apiv2_portfolio_my_list"
     * )
     * @EXT\ParamConverter("user", converter="current_user", options={"allowAnonymous"=false})
     *
     * @param User    $user
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function myPortfoliosListAction(User $user, Request $request)
    {
        $params = $request->query->all();

        if (!isset($params['hiddenFilters'])) {
            $params['hiddenFilters'] = [];
        }
        $params['hiddenFilters']['owner'] = $user->getUuid();

        return new JsonResponse(
            $this->finder->search(Portfolio::class, $params)
        );
    }
}
