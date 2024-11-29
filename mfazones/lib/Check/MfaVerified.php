<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 MohammadReza Vahedi <mr.vahedi68@gmail.com>
 * SPDX-FileCopyrightText: 2024 Pondersource <michiel@pondersource.com> 
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\mfazones\Check;

use OCP\IL10N;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\WorkflowEngine\ICheck;
use Psr\Log\LoggerInterface;


class MfaVerified implements ICheck
{
  protected IL10N $l;
  protected ISession $session;
  private LoggerInterface $logger;
  private IUserManager $userManager;
  private string $userId;

  /**
   * @param IL10N $l
   * @param ISession $session
   * @param LoggerInterface $logger
   * @param IUserManager $usermanager
   * @param string $userId
   */
  public function __construct(
    IL10N $l,
    ISession $session,
    LoggerInterface $logger,
    IUserManager $userManager,
    string $userId,
  ) {
    $this->l = $l;
    $this->session = $session;
    $this->logger = $logger;
    $this->userManager = $userManager;
    $this->userId = $userId;
  }

  /**
   * @param string $operator
   * @param string $value
   * @return bool
   */
  public function executeCheck($operator, $value): bool
  {

    $mfaVerified = '0';
    /**
     * @var IUser $user
     */
    $user = $this->userManager->get($this->userId);
    if (!empty($this->session->get('globalScale.userData'))) {
      $attr = $this->session->get('globalScale.userData')["userData"];
      $mfaVerified = $attr["mfaVerified"];
    }
    if ($user !== null ) {
      /**
       * @var IUserBackend $userbackend
       */
      $userbackend = $user->getBackend();
      if ($userbackend->getBackendName() == 'user_saml') {
        $formatted = $userbackend->getUserData();
        $mfaVerified = $formatted['formatted']['mfaVerified'];
        $this->logger->debug("MFA: mfa_verified from samlUserData: " . $mfaVerified);
      }
    }
    if (!empty($this->session->get("two_factor_auth_passed"))) {
      $uid = $this->session->get('user_id');
      $event_passed = $this->session->get('two_factor_event_passed');
      if (!empty($uid) && !empty($event_passed) && ($uid === $event_passed)) {
        $this->logger->debug("MFA: 2fa passed for user " . (string) $uid);
        $mfaVerified = '1';
      } else {
        $this->logger->debug("MFA: 2fa not passed for user " . (string) $uid . " and event setting " . (string) $event_passed);
      }
    }
    if ($operator === 'is') {
      return $mfaVerified === '1'; // checking whether the current user is MFA-verified
    } else {
      return $mfaVerified !== '1'; // checking whether the current user is not MFA-verified
    }
  }

  /**
   * @param string $operator
   * @param string $value
   * @throws \UnexpectedValueException
   */
  public function validateCheck($operator, $value): void
  {
    if (!in_array($operator, ['is', '!is'])) {
      throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
    }
  }

  public function supportedEntities(): array
  {
    return [];
  }

  public function isAvailableForScope(int $scope): bool
  {
    return true;
  }
}
