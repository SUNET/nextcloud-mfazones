<?php
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\MfaVerifiedZone\AppInfo;

use OCP\AppFramework\App;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCA\WorkflowEngine\Manager;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception;

class Application extends App {
	public const APP_ID = 'mfaverifiedzone';
	public const TAG_NAME = 'mfaresterictedzone__tag';
    
    /** @var ISystemTagManager */
    protected ISystemTagManager $systemTagManager;

	/** @var Manager */
	protected $manager;

	/** @var LoggerInterface */
	private $logger;

	public function __construct() {
		parent::__construct(self::APP_ID);

        if (!\OCP\App::isEnabled('files_accesscontrol')) {
            throw new Exception("MFA Zone needs files_accesscontrol app to be enabled before installation.");
        }

        $container = $this->getContainer();
        $server = $container->getServer();
        $eventDispatcher = $server->getEventDispatcher();

        $this->systemTagManager = $this->getContainer()->get(ISystemTagManager::class);
        $this->manager = $this->getContainer()->get(Manager::class);
        $this->logger = $this->getContainer()->get(LoggerInterface::class);

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

        $this->addFlows();
	}

    private function addFlows(){
        try {
            $scope = new ScopeContext(IManager::SCOPE_ADMIN);
            $class = "OCA\\FilesAccessControl\\Operation";
            $name = "";
            $checks = json_decode('[{"class":"OCA\\WorkflowEngine\\Check\\MfaVerified","operator":"!is","value":"","invalid":false},{"class":"OCA\\WorkflowEngine\\Check\\FileSystemTags","operator":"is","value":1,"invalid":false},{"class":"OCA\\WorkflowEngine\\Check\\UserGroupMembership","operator":"!is","value":"admin","invalid":false}]');
            $operation = "deny";
            $entity = "OCA\\WorkflowEngine\\Entity\\File";
            $events = [];

            $this->manager->addOperation($class, $name, $checks, $operation, $scope, $entity, $events);
        } catch (Exception $e) {
            $this->logger->error('Error when inserting flow on enabling MFAverifiedzone app', ['exception' => $e]);
        }
    }
}
