<?php
namespace OCA\MfaZone\Controller;

use OC\Files\Filesystem;
use OCA\MfaZone\AppInfo\Application;
use OCA\MfaZone\Service\MfaZoneService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class MfaZoneController extends Controller {
	protected $mfazoneService;

	public function __construct($appName, IRequest $request, MfaZoneService $mfazoneService) {
		parent::__construct($appName, $request);
		$this->mfazoneService = $mfazoneService;
	}

	/**
	 * @NoAdminRequired
	 */
	public function get($source)
    {
        try {
            return new JSONResponse(
                array(
                    'response' => 'error',
                    'msg' => Application::getL10N()->t('No mfazone found.')
                )
            );
        } catch (\Exception $e) {
            \OC::$server->getLogger()->logException($e, ['app' => 'mfazone']);

            return new JSONResponse(
                array(
                    'response' => 'error',
                    'msg' => $e->getMessage()
                )
            );
        }
    }
}
