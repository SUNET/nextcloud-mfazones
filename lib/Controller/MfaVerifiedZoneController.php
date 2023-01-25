<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\MfaVerifiedZone\Controller;

use OCA\MfaVerifiedZone\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class MfaVerifiedZoneController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		Util::addScript(Application::APP_ID, 'mfaverifiedzone-main');

		return new TemplateResponse(Application::APP_ID, 'main');
	}

    /**
     * @NoAdminRequired
     */
    public function get($source) {
        //TODO Check for the owner and current status
        try {
            return true;

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
