<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Pondersource <michiel@pondersource.com> 
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones;

use OCA\DAV\Connector\Sabre\Node;
use OCA\mfazones\Utils;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class MFAPlugin extends ServerPlugin
{
  public const ATTR_NAME = '{http://nextcloud.org/ns}requires-mfa';

  public function __construct(
    private Utils $utils,
    private ISystemTagObjectMapper $tagMapper
  ) {}

  public function initialize(Server $server)
  {
    $server->on('propFind', [$this, 'propFind']);
  }
  /*
  @param PropFind $propFind 
  @param Node $node
  @return void 
 */
  public function propFind(PropFind $propFind, Node $node): void
  {
    $propFind->handle(self::ATTR_NAME, function () use (&$node) {
      $node = $node->getNode();
      $tagId = $this->utils->getTagId();
      if ($tagId === '') {
        return false;
      }
      do {
        $have_tag = $this->tagMapper->haveTag([$node->getId()], 'files', (string) $tagId);
        if ($have_tag) {
          return true;
        }
        try {
          $node = $node->getParent();
        } catch (NotFoundException) {
          return false;
        }
      } while ($node instanceof Folder);
      return false;
    });
  }
}
