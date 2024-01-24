<?php

/**
 * @copyright Copyright (c) 2023 MohammadReza Vahedi <mr.vahedi68@gmail.com>
 *
 * @author MohammadReza Vahedi <<mr.vahedi68@gmail.com>
 * @author Michiel de Jong <michiel@pondersource.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\mfazones\Check;

use OCP\IL10N;
use OCP\WorkflowEngine\ICheck;
use OCP\ISession;
use Psr\Log\LoggerInterface;


class MfaVerified implements ICheck
{
  protected IL10N $l;
  protected ISession $session;
  private LoggerInterface $logger;

  /**
   * @param IL10N $l
   * @param ISession $session
   */
  public function __construct(
    IL10N $l,
    ISession $session,
    LoggerInterface $logger,
  ) {
    $this->l = $l;
    $this->session = $session;
    $this->logger = $logger;
  }

  /**
   * @param string $operator
   * @param string $value
   * @return bool
   */
  public function executeCheck($operator, $value): bool
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
      $uid = $this->session->get('user_id');
      $event_passed = $this->session->get('two_factor_event_passed');
      if (!empty($uid) && !empty($event_passed) && ($uid === $event_passed))  {
        $this->logger->debug("MFA: 2fa passed for user " . (String) $uid);
        $mfaVerified = '1';
      } else {
        $this->logger->debug("MFA: 2fa not passed for user " . (String) $uid . " and event setting " . (String) $event_passed);
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

