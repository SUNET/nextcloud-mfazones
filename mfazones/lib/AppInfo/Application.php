<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\AppInfo;

use Doctrine\DBAL\Exception;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\mfazones\MFAPlugin;
use OCA\mfazones\Check\MfaVerified;
// use OCA\WorkflowEngine\Manager;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ISession;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
// use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Util;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use Psr\Log\LoggerInterface;
use OCP\mfazones\Listener\RegisterFlowOperationsListener;
use OCA\mfazones\Listener\TwoFactorProviderChallengePassedListener;
use OCA\mfazones\Listener\TwoFactorProviderChallengeForUserEnabledListener;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;

class Application extends App implements IBootstrap
{
  public const APP_ID = 'mfazones';
  public const TAG_NAME = 'mfazone';

  /** @var ISystemTagManager */
  protected ISystemTagManager $systemTagManager;

  /** @var IManager */
  protected $manager;

  /** @var LoggerInterface */
  private $logger;

  /** @var IDBConnection */
  protected $connection;

  /** @var IL10N */
  protected $l;

  /** @var ISession */
  protected $session;

  /** @var MfaVerified */
  protected $mfaVerifiedCheck;

  public function __construct()
  {
    parent::__construct(self::APP_ID);

    $container = $this->getContainer();
    $container->registerService(MFAPlugin::class, function ($c) {
      $systemTagManager = $c->query(ISystemTagManager::class);
      $tagMapper = $c->query(ISystemTagObjectMapper::class);
      $x = new MFAPlugin($systemTagManager, $tagMapper);
      return $x;
    });

    $this->l = $this->getContainer()->get(IL10N::class);
    $this->session = $this->getContainer()->get(ISession::class);
    $this->logger = $this->getContainer()->get(LoggerInterface::class);
    $this->mfaVerifiedCheck = new MfaVerified($this->l, $this->session, $this->logger);

    /* @var IEventDispatcher $dispatcher */
    $dispatcher = $this->getContainer()->query(IEventDispatcher::class);
    $dispatcher->addListener(RegisterChecksEvent::class, function (RegisterChecksEvent $event) {
      // copied from https://github.com/nextcloud/flow_webhooks/blob/d06203fa3cc6a5dc83b6f08ab7dd82d61585d334/lib/Listener/RegisterChecksListener.php
      if (!($event instanceof RegisterChecksEvent)) {
        return;
      }
      $event->registerCheck($this->mfaVerifiedCheck);
      Util::addScript(Application::APP_ID, 'mfazones-main');
    });

    $this->systemTagManager = $this->getContainer()->get(ISystemTagManager::class);
    $this->manager = $this->getContainer()->get(IManager::class);
    $this->connection = $this->getContainer()->get(IDBConnection::class);

    $dispatcher->addListener(RegisterOperationsEvent::class, function () {
      \OCP\Util::addScript(self::APP_ID, 'mfazones-main');
    });
    $dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
      \OCP\Util::addStyle(self::APP_ID, 'tabview');
      \OCP\Util::addScript(self::APP_ID, 'mfazones-main');

      // $policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();
      // \OC::$server->getContentSecurityPolicyManager()->addDefaultPolicy($policy);
    });
    $groupManager = \OC::$server->get(\OCP\IGroupManager::class);
    $userSession = \OC::$server->get(\OCP\IUserSession::class);
    $user = $userSession->getUser();
    // The first time an admin logs in to the server, this will create the tag and flow
    if ($user !== null && $groupManager->isAdmin($user->getUID())) {
      $this->addFlows();
    }
  }

  /**
   * @inheritdoc
   */
  public function register(IRegistrationContext $context): void
  {
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
    $this->logger->debug("MFA: done with listners");
  }

  /**
   * @inheritdoc
   */
  public function boot(IBootContext $context): void
  {
  }

  public static function castObjectType($type)
  {
    if ($type === 'file') {
      return "files";
    }
    if ($type === "dir") {
      return "files";
    }
    return $type;
  }

  public static function getOurTagIdFromSystemTagManager($systemTagManager)
  {
    try {
      $tags = $systemTagManager->getAllTags(
        null,
        self::TAG_NAME
      );

      if (count($tags) < 1) {
        $tag = $systemTagManager->createTag(self::TAG_NAME, false, false);
      } else {
        $tag = current($tags);
      }
      return $tag->getId();
    } catch (Exception $e) {
      $this->logger->error('Error when inserting tag on enabling mfazones app', ['exception' => $e]);
      return false;
    }
  }

  public function nodeHasTag($node, $tagId)
  {
    $tags = $this->systemTagManager->getTagsForObjects([$node->getId()]);
    foreach ($tags as $tag) {
      if ($tag->getId() === $tagId) {
        return true;
      }
    }
    return false;
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

      $tagId = self::getOurTagIdFromSystemTagManager($this->systemTagManager); // will create the tag if necessary

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
