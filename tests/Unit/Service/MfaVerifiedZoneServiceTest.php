<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\MfaVerifiedZone\Tests\Unit\Service;

use OCA\MfaVerifiedZone\Service\NotFoundException;
use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Db\DoesNotExistException;

use OCA\MfaVerifiedZone\Service\MfaVerifiedZoneService;

class MfaVerifiedZoneServiceTest extends TestCase {
	private MfaVerifiedZoneService $service;
	private string $userId = 'john';

	public function setUp(): void {
		$this->service = new MfaVerifiedZoneService();
	}
}
