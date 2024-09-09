<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se> 
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCA\mfazones\Events\MFAZoneDisabledEvent;
use OCA\mfazones\Utils;
use OCP\Files\Folder;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

class MFAZoneDisabledListener implements IEventListener
{
  public function __construct(
    private Utils $utils,
    private ISystemTagObjectMapper $tagMapper,
    private LoggerInterface $logger
  ) {
  }
  public function handle($event): void
  {
    if (! $event instanceof MFAZoneDisabledEvent) {
      return;
    }
    $this->logger->debug('MFAZoneDisabledListener');
    $node = $event->getNode();
    if ($this->utils->nodeOrChildHasTag($node)) {
      // This node has the tag, so remove the tag on this node.
      if ($this->utils->hasTag($node)) {
        $this->utils->removeTag($node);
      }
      // We are a folder, so we need to remove the tag on all children.
      if ($node instanceof Folder) {
        $this->utils->removeTagOnChildren($node);
      }
    }
  }
}
