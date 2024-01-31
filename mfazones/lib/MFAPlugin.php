<?php

declare(strict_types=1);

namespace OCA\mfazones;

use OCA\DAV\Connector\Sabre\Node;
use OCA\mfazones\Utils;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class MFAPlugin extends ServerPlugin
{
  private ISystemTagManager $systemTagManager;
  private ISystemTagObjectMapper $tagMapper;

  public const ATTR_NAME = '{http://nextcloud.org/ns}requires-mfa';

  public function __construct(
    ISystemTagManager $systemTagManager,
    ISystemTagObjectMapper $tagMapper
  ) {
    $this->systemTagManager = $systemTagManager;
    $this->tagMapper = $tagMapper;
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
      $systemTagManager = $this->systemTagManager;
      $tagId = Utils::getOurTagIdFromSystemTagManager($systemTagManager);
      if ($tagId === false) {
        return false;
      }
      // FIXME: check parents too
      return $this->tagMapper->haveTag($node->getId(), 'files', $tagId);
    });
  }
}
