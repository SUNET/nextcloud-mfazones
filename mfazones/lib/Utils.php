<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones;

use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;

class Utils
{
  public const TAG_NAME = 'mfazone';
  public string $tagId;

  public function __construct(
    private ISystemTagObjectMapper $tagMapper,
    private ISystemTagManager $systemTagManager
  ) {
    $this->tagId = $this->getTagId();
  }
  /**
   * @return string
   */
  public function getTagId()
  {
    try {
      $tags = $this->systemTagManager->getAllTags();
      foreach ($tags as $tag) {
        if ($tag->getName() === self::TAG_NAME) {
          return (string) $tag->getId();
        }
      }

      // https://github.com/nextcloud/server/blob/5a8cc42eb26cf9a31187ca8efc91405cc15d8e6d/lib/private/SystemTag/SystemTagManager.php#L180
      // Since NC 28 we don't want the user to see the tag. Previously it showed a cool tag, but no settings for it.
      // Now we get settings that does not work.
      $uservisible = false;
      // But we want it to be restricted so the user can not escape it.
      $userassignable = false;
      $tag = $this->systemTagManager->createTag(self::TAG_NAME, $uservisible, $userassignable);
      return $tag->getId();
    } catch (\Exception) {
      return '';
    }
  }
  public function nodeOrParentHasTag(Node $node): bool
  {
    try {
      while (!$this->hasTag($node)) {
        $node = $node->getParent();
      }
      return true;
    } catch (NotFoundException) {
      // We are at the root folder, so noone has the tag.
      return false;
    }
  }
  public function nodeOrChildHasTag(Node $node): bool
  {
    if ($this->hasTag($node)) {
      return true;
    }
    try {
      if ($node instanceof Folder) {
        foreach ($node->getDirectoryListing() as $child) {
          if ($this->hasTag($child)) {
            return true;
          }
          if ($child instanceof Folder) {
            $this->nodeOrChildHasTag($child);
          }
        }
      } else {
        return $this->hasTag($node);
      }
    } catch (NotFoundException) {
      return false;
    }
    return false;
  }

  // Recursively set the tag on all children.
  public function setTagOnChildren(Folder $folder): void
  {
    try {
      $children = $folder->getDirectoryListing();
      foreach ($children as $child) {
        if ($child instanceof Folder) {
          $this->setTag($child);
          $this->setTagOnChildren($child);
        } else {
          $this->setTag($child);
        }
      }
    } catch (NotFoundException) {
      return;
    }
  }
  // Recursively remove the tag on all children.
  public function removeTagOnChildren(Folder $folder): void
  {
    try {
      $children = $folder->getDirectoryListing();
      foreach ($children as $child) {
        if ($child instanceof Folder) {
          $this->removeTag($child);
          $this->removeTagOnChildren($child);
        } else {
          $this->removeTag($child);
        }
      }
    } catch (NotFoundException) {
      return;
    }
  }
  public function hasTag(Node $node): bool
  {
    return $this->tagMapper->haveTag((string) $node->getId(), 'files', $this->tagId);
  }
  public function removeTag(Node $node): void
  {
    $this->tagMapper->unassignTags((string) $node->getId(), 'files', [$this->tagId]);
  }
  public function setTag(Node $node): void
  {
    $this->tagMapper->assignTags((string) $node->getId(), 'files', [$this->tagId]);
  }
}
