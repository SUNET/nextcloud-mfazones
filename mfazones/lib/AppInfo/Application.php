<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\AppInfo;


use Doctrine\DBAL\Exception;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\WorkflowEngine\IManager;
use OCA\mfazones\MFAPlugin;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;

// Our listeners
use OCP\mfazones\Listeners\RegisterFlowOperationsListener;
use OCA\mfazones\Listeners\TwoFactorProviderChallengePassedListener;
use OCA\mfazones\Listeners\TwoFactorProviderForUserEnabledListener;
use OCA\mfazones\Listeners\RegisterChecksEventListener;

// Events we listen to
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;

use OCA\mfazones\Utils\MFAZonesUtils;
use Throwable;

/**
 * Class Application
 *
 * @package OCA\mfazones\AppInfo
 */
class Application extends App implements IBootstrap
{
  public const APP_ID = 'mfazones';
  public const TAG_NAME = 'mfazone';

  /** @var ISystemTagManager */
  protected ISystemTagManager $systemTagManager;

  /** @var LoggerInterface */
  private $logger;

  /** @var IDBConnection */
  protected $connection;

  /** @var IServerContainer */
  protected $serverContainer;

  /** @var Manager */
  protected $manager;

  /** @var IL10N */
  protected $l;

  /** @var IUserSession */
  protected $userSession;

  /** @var IConfig */
  protected $config;

  /** @var IEventDispatcher */
  protected $dispatcher;

  /** @var ICacheFactory */
  protected $cacheFactory;

  public function __construct()
  {
    parent::__construct(self::APP_ID);

    $this->logger = $this->getContainer()->get(LoggerInterface::class);
    /* @var ISystemTagManager */
    $this->systemTagManager = $this->getContainer()->get(ISystemTagManager::class);
    /* @var Manager */
    $this->connection = $this->getContainer()->get(IDBConnection::class);

    $this->serverContainer = $this->getContainer()->get(IServerContainer::class);

    $this->userSession = $this->getContainer()->get(\OCP\IUserSession::class);

    $this->l = $this->getContainer()->get(IL10N::class);

    $this->dispatcher = $this->getContainer()->get(IEventDispatcher::class);

    $this->config = $this->getContainer()->get(IConfig::class);

    $this->cacheFactory = $this->getContainer()->get(ICacheFactory::class);

    $this->manager = new Manager($this->connection, $this->serverContainer, $this->l, $this->logger, $this->userSession, $this->dispatcher, $this->config, $this->cacheFactory);
  }

  /**
   * @inheritdoc
   */
  public function register(IRegistrationContext $context): void
  {
    $this->logger->debug("MFA: registering service");
    $context->registerService(MFAPlugin::class, function ($c) {
      $systemTagManager = $c->get(ISystemTagManager::class);
      $tagMapper = $c->get(ISystemTagObjectMapper::class);
      $x = new MFAPlugin($systemTagManager, $tagMapper);
      return $x;
    });
    // TODO: Remove this when we drop support for NC < 28
    if (class_exists(TwoFactorProviderChallengePassed::class)) {
      $this->logger->debug("MFA: detection class is TwoFactorProviderChallengePassed");
      $context->registerEventListener(TwoFactorProviderChallengePassed::class, TwoFactorProviderChallengePassedListener::class);
    } else {
      $this->logger->warning("MFA: detection class is deprecated class TwoFactorProviderForUserEnabled");
      $context->registerEventListener(TwoFactorProviderForUserEnabled::class, TwoFactorProviderForUserEnabledListener::class);
    }
    $this->logger->debug("MFA: register operations listner");
    $context->registerEventListener(RegisterOperationsEvent::class, RegisterFlowOperationsListener::class);
    $context->registerEventListener(LoadAdditionalScriptsEvent::class, RegisterFlowOperationsListener::class);
    $this->logger->debug("MFA: register check listner");
    $context->registerEventListener(RegisterChecksEvent::class, RegisterChecksEventListener::class);
    $this->logger->debug("MFA: done with listners");

    $groupManager = \OC::$server->get(\OCP\IGroupManager::class);
    $user = $this->userSession->getUser();
    // The first time an admin logs in to the server, this will create the tag and flow
    if ($user !== null && $groupManager->isAdmin($user->getUID())) {
      $this->addFlows();
    }
  }

  /**
   * @param IBootContext $context
   *
   * @throws Throwable
   */
  public function boot(IBootContext $context): void
  {
  }


  private function addFlows()
  {
    try {
      $hash = md5('OCA\mfazones\Check\MfaVerified::!is::');

      $query = $this->connection->getQueryBuilder();
      $query->select('id')
        ->from('flow_checks')
        ->where($query->expr()->eq('hash', $query->createNamedParameter($hash)));
      $result = $query->execute();

      if ($row = $result->fetch()) {
        $result->closeCursor();
        return;
      }

      $tagId = MFAZonesUtils::getOurTagIdFromSystemTagManager($this->systemTagManager); // will create the tag if necessary

      $scope = new ScopeContext(IManager::SCOPE_ADMIN);
      $class = "OCA\\FilesAccessControl\\Operation";
      $name = "";
      $checks =  [
        [
          "class" => "OCA\mfazones\Check\MfaVerified",
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
