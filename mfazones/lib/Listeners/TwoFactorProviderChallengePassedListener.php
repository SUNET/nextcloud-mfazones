<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\mfazones\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\ISession;
use OCP\IUser;
use Psr\Log\LoggerInterface;

/**
 * Class TwoFactorProviderChallengePassedListener
 *
 * @package OCA\mfazones\Listeners
 */

class TwoFactorProviderChallengePassedListener implements IEventListener
{

  public function __construct(
    private ISession $session,
    private LoggerInterface $logger
  ) {
  }
  /**
   * @param Event $event
   */
  public function handle(Event $event): void
  {
    if (!$event instanceof TwoFactorProviderChallengePassed) {
      $this->logger->debug("MFA: TwoFactorProviderChallengePassed early return");
      return;
    }
    /**
     * @var IUser $user
     */
    $user = $event->getUser();
    $session = $this->session;
    $this->logger->debug("MFA: setting session variable for user: " . (string) $user->getUID());
    $session->set('two_factor_event_passed', $user->getUID());
  }
}
