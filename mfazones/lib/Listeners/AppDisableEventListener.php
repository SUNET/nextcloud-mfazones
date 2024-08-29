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
use OCA\mfazones\Utils;
use OCP\App\Events\AppDisableEvent;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IDBConnection;
use OCP\WorkflowEngine\IManager;
use Psr\Log\LoggerInterface;

/**
 * Class AppDisableEventListener
 *
 * @package OCA\mfazones\Listeners
 */
class AppDisableEventListener implements IEventListener
{
  public function __construct(
    private IDBConnection $connection,
    private Utils $utils,
    private LoggerInterface $logger,
    private Manager $manager
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

    $tagId = $this->utils->getTagId(); // will create the tag if necessary

    try {

      $mfaVerifiedId = $this->getCheckId('OCA\mfazones\Check\MfaVerified', '!is');
      $fileSystemTagsId = $this->getCheckId('OCA\WorkflowEngine\Check\FileSystemTags', 'is', $tagId);
      $this->logger->debug("MFA: removing flow for $mfaVerifiedId and $fileSystemTagsId");

      // select id from oc_flow_operations where class = 'OCA\\FilesAccessControl\\Operation' and operation = 'deny' and checks = '[10,5]';
      $query = $this->connection->getQueryBuilder();
      $query->select('id')
        ->from('flow_operations')
        ->where($query->expr()->eq('class', $query->createNamedParameter('OCA\\FilesAccessControl\\Operation')))
        ->where($query->expr()->eq('operation', $query->createNamedParameter('deny')))
        ->where($query->expr()->eq('checks', $query->createNamedParameter('[' . $mfaVerifiedId . ',' . $fileSystemTagsId . ']')));
      $result = $query->executeQuery();
      $context = new ScopeContext(IManager::SCOPE_ADMIN);
      $operationId = $result->fetchOne();
      $result->closeCursor();
      if (!$operationId) {
        $this->logger->debug("MFA: removing flow for $mfaVerifiedId and $fileSystemTagsId unsuccessfull");
        return;
      }
      $this->logger->debug("MFA: removing flow with operationId: $operationId");
      $this->manager->deleteOperation($operationId, $context);
      $this->deleteCheckById($mfaVerifiedId);
      $this->deleteCheckById($fileSystemTagsId);
    } catch (\Exception $e) {
      $this->logger->error('MFA: Error when removing flow on disabling mfazones app', ['exception' => $e]);
    }
  }

  private function deleteCheckById($id, $table='flow_checks')
  {

    /** @var IQueryBuilder $query */
    $query = $this->connection->getQueryBuilder();
    $query->delete($table)
      ->where($query->expr()->eq('id', $query->createNamedParameter($id)));
    $query->executeStatement();
  }
  private function getCheckId($class, $operator, $value = "")
  {

    /** @var IQueryBuilder $query */
    $query = $this->connection->getQueryBuilder();
    $query->select('id')
      ->from('flow_checks')
      ->where($query->expr()->eq('class', $query->createNamedParameter($class)))
      ->where($query->expr()->eq('operator', $query->createNamedParameter($operator)))
      ->where($query->expr()->eq('value', $query->createNamedParameter($value)));
    $result = $query->executeQuery();

    $id = $result->fetchOne();
    $result->closeCursor();
    return $id;
  }
}
