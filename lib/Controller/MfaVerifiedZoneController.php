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

class MfaVerifiedZoneController extends Controller {
	/** @var IUserManager */
	private $userManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var string */
	private $userId;

	/** @var IGroupManager */
	private $groupManager;

	/**
	 * @var \OCP\ITags
	 */
	private $tagger;

	/**
	 * @var \OCP\ITagManager
	 */
	private $tagManager;

	public function __construct(IRequest $request,
								IUserManager $userManager,
								IRootFolder $rootFolder,
                                IGroupManager $groupManager,
                                \OCP\ITagManager $tagManager,
                                string $userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
        $this->userId = $userId;
        $this->groupManager = $groupManager;
		$this->tagManager = $tagManager;
		$this->tagger = null;
	}

    /**
	 * Returns the tagger
	 *
	 * @return \OCP\ITags tagger
	 */
	private function getTagger() {
		if (!$this->tagger) {
			$this->tagger = $this->tagManager->load('files');
		}
		return $this->tagger;
	}

    private function hasAccess($source){
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
    public function get($source) {
        try {
            $userRoot = $this->rootFolder->getUserFolder($this->userId);
            $node = $userRoot->get($source);
            $tags = $this->getTagger()->getTagsForObjects([$node->getId()]);
            $tags = current($tags);
            if ($tags === false) {
				// the tags API returns false on error...
				$result = false;
			} else{
                $result = in_array(Application::TAG_NAME, $tags);
            }

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
    public function set($source, $protect) {
        try {
            $hasAccess = $this->hasAccess($source);
            if(!$hasAccess){
                return new DataResponse([], Http::STATUS_FORBIDDEN);
            }
            $userRoot = $this->rootFolder->getUserFolder($this->userId);
            $node = $userRoot->get($source);

            if($protect == true){
                $this->getTagger()->tagAs($node->getId(), Application::TAG_NAME);
            }else {
                $this->getTagger()->unTag($node->getId(), Application::TAG_NAME);
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
    public function access($source) {
       return new JSONResponse(
                array(
                    'access' => $this->hasAccess($source)
                )
            );
    }
}
