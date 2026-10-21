<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Service;

use OCA\OpenOTPAuth\Event\StateChanged;
use OCA\OpenOTPAuth\Provider\TwoFactorRCDevsOpenOTPProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;

class RegistrySynchronizer {
	public function __construct(
		private AdminPolicy $policy,
		private TwoFactorRCDevsOpenOTPProvider $provider,
		private IRegistry $registry,
		private IUserManager $userManager,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	public function synchronize(): int {
		// Initial setup may save server settings before choosing an auth mode.
		// Do not interpret missing/unknown configuration as a disable request.
		if (!$this->policy->isConfigured()) {
			return 0;
		}

		$count = 0;
		// Nextcloud enumerates all backends in batches of 500 without caching
		// every user object. Accounts that have never logged in are included.
		$this->userManager->callForAllUsers(function (IUser $user) use (&$count): void {
			$enabled = $this->policy->isEnabledForUser($user);
			$states = $this->registry->getProviderStates($user);
			if (($states[$this->provider->getId()] ?? null) === $enabled) {
				return;
			}

			if ($enabled) {
				$this->registry->enableProviderFor($this->provider, $user);
			} else {
				$this->registry->disableProviderFor($this->provider, $user);
			}
			$count++;
			$this->eventDispatcher->dispatchTyped(new StateChanged($user, $enabled));
		});

		return $count;
	}
}
