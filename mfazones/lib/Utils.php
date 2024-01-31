<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: SUNET <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones;
use Doctrine\DBAL\Exception;
use OCP\SystemTag\ISystemTagManager;

class Utils {
  public const TAG_NAME = 'mfazone';

  /**
  * @param ISystemTagManager $systemTagManager
  * @return int|false
  */
  public static function getOurTagIdFromSystemTagManager($systemTagManager)
  {
    try {
      $tags = $systemTagManager->getAllTags(
        null,
        self::TAG_NAME
      );

      if (count($tags) < 1) {
        $tag = $systemTagManager->createTag(self::TAG_NAME, false, false);
      } else {
        $tag = current($tags);
      }
      return $tag->getId();
    } catch (Exception $e) {
      return false;
    }
  }
}
