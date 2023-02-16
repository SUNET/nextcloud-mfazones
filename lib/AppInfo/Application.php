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

        $this->addFlows();
	}

    private function addFlows(){
        $body = '{
            "id":-1676026382678,
            "class":"OCA\\FilesAccessControl\\Operation",
            "entity":"OCA\\WorkflowEngine\\Entity\\File",
            "events":[
               
            ],
            "name":"",
            "checks":[
               {
                  "class":"OCA\\WorkflowEngine\\Check\\MfaVerified",
                  "operator":"!is",
                  "value":"",
                  "invalid":false
               },
               {
                  "class":"OCA\\WorkflowEngine\\Check\\FileSystemTags",
                  "operator":"is",
                  "value":1,
                  "invalid":false
               },
               {
                  "class":"OCA\\WorkflowEngine\\Check\\UserGroupMembership",
                  "operator":"!is",
                  "value":"admin",
                  "invalid":false
               }
            ],
            "operation":"deny",
            "valid":true
         }';
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8080/ocs/v2.php/apps/workflowengine/api/v1/workflows/global?format=json");
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
          $result = curl_exec($ch);
          error_log(print_r($result, true));
    }
}
