<?php

declare(strict_types=1);
/** SPDX-FileCopyrightText: 2024 Pondersource <michiel@pondersource.com>
 *  SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 *  SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\mfazones\Events\MFAZoneDisabledEvent;
use OCA\mfazones\Events\MFAZoneEnabledEvent;
use OCA\mfazones\Listeners\AppDisableEventListener;
use OCA\mfazones\Listeners\AppEnableEventListener;
use OCA\mfazones\Listeners\LoadAdditionalScriptsListener;
use OCA\mfazones\Listeners\MFAZoneDisabledListener;
use OCA\mfazones\Listeners\MFAZoneEnabledListener;
use OCA\mfazones\Listeners\NodeCreatedListener;
use OCA\mfazones\Listeners\RegisterChecksListener;
use OCA\mfazones\Listeners\RegisterOperationsListener;
use OCA\mfazones\Listeners\TwoFactorProviderChallengePassedListener;
use OCA\mfazones\MFAPlugin;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\MapperEvent;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;

/**
 * Class Application
 *
 * @package OCA\mfazones\AppInfo
 */
class Application extends App implements IBootstrap
{
  public const APP_ID = 'mfazones';

  public function __construct()
  {
    parent::__construct(self::APP_ID);
  }

  /**
   * @inheritdoc
   */
  public function register(IRegistrationContext $context): void
  {
    $context->registerEventListener(AppDisableEvent::class, AppDisableEventListener::class);
    $context->registerEventListener(AppEnableEvent::class, AppEnableEventListener::class);
    $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
    $context->registerEventListener(MFAZoneEnabledEvent::class, MFAZoneEnabledListener::class);
    $context->registerEventListener(MFAZoneDisabledEvent::class, MFAZoneDisabledListener::class);
    $context->registerEventListener(MapperEvent::class, MapperListener::class);
    $context->registerEventListener(NodeCreatedEvent::class, NodeCreatedListener::class);
    $context->registerEventListener(RegisterChecksEvent::class, RegisterChecksListener::class);
    $context->registerEventListener(RegisterOperationsEvent::class, RegisterOperationsListener::class);
    $context->registerEventListener(TwoFactorProviderChallengePassed::class, TwoFactorProviderChallengePassedListener::class);
    $context->registerService(
      MFAPlugin::class,
      function (ContainerInterface $c) {
        $systemTagManager = $c->get(ISystemTagManager::class);
        $tagMapper = $c->get(ISystemTagObjectMapper::class);
        $x = new MFAPlugin($systemTagManager, $tagMapper);
        return $x;
      }
    );
  }

  /**
   * @inheritdoc
   */
  public function boot(IBootContext $context): void {}
}
