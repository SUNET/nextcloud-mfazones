<?php
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\Controller;


use OCA\mfazones\AppInfo\Application;
use OCP\Activity\IManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Util;
use Psr\Log\LoggerInterface;

use OCA\mfazones\Utils\MFAZonesUtils;

class MfazonesController extends Controller
{
  /** @var IUserManager */
  private $userManager;

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

  /** @var \OCP\ITagManager */
  private $tagManager;

  /** @var ISystemTagObjectMapper */
  private $tagMapper;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(
    IRequest $request,
    IUserManager $userManager,
    IRootFolder $rootFolder,
    IGroupManager $groupManager,
    \OCP\ITagManager $tagManager,
    string $userId,
    ISession $session,
    ISystemTagObjectMapper $tagMapper,
    ISystemTagManager $systemTagManager,
    LoggerInterface $logger
  ) {
    parent::__construct(Application::APP_ID, $request);
    $this->userManager = $userManager;
    $this->rootFolder = $rootFolder;
    $this->userId = $userId;
    $this->groupManager = $groupManager;
    $this->tagManager = $tagManager;
    $this->tagMapper = $tagMapper;
    $this->session = $session;
    $this->systemTagManager = $systemTagManager;
    $this->logger = $logger;
  }

  private function isMfaVerified()
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

  public function hasAccess($source)
  {
    try {
      $mfaVerified = $this->isMfaVerified();
      $isAdmin = $this->groupManager->isAdmin($this->userId);
      $userRoot = $this->rootFolder->getUserFolder($this->userId);

      try {
        $node = $userRoot->get($source);
        $hasAccess = ($isAdmin || $node->getOwner()->getUID() === $this->userId) && $mfaVerified;
      } catch (\Exception $e) {
        \OC::$server->getLogger()->logException($e, ['app' => 'mfazones']);
        $hasAccess = false;
      }
      return $hasAccess;
    } catch (\Exception $e) {
      \OC::$server->getLogger()->logException($e, ['app' => 'mfazones']);

      return false;
    }
  }

  /**
   * @NoAdminRequired
   */
  public function getMfaStatus()
  {
    return new JSONResponse(
      array(
        'mfa_passed' => $this->isMfaVerified()
      )
    );
  }

  /**
   * @NoAdminRequired
   */
  public function get($source)
  {
    try {
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $node = $userRoot->get($source);
      $tagId = MFAZonesUtils::getOurTagIdFromSystemTagManager($this->systemTagManager);
      if ($tagId === false) {
        $this->logger->error('A server admin should log in so the MFA Zone tag and flow can be created.');
        return new JSONResponse(
          array(
            'error' => 'A server admin should log in so the MFA Zone tag and flow can be created'
          )
        );
      }
      $type = MFAZonesUtils::castObjectType($node->getType());
      $result = $this->tagMapper->haveTag($node->getId(), $type, $tagId);

      return new JSONResponse(
        array(
          'status' => $result,
          'mfa_passed' => $this->isMfaVerified()
        )
      );
    } catch (\Exception $e) {
      \OC::$server->getLogger()->logException($e, ['app' => 'mfazones']);

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
   */
  public function getList($nodeIds)
  {
    try {
      $userRoot = $this->rootFolder->getUserFolder($this->userId);
      $tags = $this->systemTagManager->getAllTags(
        null,
        Application::TAG_NAME
      );
      $tag = current($tags);
      if ($tag === false) {
        $this->logger->error('A server admin should log in so the MFA Zone tag and flow can be created.');
        return new JSONResponse(
          array(
            'error' => 'A server admin should log in so the MFA Zone tag and flow can be created'
          )
        );
      }
      $tagId = $tag->getId();
      $results = [];
      foreach ($nodeIds as $nodeId) {
        $node = $userRoot->getById($nodeId);
        $type = MFAZonesUtils::castObjectType($node->getType());
        $results[$nodeId] = $this->tagMapper->haveTag($nodeId, $type, $tagId);
      }

      return new JSONResponse(
        array(
          'zones' => $results,
          'mfa_passed' => $this->isMfaVerified()
        )
      );
    } catch (\Exception $e) {
      \OC::$server->getLogger()->logException($e, ['app' => 'mfazones']);

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
      $tagId = MFAZonesUtils::getOurTagIdFromSystemTagManager($this->systemTagManager);
      if ($tagId === false) {
        $this->logger->error('A server admin should log in so the MFA Zone tag and flow can be created.');
        return new JSONResponse(
          array(
            'error' => 'A server admin should log in so the MFA Zone tag and flow can be created'
          )
        );
      }
      $type = MFAZonesUtils::castObjectType($node->getType());

      if ($protect === "true") {
        $this->tagMapper->assignTags($node->getId(), $type, $tagId);
      } else {
        $this->tagMapper->unassignTags($node->getId(), $type, $tagId);
      }

      return new DataResponse([], Http::STATUS_OK);
    } catch (\Exception $e) {
      \OC::$server->getLogger()->logException($e, ['app' => 'mfazones']);

      return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * @NoAdminRequired
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
