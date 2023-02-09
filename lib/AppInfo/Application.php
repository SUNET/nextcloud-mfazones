<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\MfaVerifiedZone\AppInfo;

use OCP\AppFramework\App;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;

class Application extends App {
	public const APP_ID = 'mfaverifiedzone';
	public const TAG_NAME = 'mfaresterictedzone';

    protected ISystemTagManager $systemTagManager;

	public function __construct() {
		parent::__construct(self::APP_ID);

        $container = $this->getContainer();
        $server = $container->getServer();
        $eventDispatcher = $server->getEventDispatcher();

        $this->systemTagManager = $this->getContainer()->get(ISystemTagManager::class);;

        $eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
            \OCP\Util::addStyle(self::APP_ID, 'tabview' );
            \OCP\Util::addScript(self::APP_ID, 'tabview' );
            \OCP\Util::addScript(self::APP_ID, 'plugin' );

            $policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();
            \OC::$server->getContentSecurityPolicyManager()->addDefaultPolicy($policy);
        });

        $tags = $this->systemTagManager->getAllTags(
			null,
			self::TAG_NAME
		);

        if(count($tags) < 1){
            $this->systemTagManager->createTag(self::TAG_NAME, false, false);
        }
	}
}
