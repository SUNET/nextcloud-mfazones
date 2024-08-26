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
use OCA\mfazones\Check\MfaVerified;
use OCA\mfazones\Utils;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\ISession;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use OCP\WorkflowEngine\IManager;
use Psr\Log\LoggerInterface;

class RegisterChecksListener implements IEventListener
{
  private MfaVerified $mfaVerifiedCheck;
  private ISession $session;
  private LoggerInterface $logger;
  private string $tagId;
  private Manager $manager;
  private IL10N $l;

  public function __construct(Utils $utils, IL10N $l, ISession $session, LoggerInterface $logger, Manager $manager)
  {
    $this->l = $l;
    $this->session = $session;
    $this->logger = $logger;
    $this->manager = $manager;
    $this->mfaVerifiedCheck = new MfaVerified($this->l, $this->session, $this->logger);
    $this->tagId = $utils->getTagId(); // will create the tag if necessary
  }

  public function handle(Event $event): void
  {
    if (!$event instanceof RegisterChecksEvent) {
      return;
    }
    Util::addScript(Application::APP_ID, 'mfazones-main');
    $event->registerCheck($this->mfaVerifiedCheck);
    $context = new ScopeContext(IManager::SCOPE_ADMIN);
    $class = "OCA\\FilesAccessControl\\Operation";
    $name = "";
    $checks =  [
      [
        "class" => "OCA\mfazones\Check\MfaVerified",
        "operator" => "!is",
        "value" => ""
      ],
      [
        "class" => "OCA\WorkflowEngine\Check\FileSystemTags",
        "operator" => "is",
        "value" => $this->tagId
      ]
    ];
    $operation = "deny";
    $entity = "OCA\\WorkflowEngine\\Entity\\File";
    $events = [];
    $this->manager->addOperation($class, $name, $checks, $operation, $context, $entity, $events);
  }
}
