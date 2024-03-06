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
        // https://github.com/nextcloud/server/blob/5a8cc42eb26cf9a31187ca8efc91405cc15d8e6d/lib/private/SystemTag/SystemTagManager.php#L180
        // Since NC 28 we don't want the user to see the tag. Previously it showed a cool tag, but no settings for it.
        // Now we get settings that does not work.
        $uservisible = false;
        // But we want it to be restricted so the user can not escape it.
        $userassignable = false;
        $tag = $systemTagManager->createTag(self::TAG_NAME, $uservisible, $userassignable);
      } else {
        $tag = current($tags);
      }
      return $tag->getId();
    } catch (Exception $e) {
      return false;
    }
  }
}
