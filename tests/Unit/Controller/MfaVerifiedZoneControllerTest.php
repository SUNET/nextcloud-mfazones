<?php

namespace OCA\MfaVerifiedZone\Controller\Tests;

use OCA\MfaVerifiedZone\Controller\MfaVerifiedZoneController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\QueryException;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\ITag;

class MfaVerifiedZoneControllerTest extends \Test\TestCase
{
    /** @var MfaVerifiedZoneController */
    private $controller;

    /** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
    private $rootFolder;

    /** @var ISystemTagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $systemTagManager;

    /** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
    private $groupManager;

    /** @var \OCP\ITagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $tagManager;

    /** @var ISystemTagObjectMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $tagMapper;

    /** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
    private $user;

    /** @var ITag|\PHPUnit\Framework\MockObject\MockObject */
    private $tag;

    protected function setUp(): void
    {
        $this->request = $this->createMock(IRequest::class);
        $this->userManager = $this->createMock(IUserManager::class);
        $this->rootFolder = $this->createMock(IRootFolder::class);
        $this->systemTagManager = $this->createMock(ISystemTagManager::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        $this->tagManager = $this->createMock(\OCP\ITagManager::class);
        $this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
        $this->user = $this->createMock(IUser::class);
        $this->tag = $this->createMock(ITag::class);

        $this->controller = new MfaVerifiedZoneController(
            $this->request,
            $this->userManager,
            $this->rootFolder,
            $this->groupManager,
            $this->tagManager,
            "user1",
            $this->tagMapper,
            $this->systemTagManager
        );
    }

    public function testGetReturnsJSONResponse(): void
    {
        $this->rootFolder
            ->method("getUserFolder")
            ->willReturn($this->createMock(\OCP\Files\Folder::class));
        $this->systemTagManager
            ->method("getAllTags")
            ->willReturn([$this->createMock(\OCP\SystemTag\ITag::class)]);
        $this->tagMapper->method("haveTag")->willReturn(true);

        $result = $this->controller->get("testsource");

        $this->assertInstanceOf(JSONResponse::class, $result);
    }

    public function testGet(): void
    {
        // Create a mock File object
        $file = $this->createMock(File::class);
        $file->method("getId")->willReturn("file1");
        $file->method("getName")->willReturn("test.txt");

        // Set up the expected response
        $expectedResponse = new JSONResponse([
            "status" => true
        ]);

        // Mock the root folder and user manager
        $this->rootFolder
            ->method("getUserFolder")
            ->willReturn($this->createMock(Node::class));
        $this->userManager->method("get")->willReturn($this->user);

        // Mock the request object
        $this->request
            ->method("getParam")
            ->with("fileid")
            ->willReturn("file1");

        // Set up expectations for the controller method calls
        $this->userManager
            ->expects($this->once())
            ->method("get")
            ->with("user1")
            ->willReturn($this->user);

        $this->user
            ->expects($this->once())
            ->method("getUID")
            ->willReturn("user1");

        $this->rootFolder
            ->expects($this->once())
            ->method("getById")
            ->with("file1")
            ->willReturn($file);

        // Call the controller method
        $actualResponse = $this->controller->get("test.txt");

        // Check that the response matches the expected value
        $this->assertEquals(
            $expectedResponse->getData(),
            $actualResponse->getData()
        );
    }

    public function testHasUserAccess(): void
    {
        $node = $this->createMock(Node::class);
        $node->method("getOwner")->willReturn($this->user);
        $node->method("getId")->willReturn("id1");
        $userRoot = $this->createMock(Node::class);
        $userRoot->method("get")->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);
        $this->groupManager->method("isAdmin")->willReturn(false);

        // Test user has access to own file
        $this->user->method("getUID")->willReturn("user1");
        $this->assertTrue($this->controller->hasAccess("id1"));

        // Test user does not have access to file owned by another user
        $this->user->method("getUID")->willReturn("user2");
        $this->assertFalse($this->controller->hasAccess("id1"));
    }
    public function testHasAdminAccess(): void
    {
        $node = $this->createMock(Node::class);
        $node->method("getOwner")->willReturn($this->user);
        $node->method("getId")->willReturn("id1");
        $userRoot = $this->createMock(Node::class);
        $userRoot->method("get")->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);

        // Test admin user has access to any file
        $this->groupManager->method("isAdmin")->willReturn(true);
        $this->assertTrue($this->controller->hasAccess("id1"));
    }

    public function testHasAccessReturnsTrueForFileOwnedByUser()
    {
        $source = "/path/to/file";
        $userFolder = $this->createMock(\OCP\Files\Folder::class);
        $node = $this->createMock(\OCP\Files\Node::class);

        $this->rootFolder
            ->expects($this->once())
            ->method("getUserFolder")
            ->with($this->equalTo($this->userId))
            ->willReturn($userFolder);

        $userFolder
            ->expects($this->once())
            ->method("get")
            ->with($this->equalTo($source))
            ->willReturn($node);

        $node
            ->expects($this->once())
            ->method("getOwner")
            ->willReturn($this->createMock(\OCP\IUser::class));

        $this->assertTrue($this->controller->hasAccess($source));
    }

    public function testSetWithValidNodeId(): void
    {
        $nodeId = "valid-node-id";
        $tagName = "verified-zone";

        $node = $this->createMock(File::class);
        $this->rootFolder
            ->method("getNode")
            ->with($nodeId)
            ->willReturn($node);
        $this->tagMapper
            ->expects($this->once())
            ->method("assignTags")
            ->with($node, [$tagName])
            ->willReturn(true);

        $response = $this->controller->set($nodeId, $tagName);

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals("ok", $response->getData()["status"]);
    }
}
