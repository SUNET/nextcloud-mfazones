<?php

namespace OCA\MfaVerifiedZone\Controller;

use OCA\MfaVerifiedZone\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Util;
use OCP\Files\IRootFolder;
use OCP\Activity\IManager;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\SystemTag\ISystemTagManager;

use OCP\SystemTag\ISystemTagObjectMapper;

class MfaVerifiedZoneController extends Controller
{
    /** @var IUserManager */
    private $userManager;

    /** @var IRootFolder */
    private $rootFolder;

    /** @var string */
    private $userId;

    /** @var ISystemTagManager */
    protected ISystemTagManager $systemTagManager;

    /** @var IGroupManager */
    private $groupManager;

    /** @var \OCP\ITags */
    private $tagger;
    

    /** @var \OCP\ITagManager */
    private $tagManager;

    /** @var ISystemTagObjectMapper */
    private $tagMapper;

    public function __construct(
        IRequest $request,
        IUserManager $userManager,
        IRootFolder $rootFolder,
        IGroupManager $groupManager,
        \OCP\ITagManager $tagManager,
        string $userId,
        ISystemTagObjectMapper $tagMapper,
        ISystemTagManager $systemTagManager
    )
    {
        parent::__construct(Application::APP_ID, $request);
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->userId = $userId;
        $this->groupManager = $groupManager;
        $this->tagManager = $tagManager;
        $this->tagger = null;
        $this->tagMapper = $tagMapper;
        $this->systemTagManager = $systemTagManager;
    }

    /**
     * Returns the tagger
     *
     * @return \OCP\ITags tagger
     */
    private function getTagger()
    {
        if (!$this->tagger) {
            $this->tagger = $this->tagManager->load('files');
        }
        return $this->tagger;
    }

    private function hasAccess($source)
    {
        try {
            $isAdmin = $this->groupManager->isAdmin($this->userId);
            $userRoot = $this->rootFolder->getUserFolder($this->userId);

            try {
                $node = $userRoot->get($source);
                $hasAccess = $isAdmin || $node->getOwner()->getUID() === $this->userId;
            } catch (\Exception $e) {
                \OC::$server->getLogger()->logException($e, ['app' => 'mfaverifiedzone']);
                $hasAccess = false;
            }
            return $hasAccess;

        } catch (\Exception $e) {
            \OC::$server->getLogger()->logException($e, ['app' => 'mfaverifiedzone']);

            return false;
        }
    }

    /**
     * @NoAdminRequired
     */
    public function get($source)
    {
        try {
            $userRoot = $this->rootFolder->getUserFolder($this->userId);
            $node = $userRoot->get($source);
            $tags = $this->systemTagManager->getAllTags(
                null,
                Application::TAG_NAME
            );
            $tag = current($tags);
            $tagId = $tag->getId();
            $type = $this->castObjectType($node->getType());
            $result = $this->tagMapper->haveTag($node->getId(), $type, $tagId);

            return new JSONResponse(
                array(
                    'status' => $result
                )
            );

        } catch (\Exception $e) {
            \OC::$server->getLogger()->logException($e, ['app' => 'mfaverifiedzone']);

            return new JSONResponse(
                array(
                    'response' => 'error',
                    'msg' => $e->getMessage()
                )
            );
        }
    }

    /**
     * @NoAdminRequired
     */
    public function set($source, $protect)
    {
        try {
            $hasAccess = $this->hasAccess($source);
            if (!$hasAccess) {
                return new DataResponse([], Http::STATUS_FORBIDDEN);
            }
            $userRoot = $this->rootFolder->getUserFolder($this->userId);
            $node = $userRoot->get($source);
            $tags = $this->systemTagManager->getAllTags(
                null,
                Application::TAG_NAME
            );
            $tag = current($tags);
            $tagId = $tag->getId();

            $type = $this->castObjectType($node->getType());

            if ($protect == "true") {
                $this->getTagger()->tagAs($node->getId(), Application::TAG_NAME);
                $this->tagMapper->assignTags($node->getId(), $type, $tagId);
            } else {
                $this->getTagger()->unTag($node->getId(), Application::TAG_NAME);
                $this->tagMapper->unassignTags($node->getId(), $type, $tagId);
            }

            return new DataResponse([], Http::STATUS_OK);

        } catch (\Exception $e) {
            \OC::$server->getLogger()->logException($e, ['app' => 'mfaverifiedzone']);

            return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function access($source)
    {
        return new JSONResponse(
            array(
                'access' => $this->hasAccess($source)
            )
        );
    }

    private function castObjectType($type)
    {
        if ($type == 'file') {
            return "files";
        }
        if ($type == "dir") {
            return "files";
        }
        return $type;
    }
}