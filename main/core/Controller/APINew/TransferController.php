<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\APINew;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\TransferProvider;
use Claroline\AppBundle\Controller\AbstractCrudController;
use Claroline\AppBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Entity\File\PublicFile;
use Claroline\CoreBundle\Entity\Import\File;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @EXT\Route("/transfer")
 */
class TransferController extends AbstractCrudController
{
    /** @var TransferProvider */
    private $provider;

    /** @var string */
    private $schemaDir;

    /**
     * @param TransferProvider $provider
     * @param string           $schemaDir
     */
    public function __construct(
        TransferProvider $provider,
        $schemaDir
    ) {
        $this->provider = $provider;
        $this->schemaDir = $schemaDir;
    }

    public function getName()
    {
        return 'transfer';
    }

    public function getClass()
    {
        return File::class;
    }

    public function getIgnore()
    {
        return ['update', 'exist', 'schema'];
    }

    /**
     * @return array
     */
    public function getRequirements()
    {
        return [
            'get' => ['id' => '^(?!.*(schema|copy|parameters|find|transfer|\/)).*'],
            'update' => ['id' => '^(?!.*(schema|parameters|find|transfer|\/)).*'],
            'exist' => [],
        ];
    }

    /**
     * @EXT\Route("/upload/{workspaceId}", name="apiv2_transfer_upload_file")
     * @EXT\ParamConverter("organization", options={"mapping": {"id": "uuid"}})
     * @EXT\Method("POST")
     *
     * @param Request $request
     * @param int     $workspaceId
     *
     * @return JsonResponse
     */
    public function uploadFileAction(Request $request, $workspaceId = null)
    {
        $toUpload = $request->files->all()['file'];
        $handler = $request->get('handler');

        /** @var StrictDispatcher */
        $dispatcher = $this->container->get('Claroline\AppBundle\Event\StrictDispatcher');

        $object = $this->crud->create(PublicFile::class, [], ['file' => $toUpload]);

        $dispatcher->dispatch(strtolower('upload_file_'.$handler), 'File\UploadFile', [$object]);

        $file = $this->serializer->serialize($object);

        $workspace = null;
        if ($workspaceId) {
            $workspace = $this->om->getRepository(Workspace::class)->find($workspaceId);
        }

        $this->crud->create(File::class, [
            'uploadedFile' => $file,
            'workspace' => $workspace ? $this->serializer->serialize($workspace) : null,
        ]);

        return new JsonResponse([$file], 200);
    }

    /**
     * @EXT\Route("/workspace/{workspaceId}", name="apiv2_workspace_transfer_list")
     * @EXT\Method("GET")
     *
     * @param int     $workspaceId
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function workspaceListAction($workspaceId, Request $request)
    {
        $query = $request->query->all();
        $options = $this->options['list'];

        if (isset($query['options'])) {
            $options = $query['options'];
        }

        $query['hiddenFilters'] = ['workspace' => $workspaceId];

        return new JsonResponse($this->finder->search(
          self::getClass(),
          $query,
          $options
      ));
    }

    /**
     * @EXT\Route("/start", name="apiv2_transfer_start")
     * @EXT\Method("POST")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function startAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $file = $data['file'];
        unset($data['file']);
        $action = $data['action'];
        unset($data['action']);

        $publicFile = $this->om->getObject($file, PublicFile::class) ?? new PublicFile();
        $uuid = $request->get('workspace');
        $workspace = $this->om->getRepository(Workspace::class)->findOneBy(['uuid' => $uuid]);

        if ($workspace) {
            $data['workspace'] = $this->serializer->serialize($workspace, [Options::SERIALIZE_MINIMAL]);
        }

        $this->container->get('claroline.manager.api_manager')->import(
            $publicFile,
            $action,
            $this->getLogFile($request),
            $data
        );

        return new JsonResponse('started', 200);
    }

    /**
     * @EXT\Route("/schema", name="apiv2_transfer_schema")
     * @EXT\Method("GET")
     *
     * @param string $class
     *
     * @return JsonResponse
     */
    public function schemaAction($class)
    {
        $file = $this->schemaDir.'/transfer.json';

        return new JsonResponse($this->serializer->loadSchema($file));
    }

    /**
     * @EXT\Route("/export/{format}", name="apiv2_transfer_export")
     * @EXT\Method("GET")
     *
     * @param Request $request
     * @param string  $format
     *
     * @return Response
     */
    public function exportAction(Request $request, $format)
    {
        $results = $this->finder->search(
            //maybe use a class map because it's the entity one currently
            $request->query->get('class'),
            $request->query->all(),
            []
        );

        return new Response($this->provider->format($format, $results['data'], $request->query->all()));
    }

    /**
     * @EXT\Route("/action/{name}/{format}", name="apiv2_transfer_action")
     * @EXT\Method("GET")
     *
     * @param string $name
     * @param string $format
     *
     * @return JsonResponse
     */
    public function getExplanationAction($name, $format)
    {
        return new JsonResponse($this->provider->explainAction($name, $format));
    }

    /**
     * @EXT\Route("/actions/{format}", name="apiv2_transfer_actions")
     * @EXT\Method("GET")
     *
     * @param string $format
     *
     * @return JsonResponse
     */
    public function getAvailableActions($format)
    {
        return new JsonResponse($this->provider->getAvailableActions($format));
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function getLogFile(Request $request)
    {
        return $request->query->get('log');
    }
}
