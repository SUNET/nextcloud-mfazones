<?php
/**
 * SPDX-FileCopyrightText: Micke Nordin <kano@sunet.se>
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

  public function __construct(
    private Utils $utils,
    IL10N $l,
    ISystemTagManager $systemTagManager,
    ISystemTagObjectMapper $systemTagObjectMapper,
    IUserSession $userSession,
    IGroupManager $groupManager
  ) {
    $this->utils = $utils;
    parent::__construct($l, $systemTagManager, $systemTagObjectMapper, $userSession, $groupManager);
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
    } catch (TagNotFoundException $e) {
      throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 3);
    } catch (\InvalidArgumentException $e) {
      throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 4);
    }
  }
}
