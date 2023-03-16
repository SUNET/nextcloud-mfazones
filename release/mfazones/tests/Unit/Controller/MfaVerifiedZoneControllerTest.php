<?php

namespace OCA\mfazones\Controller\Tests;

use OCA\mfazones\Controller\mfazonesController;
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
use OC\SystemTag\SystemTag;
use \OCP\Files\Folder;

class mfazonesControllerTest extends \Test\TestCase
{
    /** @var mfazonesController */
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

    /** @var array */
    private $tags;

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
        $this->tags = [];
        $this->tags[1] = new SystemTag("1", "mfazone", false, false);;

        $this->controller = new mfazonesController(
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
        $source = "testsource";
        
        $this->systemTagManager
            ->method("getAllTags")
            ->willReturn($this->tags);
        $this->tagMapper->method("haveTag")->willReturn(true);
        $node = $this->createMock(Node::class);
        $node->method("getType")->willReturn("file");
        $userRoot = $this->createMock(\OCP\Files\Folder::class);
        $userRoot->method("get")->with($source)->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);

        $result = $this->controller->get($source);

        $this->assertInstanceOf(JSONResponse::class, $result);
    }

    public function testGet(): void
    {
        $source = "test.txt";
        // Create a mock File object
        $file = $this->createMock(File::class);
        $file->method("getId")->willReturn("file1");
        $file->method("getName")->willReturn($source);

        // Set up the expected response
        $expectedResponse = new JSONResponse([
            "status" => true
        ]);

        // Mock the root folder and user manager
        $node = $this->createMock(Node::class);
        $node->method("getType")->willReturn("file");
        $userRoot = $this->createMock(\OCP\Files\Folder::class);
        $userRoot->method("get")->with($source)->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);

        $this->userManager->method("get")->willReturn($this->user);

        $this->tagMapper->method("haveTag")->willReturn(true);

        $this->systemTagManager
            ->method("getAllTags")
            ->willReturn($this->tags);

        // Mock the request object
        $this->request
            ->method("getParam")
            ->with("fileid")
            ->willReturn("file1");

        // Call the controller method
        $actualResponse = $this->controller->get($source);

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
        $userRoot = $this->createMock(Folder::class);
        $userRoot->method("get")->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);
        $this->groupManager->method("isAdmin")->willReturn(false);

        // Test user has access to own file
        $this->user->method("getUID")->willReturn("user1");
        $this->assertTrue($this->controller->hasAccess("id1"));
    }

    public function testHasNotUserAccess(): void
    {
        $node = $this->createMock(Node::class);
        $node->method("getOwner")->willReturn($this->user);
        $node->method("getId")->willReturn("id1");
        $userRoot = $this->createMock(Folder::class);
        $userRoot->method("get")->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);
        $this->groupManager->method("isAdmin")->willReturn(false);

        // Test user does not have access to file owned by another user
        $this->user->method("getUID")->willReturn("user2");
        $this->assertFalse($this->controller->hasAccess("id1"));
    }

    public function testHasAdminAccess(): void
    {
        $node = $this->createMock(Node::class);
        $node->method("getOwner")->willReturn($this->user);
        $node->method("getId")->willReturn("id1");
        $userRoot = $this->createMock(Folder::class);
        $userRoot->method("get")->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);

        // Test admin user has access to any file
        $this->groupManager->method("isAdmin")->willReturn(true);
        $this->assertTrue($this->controller->hasAccess("id1"));
    }

    public function testSetWithValidNodeId(): void
    {
        $nodeId = "valid-node-id";
        $tagName = "verified-zone";

        $node = $this->createMock(Node::class);
        $node->method("getOwner")->willReturn($this->user);
        $node->method("getId")->willReturn("id1");
        $userRoot = $this->createMock(Folder::class);
        $userRoot->method("get")->willReturn($node);
        $this->rootFolder->method("getUserFolder")->willReturn($userRoot);
        $this->tagMapper
            ->method("assignTags")
            ->with($node, [$tagName])
            ->willReturn(true);

        $response = $this->controller->set($nodeId, $tagName);

        $this->assertInstanceOf(DataResponse::class, $response);
    }
}
