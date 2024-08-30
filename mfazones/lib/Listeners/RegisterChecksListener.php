<?php

declare(strict_types=1);

/**
 * @copyright 2024 Micke Nordin <kano@sunet.se>
 * 
 * @author Micke Nordin <kano@sunet.se>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
