<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCA\mfazones\AppInfo\Application;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadAdditionalScriptsListener implements IEventListener
{

  public function __construct()
  {
  }

  public function handle(Event $event): void
  {
    if (!$event instanceof LoadAdditionalScriptsEvent) {
      return;
    }
    Util::addScript(Application::APP_ID, 'mfazones-main');
    Util::addStyle(Application::APP_ID, 'tabview');
  }
}
