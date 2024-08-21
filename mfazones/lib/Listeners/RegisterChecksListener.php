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
use OCA\mfazones\Check\MfaVerified;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\ISession;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use Psr\Log\LoggerInterface;

class RegisterChecksListener implements IEventListener
{
  private MfaVerified $mfaVerifiedCheck;
  private ISession $session;
  private LoggerInterface $logger;
  private IL10N $l;

  public function __construct()
  {
    $this->mfaVerifiedCheck = new MfaVerified($this->l, $this->session, $this->logger);
  }

  public function handle(Event $event): void
  {
    if (!$event instanceof RegisterChecksEvent) {
      return;
    }
    $event->registerCheck($this->mfaVerifiedCheck);
    Util::addScript(Application::APP_ID, 'mfazones-main');
  }
}
