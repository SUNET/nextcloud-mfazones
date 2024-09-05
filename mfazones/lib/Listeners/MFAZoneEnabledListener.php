<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se> 
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCA\mfazones\Utils;
use OCA\mfazones\Events\MFAZoneEnabledEvent;
use OCP\Files\Folder;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

class MFAZoneEnabledListener implements IEventListener
{
  public function __construct(
    private Utils $utils,
    private LoggerInterface $logger
  ) {
    $this->tagId = (string) $this->utils->getTagId();
  }
  public function handle($event): void
  {
    if (! $event instanceof MFAZoneEnabledEvent) {
      return;
    }
    $this->logger->debug('MFAZoneEnabledListener');
    $node = $event->getNode();
    if ($this->utils->nodeOrParentHasTag($node)) {
      // A parent has the tag but not this node, so add the tag to this node.
      if (!$this->utils->hasTag($node)) {
        $this->utils->setTag($node);
      }
      // We are a folder, so we need to set the tag on all children.
      if ($node instanceof Folder) {
        $this->utils->setTagOnChildren($node);
      }
    }
  }
}
