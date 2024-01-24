<?php

declare(strict_types=1);

/**
 * @copyright 2024 Micke Nordin <kano@sunet.se>
 * 
 * @author Micke Nordin <kano@sunet.se>
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
namespace OCA\mfazones\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\ISession;
use Psr\Log\LoggerInterface;

class TwoFactorProviderChallengePassedListener implements IEventListener {

  public function __construct(
    private ISession $session,
    private LoggerInterface $logger
  ) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof TwoFactorProviderChallengePassed) {
			return;
		}
    $user = $event->getUser();
    $session = $this->session;
    $session->set('two_factor_event_passed', $user->getUID());
	}
}
