<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Check;

use OCA\WorkflowEngine\Check\FileSystemTags;
use OCP\SystemTag\TagNotFoundException;
use OCA\mfazones\Utils;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;

class FileSystemTag extends FileSystemTags
{
  private Utils $utils;

  public function __construct(
    IL10N $l,
    ISystemTagManager $systemTagManager,
    ISystemTagObjectMapper $systemTagObjectMapper,
    IUserSession $userSession,
    IGroupManager $groupManager,
    Utils $utils,
  ) {
    parent::__construct(
      $l,
      $systemTagManager,
      $systemTagObjectMapper,
      $userSession,
      $groupManager
    );
    $this->utils = $utils;
  }
  /**
   * @param string $operator
   * @param string $value
   * @throws \UnexpectedValueException
   */
  public function validateCheck($operator, $value)
  {
    if (!in_array($operator, ['is', '!is'])) {
      throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
    }
    if ($this->utils->getTagId() != $value) {
      throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 2);
    }

    try {
      $this->systemTagManager->getTagsByIds($value);
    } catch (TagNotFoundException) {
      throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 3);
    } catch (\InvalidArgumentException) {
      throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 4);
    }
  }
}
