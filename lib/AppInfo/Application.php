<?php

namespace OCA\MfaZone\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	const APP_NAME = 'mfazone';

	/**
	 * Application constructor.
	 *
	 * @param array $params
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function __construct(array $params = []) {
		parent::__construct(self::APP_NAME, $params);

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


	public static function getL10N() {
		return \OC::$server->getL10N(Application::APP_NAME);
	}
}
