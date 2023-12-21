<?php

declare(strict_types=1);
namespace OCA\mfazones;

use OC\AppFramework\Http\Request;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\IPreview;
use OCP\IRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class MFAPlugin extends ServerPlugin {
    /** @var ISystemTagManager */
    private $sytemTagMapper;

    /** @var ISystemTagObjectMapper */
    private $tagMapper;

	public const ATTR_NAME = '{http://nextcloud.org/ns}requires-mfa';

	public function __construct(
		ISystemTagManager $systemTagManager,
        ISystemTagObjectMapper $tagMapper
	) {
		$this->systemTagManager = $systemTagManager;
		$this->tagMapper = $tagMapper;
        // $server = \OC\Server::getInstance();
		// $container = $server->getContainer();
	}

	public function initialize(Server $server) {
		$this->server = $server;
        // $this->systemTagManager = $server::getInstance()->get(ISystemTagManager::class);
		// $this->tagMapper = \OCP\Server::getInstance()->get(ITagMapper::class);
		$server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node): void {
		$propFind->handle(self::ATTR_NAME, function() {
			$tagId = Application::getOurTagIdFromSystemTagManager($this->systemTagManager);
            if ($tagId === false) {
                return false;
            }
            $type = Application::castObjectType($node->getType());
			// FIXME: check parents too
            return $this->tagMapper->haveTag($node->getId(), $type, $tagId);
		});
	}
}
