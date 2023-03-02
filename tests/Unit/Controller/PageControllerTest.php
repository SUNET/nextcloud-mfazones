<?php

declare(strict_types=1);

namespace OCA\MfaVerifiedZone\Tests\Unit\Controller;

use OCA\MfaVerifiedZone\Controller\MfaVerifiedZoneController;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IUserManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Class MfaVerifiedZoneControllerTest.
 *
 * @covers \OCA\MfaVerifiedZone\Controller\MfaVerifiedZoneController
 */
final class MfaVerifiedZoneControllerTest extends TestCase
{
    private MfaVerifiedZoneController $mfaVerifiedZoneController;

    private IRequest|MockObject $request;

    private IUserManager|MockObject $userManager;

    private IRootFolder|MockObject $rootFolder;

    private IGroupManager|MockObject $groupManager;

    private ITagManager|MockObject $tagManager;

    private string $userId;

    private ISystemTagObjectMapper|MockObject $tagMapper;

    private ISystemTagManager|MockObject $systemTagManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->userManager = $this->createMock(IUserManager::class);
        $this->rootFolder = $this->createMock(IRootFolder::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        $this->tagManager = $this->createMock(ITagManager::class);
        $this->userId = '42';
        $this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
        $this->systemTagManager = $this->createMock(ISystemTagManager::class);
        $this->mfaVerifiedZoneController = new MfaVerifiedZoneController($this->request, $this->userManager, $this->rootFolder, $this->groupManager, $this->tagManager, $this->userId, $this->tagMapper, $this->systemTagManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mfaVerifiedZoneController);
        unset($this->request);
        unset($this->userManager);
        unset($this->rootFolder);
        unset($this->groupManager);
        unset($this->tagManager);
        unset($this->userId);
        unset($this->tagMapper);
        unset($this->systemTagManager);
    }

    public function testGet(): void
    {
		$folder = $this->createMock(Folder::class);
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->willReturn($folder);
				
		$user = $this->createMock(IUser::class);

		$this->session->expects($this->once())
			->method('getUser')
			->willReturn($user);
		
		$user->expects($this->any())
		->method('getUID')
		->willReturn('user');
        /** @todo This test is incomplete. */
        $this->markTestIncomplete();
    }

    public function testSet(): void
    {
        /** @todo This test is incomplete. */
        $this->markTestIncomplete();
    }

    public function testAccess(): void
    {
        /** @todo This test is incomplete. */
        $this->markTestIncomplete();
    }
}
