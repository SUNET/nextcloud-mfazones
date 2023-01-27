<?php

namespace OCA\MfaVerifiedZone\Controller;

use OCA\MfaVerifiedZone\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Util;
use OCP\Files\IRootFolder;
use OCP\Activity\IManager;
use OCP\IUserManager;

class MfaVerifiedZoneController extends Controller {
	/** @var IUserManager */
	private $userManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var string */
	private $userId;
	/** @var IManager */
	private $activityManager;

	public function __construct(IRequest $request,
								IUserManager $userManager,
								IRootFolder $rootFolder,
                                IManager $activityManager) {
		parent::__construct(Application::APP_ID, $request);
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->activityManager = $activityManager;
	}

    /**
     * @NoAdminRequired
     */
    public function get($source) {
        //TODO Check for the owner and current status
        try {
            return new JSONResponse(
                array(
                    'status' => true
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
    public function access($path) {
        //TODO Check for the owner and current status
        try {
            error_log( print_r( $this->activityManager->getCurrentUserId(), true ) );
            $userRoot = $this->rootFolder->getUserFolder($this->userId);

            try {
                $node = $userRoot->get($path);
            } catch (\Exception $e) {
                return new DataResponse([], Http::STATUS_BAD_REQUEST);
            }
            return new JSONResponse(
                array(
                    'access' => false
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
}
