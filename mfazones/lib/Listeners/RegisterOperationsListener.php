<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Michiel de Jong <michiel@pondersource.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCA\mfazones\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

class RegisterOperationsListener implements IEventListener
{

  public function __construct()
  {
  }

  public function handle(Event $event): void
  {
    if (!$event instanceof RegisterOperationsEvent) {
      return;
    }
    Util::addScript(Application::APP_ID, 'mfazones-main');
  }
}
