<?php
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-FileCopyrightText: SUNET <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\Controller;


use OCA\mfazones\AppInfo\Application;
use OCA\mfazones\Utils;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;

class MfazonesController extends Controller
{
  /** @var IRootFolder */
  private $rootFolder;

  /** @var string */
  private $userId;

  /** @var ISession */
  protected $session;

  /** @var ISystemTagManager */
  protected ISystemTagManager $systemTagManager;

  /** @var IGroupManager */
  private $groupManager;

  /** @var ISystemTagObjectMapper */
  private $tagMapper;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(
    IRequest $request,
    IRootFolder $rootFolder,
    IGroupManager $groupManager,
    string $userId,
    ISession $session,
    ISystemTagObjectMapper $tagMapper,
    ISystemTagManager $systemTagManager,
    LoggerInterface $logger
  ) {
    parent::__construct(Application::APP_ID, $request);
    $this->rootFolder = $rootFolder;
    $this->userId = $userId;
    $this->groupManager = $groupManager;
    $this->tagMapper = $tagMapper;
    $this->session = $session;
    $this->systemTagManager = $systemTagManager;
    $this->logger = $logger;
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
    $mfaVerified = '0';
    if (!empty($this->session->get('globalScale.userData'))) {
      $attr = $this->session->get('globalScale.userData')["userData"];
      $mfaVerified = $attr["mfaVerified"];
    }
    if (!empty($this->session->get('user_saml.samlUserData'))) {
      $attr = $this->session->get('user_saml.samlUserData');
      $mfaVerified = $attr["mfa_verified"][0];
    }
    if (!empty($this->session->get("two_factor_auth_passed"))) {
      $mfaVerified = '1';
    }
    return $mfaVerified === '1';
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
  public function get($source): JSONResponse
  {
    try {
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $node = $userRoot->get($source);
      $tagId = Utils::getOurTagIdFromSystemTagManager($this->systemTagManager);
      if ($tagId === false) {
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
   * 		['name' => 'mfazones#getList', 'url' => '/getList', 'verb' => 'GET'],
   *    This function returns a list of all the MFA Zones.
   */
  public function getList($nodeIds): JSONResponse
  {
    try {
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $tags = $this->systemTagManager->getAllTags(
        null,
        Utils::TAG_NAME
      );
      $tag = current($tags);
      if ($tag === false) {
        $this->logger->error('The MFA Zone tag and flow has not been created, which should happen on app enable.');
        return new JSONResponse(
          array(
            'error' => 'The MFA Zone tag and flow has not been created, which should happen on app enable.'
          )
        );
      }
      $tagId = $tag->getId();
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
      $tagId = Utils::getOurTagIdFromSystemTagManager($this->systemTagManager);
      if ($tagId === false) {
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
