<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\mfazones\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

class MFAZoneEnabledEvent extends Event
{
  public function __construct(
    private Node $node
  ) {
    parent::__construct();
  }
  
  public function getNode(): Node
  {
    return $this->node;
  }
}
