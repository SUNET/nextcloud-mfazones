<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-FileCopyrightText: SUNET <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\mfazones\Check\MfaVerified;
use OCA\mfazones\Listeners\AppDisableEventListener;
use OCA\mfazones\Listeners\AppEnableEventListener;
use OCA\mfazones\Listeners\LoadAdditionalScriptsListener;
use OCA\mfazones\Listeners\RegisterChecksListener;
use OCA\mfazones\Listeners\RegisterFlowOperationsListener;
use OCA\mfazones\Listeners\TwoFactorProviderChallengePassedListener;
use OCA\mfazones\MFAPlugin;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ISession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Application
 *
 * @package OCA\mfazones\AppInfo
 */
class Application extends App implements IBootstrap
{
  public const APP_ID = 'mfazones';

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

    $this->l = $this->getContainer()->get(IL10N::class);
    $this->session = $this->getContainer()->get(ISession::class);
    $this->logger = $this->getContainer()->get(LoggerInterface::class);

    $this->systemTagManager = $this->getContainer()->get(ISystemTagManager::class);
    $this->connection = $this->getContainer()->get(IDBConnection::class);

  }

  /**
   * @inheritdoc
   */
  public function register(IRegistrationContext $context): void
  {
    $this->logger->debug("MFA: register app enable listner");
    $context->registerEventListener(AppEnableEvent::class, AppEnableEventListener::class);
    $context->registerService(MFAPlugin::class, function (ContainerInterface $c) {
      $systemTagManager = $c->get(ISystemTagManager::class);
      $tagMapper = $c->get(ISystemTagObjectMapper::class);
      $x = new MFAPlugin($systemTagManager, $tagMapper);
      return $x;
    });

    $this->logger->debug("MFA: detection class is TwoFactorProviderChallengePassed");
    $context->registerEventListener(TwoFactorProviderChallengePassed::class, TwoFactorProviderChallengePassedListener::class);
    $this->logger->debug("MFA: load additonal scripst listner");
    $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
    $this->logger->debug("MFA: register checks listner");
    $context->registerEventListener(RegisterChecksEvent::class, RegisterChecksListener::class);
    $this->logger->debug("MFA: register operations listner");
    $context->registerEventListener(RegisterOperationsEvent::class, RegisterFlowOperationsListener::class);
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
}
