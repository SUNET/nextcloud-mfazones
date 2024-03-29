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

namespace OCA\mfazones\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;
use OCP\ISession;
use OCP\IUser;
use Psr\Log\LoggerInterface;

/**
 * Class TwoFactorProviderForUserEnabledListener
 *
 * @package OCA\mfazones\Listeners
 */
class TwoFactorProviderForUserEnabledListener implements IEventListener
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
    if (!$event instanceof TwoFactorProviderForUserEnabled) {
      $this->logger->debug("MFA: TwoFactorProviderForUserEnabled early return");
      return;
    }
    /**
     * @var IUser $user
     */
    $user = $event->getUser();
    $uid = $user->getUID();
    $session = $this->session;
    $this->logger->debug("MFA: setting session variable for user: " . (string) $uid);
    $session->set('two_factor_event_passed', $uid);
  }
}
