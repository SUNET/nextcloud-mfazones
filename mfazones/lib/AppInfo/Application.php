<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\AppInfo;

use Doctrine\DBAL\Exception;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\mfazones\MFAPlugin;
use OCA\mfazones\Check\MfaVerified;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ISession;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use Psr\Log\LoggerInterface;
use OCA\mfazones\Listeners\AppEnableEventListener;
use OCA\mfazones\Listeners\AppDisableEventListener;
use OCA\mfazones\Listeners\RegisterFlowOperationsListener;
use OCA\mfazones\Listeners\TwoFactorProviderChallengePassedListener;
use OCA\mfazones\Listeners\TwoFactorProviderForUserEnabledListener;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppDisableEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;

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
    // $this->manager = $this->getContainer()->get(IManager::class);
    $this->connection = $this->getContainer()->get(IDBConnection::class);

    $dispatcher->addListener(RegisterOperationsEvent::class, function () {
      \OCP\Util::addScript(self::APP_ID, 'mfazones-main');
    });
    $dispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
      \OCP\Util::addStyle(self::APP_ID, 'tabview');
      \OCP\Util::addScript(self::APP_ID, 'mfazones-main');
    });
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
    $this->logger->debug("MFA: register app enable listner");
    $context->registerEventListener(AppEnableEvent::class, AppEnableEventListener::class);
    $this->logger->debug("MFA: register app disable listner");
    $context->registerEventListener(AppDisableEvent::class, AppDisableEventListener::class);
    $this->logger->debug("MFA: done with listners");
  }

  /**
   * @inheritdoc
   */
  public function boot(IBootContext $context): void
  {
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
      return false;
    }
  }
}
