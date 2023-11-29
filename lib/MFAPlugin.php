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
	private Server $server;

	public const VERSION_LABEL = '{http://nextcloud.org/ns}version-label';

	public function __construct(
		private IRequest $request,
		private IPreview $previewManager,
	) {
		$this->request = $request;
	}

	public function initialize(Server $server) {
		$this->server = $server;
		$server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node): void {
			$propFind->handle(self::VERSION_LABEL, fn() => 'ponder3source');
			// $propFind->handle(FilesPlugin::HAS_PREVIEW_PROPERTYNAME, fn () => 'ponder2source');
	}
}
