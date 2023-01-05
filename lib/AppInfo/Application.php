<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\MfaVerifiedZone\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'mfaverifiedzone';

	public function __construct() {
		parent::__construct(self::APP_ID);

        $container = $this->getContainer();
        $server = $container->getServer();
        $eventDispatcher = $server->getEventDispatcher();

        $eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
            \OCP\Util::addStyle('mfazone', 'tabview' );
            \OCP\Util::addScript('mfazone', 'tabview' );
            \OCP\Util::addScript('mfazone', 'plugin' );

            $policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();
            \OC::$server->getContentSecurityPolicyManager()->addDefaultPolicy($policy);
        });
	}
}
