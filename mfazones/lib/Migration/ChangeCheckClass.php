<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Migration;

use OCA\mfazones\Utils;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ChangeCheckClass implements IRepairStep
{


  public function __construct(
    private IDBConnection $db,
    private Utils $utils
  ) {}

  /**
   * Returns the step's name
   *
   * @return string
   */
  public function getName(): string
  {
    return 'Change FileSystemTag Class';
  }

  /**
   * @param IOutput $output
   */
  public function run(IOutput $output): void
  {
    $value = (int) $this->utils->getTagId();
    $old_class = 'OCA\WorkflowEngine\Check\FileSystemTags';
    $new_class = 'OCA\mfazones\Check\FileSystemTag';

    $output->startProgress(3);
    $output->advance(1, 'Migrating FileSystemTag');
    
    $query = $this->db->getQueryBuilder();

    $query->select('id')
      ->from('flow_checks')
      ->where($query->expr()->eq('class', $query->createNamedParameter($old_class)))
      ->andWhere($query->expr()->eq('value', $query->createNamedParameter($value)));
    $result = $query->executeQuery();
    $id = $result->fetchOne();
    $result->closeCursor();
    $query->select('operator')
      ->from('flow_checks')
      ->where($query->expr()->eq('id', $query->createNamedParameter($id)));
    $result = $query->executeQuery();
    $operator = $result->fetchOne();
    $result->closeCursor();
    
    if (!$id || !$operator) {
      $output->advance(3, 'No FileSystemTag found, id:' . (string) $id . " operator: " . (string) $operator);
      $output->finishProgress();
      return;
    }
    
    $hash = md5($new_class . '::' . $operator . '::' . (string) $value);
    
    $output->advance(2, 'Old FileSystemTag is: ' . $old_class . " old id is: " . $id);
  
    $query->update('flow_checks')
      ->set('class', $query->createNamedParameter($new_class))
      ->set('hash', $query->createNamedParameter($hash))
      ->where($query->expr()->eq('id', $query->createNamedParameter($id)));
    $query->executeStatement();
    
    $output->advance(3, 'New FileSystemTag is: ' . $new_class . " hash is " . $hash);
    $output->finishProgress();
  }
}
