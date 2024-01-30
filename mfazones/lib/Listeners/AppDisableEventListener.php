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

use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCA\mfazones\AppInfo\Application;
use OCP\App\Events\AppDisableEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\SystemTag\ISystemTagManager;
use OCP\WorkflowEngine\IManager;
use Psr\Log\LoggerInterface;

/**
 * Class AppEnableEventListener
 *
 * @package OCA\mfazones\Listeners
 */
class AppDisableEventListener implements IEventListener
{
  public function __construct(
    private Manager $manager,
    private ISystemTagManager $systemTagManager,
    private LoggerInterface $logger
  ) {
  }

  /**
   * @param Event $event
   */
  public function handle(Event $event): void
  {
    if (!$event instanceof AppDisableEvent) {
      $this->logger->debug("MFA: AppDisableEventListener early return");
      return;
    }
    if ($event->getAppId() !== 'mfazones') {
      $this->logger->debug("MFA: AppDisableEventListener not mfazones, early return");
      return;
    }

    $this->logger->debug("MFA: removing flow.");


    $tagId = Application::getOurTagIdFromSystemTagManager($this->systemTagManager); // will create the tag if necessary

    $context = new ScopeContext(IManager::SCOPE_ADMIN);
    $this->manager->deleteOperation($tagId, $context);
  }
}
