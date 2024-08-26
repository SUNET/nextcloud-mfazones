<?php

declare(strict_types=1);

namespace OCA\mfazones;

use OCA\DAV\Connector\Sabre\Node;
use OCA\mfazones\Utils;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class MFAPlugin extends ServerPlugin
{
  public const ATTR_NAME = '{http://nextcloud.org/ns}requires-mfa';

  public function __construct(
    private Utils $utils,
    private ISystemTagObjectMapper $tagMapper
  ) {
  }

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
      $tagId = $this->utils->getTagId();
      if ($tagId === '') {
        return false;
      }
      // FIXME: check parents too
      return $this->tagMapper->haveTag([$node->getId()], 'files', (string) $tagId);
    });
  }
}
