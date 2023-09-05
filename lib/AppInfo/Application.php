<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\AppInfo;

use OCP\AppFramework\App;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCA\WorkflowEngine\Manager;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCP\WorkflowEngine\IManager;
use OCP\IDBConnection;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\IUserManager;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorManager;

class Application extends App {
	public const APP_ID = 'mfazones';
	public const TAG_NAME = 'mfazone';
    
    /** @var ISystemTagManager */
    protected ISystemTagManager $systemTagManager;

	/** @var Manager */
	protected $manager;

	/** @var LoggerInterface */
	private $logger;

	/** @var IDBConnection */
	protected $connection;

	public function __construct() {
		parent::__construct(self::APP_ID);

        // if (!\OCP\App::isEnabled('files_accesscontrol')) {
        //     throw new Exception("MFA Zone needs files_accesscontrol app to be enabled before installation.");
        // }

        $container = $this->getContainer();
        $server = $container->getServer();
        $eventDispatcher = $container->get(IEventDispatcher::class);

        $this->systemTagManager = $this->getContainer()->get(ISystemTagManager::class);
        $this->manager = $this->getContainer()->get(Manager::class);
        $this->logger = $this->getContainer()->get(LoggerInterface::class);
        $this->connection = $this->getContainer()->get(IDBConnection::class);

        $eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
            \OCP\Util::addStyle(self::APP_ID, 'tabview' );
            \OCP\Util::addScript(self::APP_ID, 'tabview' );
            \OCP\Util::addScript(self::APP_ID, 'plugin' );

            $policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();
            \OC::$server->getContentSecurityPolicyManager()->addDefaultPolicy($policy);
        });

        $this->addTag();
        $this->addFlows();
	}

    private function addTag(){
        try{
            $tags = $this->systemTagManager->getAllTags(
                null,
                self::TAG_NAME
            );

            if(count($tags) < 1){
                $this->systemTagManager->createTag(self::TAG_NAME, false, false);
            }
        }catch (Exception $e) {
            $this->logger->error('Error when inserting tag on enabling mfazones app', ['exception' => $e]);
        }
    }

    private function addFlows(){
        try {
            $hash = md5('OCA\WorkflowEngine\Check\MfaVerified::!is::');

            $query = $this->connection->getQueryBuilder();
            $query->select('id')
                ->from('flow_checks')
                ->where($query->expr()->eq('hash', $query->createNamedParameter($hash)));
            $result = $query->execute();

            if ($row = $result->fetch()) {
                $result->closeCursor();
                return;
            }

            $tags = $this->systemTagManager->getAllTags(
                null,
                self::TAG_NAME
            );
            $tag = current($tags);
            $tagId = $tag->getId();

            $scope = new ScopeContext(IManager::SCOPE_ADMIN);
            $class = "OCA\\FilesAccessControl\\Operation";
            $name = "";
            $checks =  [
                [
                      "class" => "OCA\WorkflowEngine\Check\MfaVerified", 
                      "operator" => "!is", 
                      "value" => "", 
                      "invalid" => false 
                   ], 
                [
                         "class" => "OCA\WorkflowEngine\Check\FileSystemTags", 
                         "operator" => "is", 
                         "value" => $tagId, 
                         "invalid" => false 
                      ]
                // uncomment this code to re-activate admin bypass,
                // see https://github.com/pondersource/nextcloud-mfa-awareness/issues/53
                // [
                //             "class" => "OCA\WorkflowEngine\Check\UserGroupMembership",
                //             "operator" => "!is",
                //             "value" => "admin",
                //             "invalid" => false
                //          ]
                ];
            $operation = "deny";
            $entity = "OCA\\WorkflowEngine\\Entity\\File";
            $events = [];

            $this->manager->addOperation($class, $name, $checks, $operation, $scope, $entity, $events);
        } catch (Exception $e) {
            $this->logger->error('Error when inserting flow on enabling mfazones app', ['exception' => $e]);
        }
    }
}
