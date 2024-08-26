<?php
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-FileCopyrightText: SUNET <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\Controller;


use OCA\mfazones\AppInfo\Application;
use OCA\mfazones\Utils;
use OCA\mfazones\Check\MfaVerified;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;

class MfazonesController extends Controller
{
  private IGroupManager $groupManager;
  private IRootFolder $rootFolder;
  private ISystemTagObjectMapper $tagMapper;
  private LoggerInterface $logger;
  private MfaVerified $mfaVerified;
  private Utils $utils;
  private string $userId;
  public function __construct(
    IRequest $request,
    IRootFolder $rootFolder,
    IGroupManager $groupManager,
    string $userId,
    Utils $utils,
    ISystemTagObjectMapper $tagMapper,
    MfaVerified $mfaVerified,
    LoggerInterface $logger
  ) {
    // NOTE: The request is only passed to the parent class
    // and is not instantiated by us here.
    $this->groupManager = $groupManager;
    $this->logger = $logger;
    $this->mfaVerified = $mfaVerified;
    $this->rootFolder = $rootFolder;
    $this->tagMapper = $tagMapper;
    $this->userId = $userId;
    $this->utils = $utils;
    parent::__construct(Application::APP_ID, $request);
  }

  private function castObjectType($type): string
  {
    if ($type === 'file') {
      return "files";
    }
    if ($type === "dir") {
      return "files";
    }
    return $type;
  }

  private function isMfaVerified(): bool
  {
    return $this->mfaVerified->executeCheck('is', NULL);
  }

  public function hasAccess($source): bool
  {
    try {
      $mfaVerified = $this->isMfaVerified();
      $isAdmin = $this->groupManager->isAdmin($this->userId);
      $userRoot = $this->rootFolder->getUserFolder($this->userId);

      try {
        $node = $userRoot->get($source);
        $hasAccess = ($isAdmin || $node->getOwner()->getUID() === $this->userId) && $mfaVerified;
      } catch (\Exception $e) {
        $this->logger->critical($e, ['app' => 'mfazones']);
        $hasAccess = false;
      }
      return $hasAccess;
    } catch (\Exception $e) {
      $this->logger->critical($e, ['app' => 'mfazones']);

      return false;
    }
  }

  /**
   * @NoAdminRequired
   * 		['name' => 'mfazones#getMfaStatus', 'url' => '/getMfaStatus', 'verb' => 'GET'],
   * 		This function is used to check if the user has passed the MFA challenge.
   */
  public function getMfaStatus(): JSONResponse
  {
    return new JSONResponse(
      array(
        'mfa_passed' => $this->isMfaVerified()
      )
    );
  }

  /**
   * @NoAdminRequired
   * 		['name' => 'mfazones#get', 'url' => '/get', 'verb' => 'GET'],
   * 		This function is used to check if the user has passed the MFA challenge
   * 		and also if the current file has the MFA Zone tag.
   */
  public function get(): JSONResponse
  {
    $source = $this->request->getParam('source');
    try {
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $node = $userRoot->get($source);
      $tagId = $this->utils->getTagId();
      if ($tagId === '') {
        $this->logger->error('The MFA Zone tag and flow has not been created, which should happen on app enable.');
        return new JSONResponse(
          array(
            'error' => 'The MFA Zone tag and flow has not been created, which should happen on app enable.'
          )
        );
      }
      $type = $this->castObjectType($node->getType());
      $result = $this->tagMapper->haveTag($node->getId(), $type, $tagId);

      return new JSONResponse(
        array(
          'status' => $result,
          'mfa_passed' => $this->isMfaVerified(),
          'has_access' => $this->hasAccess($source)
        )
      );
    } catch (\Exception $e) {
      $this->logger->critical($e, ['app' => 'mfazones']);

      return new JSONResponse(
        array(
          'response' => 'error',
          'msg' => $e->getMessage()
        )
      );
    }
  }


  /**
   * @NoAdminRequired
   * 		['name' => 'mfazones#getList', 'url' => '/getList', 'verb' => 'GET'],
   *    This function returns a list of all the MFA Zones.
   */
  public function getList($nodeIds): JSONResponse
  {
    try {
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $tagId = $this->utils->getTagId();
      if ($tagId === '') {
        $this->logger->error('The MFA Zone tag and flow has not been created, which should happen on app enable.');
        return new JSONResponse(
          array(
            'error' => 'The MFA Zone tag and flow has not been created, which should happen on app enable.'
          )
        );
      }
      $results = [];
      foreach ($nodeIds as $nodeId) {
        $node = $userRoot->getById($nodeId);
        $type = $this->castObjectType($node->getType());
        $results[$nodeId] = $this->tagMapper->haveTag($nodeId, $type, $tagId);
      }

      return new JSONResponse(
        array(
          'zones' => $results,
          'mfa_passed' => $this->isMfaVerified()
        )
      );
    } catch (\Exception $e) {
      $this->logger->critical($e, ['app' => 'mfazones']);

      return new JSONResponse(
        array(
          'response' => 'error',
          'msg' => $e->getMessage()
        )
      );
    }
  }

  /**
   * @NoAdminRequired
   * 		['name' => 'mfazones#set', 'url' => '/set', 'verb' => 'POST'],
   *
   */
  public function set($source, $protect)
  {
    try {
      $hasAccess = $this->hasAccess($source);
      if (!$hasAccess) {
        return new DataResponse([], Http::STATUS_FORBIDDEN);
      }
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $node = $userRoot->get($source);
      if ($node->getType() !== 'dir') {
        return new DataResponse(['not a directory'], Http::STATUS_FORBIDDEN);
      }
      $tagId = $this->utils->getTagId();
      if ($tagId === '') {
        $this->logger->error('The MFA Zone tag and flow has not been created, which should happen on app enable.');
        return new JSONResponse(
          array(
            'error' => 'The MFA Zone tag and flow has not been created, which should happen on app enable.'
          )
        );
      }
      $type = $this->castObjectType($node->getType());

      if ($protect === "true") {
        $this->tagMapper->assignTags($node->getId(), $type, $tagId);
      } else {
        $this->tagMapper->unassignTags($node->getId(), $type, $tagId);
      }

      return new DataResponse([], Http::STATUS_OK);
    } catch (\Exception $e) {
      $this->logger->critical($e, ['app' => 'mfazones']);

      return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * @NoAdminRequired
   * 		['name' => 'mfazones#access', 'url' => '/access', 'verb' => 'GET'],
   */
  public function access($source)
  {
    return new JSONResponse(
      array(
        'access' => $this->hasAccess($source)
      )
    );
  }
}
