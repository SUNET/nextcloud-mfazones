<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\Utils;

use Doctrine\DBAL\Exception;
class MFAZonesUtils
{
  protected const TAG_NAME = "mfazones";

  public static function castObjectType($type)
  {
    if ($type === 'file') {
      return "files";
    }
    if ($type === "dir") {
      return "files";
    }
    return $type;
  }

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
