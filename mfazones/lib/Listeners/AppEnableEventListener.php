<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCA\mfazones\Utils;
use OCP\App\Events\AppEnableEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\WorkflowEngine\IManager;
use Psr\Log\LoggerInterface;

/**
 * Class AppEnableEventListener
 *
 * @package OCA\mfazones\Listeners
 */
class AppEnableEventListener implements IEventListener
{
  public function __construct(
    private Manager $manager,
    private Utils $utils,
    private LoggerInterface $logger
  ) {
  }

  /**
   * @param Event $event
   */
  public function handle(Event $event): void
  {
    if (!$event instanceof AppEnableEvent) {
      $this->logger->debug("MFA: AppEnableEventListener early return");
      return;
    }
    if ($event->getAppId() !== 'mfazones') {
      $this->logger->debug("MFA: AppEnableEventListener not mfazones, early return");
      return;
    }

    $this->logger->debug("MFA: setting up flow.");


    $tagId = $this->utils->getTagId(); // will create the tag if necessary

    $context = new ScopeContext(IManager::SCOPE_ADMIN);
    $class = "OCA\\FilesAccessControl\\Operation";
    $name = "";
    $checks =  [
      [
        "class" => "OCA\\mfazones\\Check\\MfaVerified",
        "operator" => "!is",
        "value" => ""
      ],
      [
        "class" => "OCA\\mfazones\\Check\\FileSystemTag",
        "operator" => "is",
        "value" => $tagId
      ]
    ];
    $operation = "deny";
    $entity = "OCA\\WorkflowEngine\\Entity\\File";
    $events = [];

    $this->manager->addOperation($class, $name, $checks, $operation, $context, $entity, $events);
  }
}
