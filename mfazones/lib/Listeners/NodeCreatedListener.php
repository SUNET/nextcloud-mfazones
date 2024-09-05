<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se> 
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCP\Files\Events\Node\NodeCreatedEvent;
use OCA\mfazones\Utils;
use OCP\Files\Folder;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

class NodeCreatedListener implements IEventListener
{
  public function __construct(
    private LoggerInterface $logger,
    private Utils $utils
  ) {
    $this->tagId = (string) $this->utils->getTagId();
  }
  public function handle($event): void
  {
    if (! $event instanceof NodeCreatedEvent) {
      return;
    }
    $node = $event->getNode();
    $this->logger->debug('NodeCreatedListener');
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
