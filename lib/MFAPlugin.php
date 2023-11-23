<?php

namespace OCA\mfazones;

use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ReportNotSupported;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class MFAPlugin extends ServerPlugin {

    public function __construct() {
        
	}

    /**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server) {
		$server->on('propFind', [$this, 'propFind'], 130);
    }

	public function propFind(PropFind $propFind, INode $node) {
		// TODO Add MFA status properties
		$propFind->set('PonderSource', 'Awesome');
		error_log($propFind->getPath().' is awesome!');
	}

}