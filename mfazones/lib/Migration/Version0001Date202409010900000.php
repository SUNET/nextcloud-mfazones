<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazone\Migration;

use OCP\Migration\SimpleMigrationStep;
use OCP\IDBConnection;
use OCP\Migration\IOutput;

class Version0001Date202409010900000 extends SimpleMigrationStep
{


  public function __construct(
    private IDBConnection $db
  ) {}


  public function run(IOutput $output)
  {
    $output->startProgress([1]);
    $output->advance([1], 'Migrating FileSystemTag');
    $query = $this->db->getQueryBuilder();
    $query->select('class,operator,value')
      ->from('flow_checks');
    $result = $query->executeQuery();
    $answer = $result->fetchOne(); 
    $result->closeCursor();
    $class = $answer['class'];
    $operator = $answer['operator'];
    $value = $answer['value'];
    $new_operator = 'OCA\mfazones\Check\FileSystemTag';
    $hash = md5($class . '::' . $new_operator . '::' . $value);
    $query->update('flow_checks')
      ->set('hash', $query->createNamedParameter($hash))
      ->set('operator', $query->createNamedParameter($new_operator))
      ->set('value', $query->createNamedParameter($value))
      ->where($query->expr()->eq('class', $query->createNamedParameter($class)))
      ->andWhere($query->expr()->eq('operator', $query->createNamedParameter($operator)))
      ->andWhere($query->expr()->eq('value', $query->createNamedParameter($value)));
    $query->executeStatement();
    $output->finishProgress();
  }
}
