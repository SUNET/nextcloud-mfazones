<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\mfazones\Listeners;

use OCA\mfazones\AppInfo\Application;
use OCA\mfazones\Check\FileSystemTag;
use OCA\mfazones\Check\MfaVerified;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;

class RegisterChecksListener implements IEventListener
{

  public function __construct(
    private FileSystemTag $fileSystemTagCheck,
    private MfaVerified $mfaVerifiedCheck
  ) {}

  public function handle(Event $event): void
  {
    if (!$event instanceof RegisterChecksEvent) {
      return;
    }
    $event->registerCheck($this->mfaVerifiedCheck);
    $event->registerCheck($this->fileSystemTagCheck);
    Util::addScript(Application::APP_ID, 'mfazones-main');
  }
}
