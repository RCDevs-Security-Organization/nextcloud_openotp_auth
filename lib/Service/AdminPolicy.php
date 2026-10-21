<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Service;

use OCA\OpenOTPAuth\Config;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserBackend;

class AdminPolicy {
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	public function isConfigured(): bool {
		return in_array($this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_authentication_method'), ['0', '1'], true);
	}

	public function isEnabledForUser(IUser $user): bool {
		if ($this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_authentication_method') !== '1') {
			return false;
		}

		if ($this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_disable_otp_local_users') === 'on') {
			$backend = $user->getBackend();
			if ($backend instanceof IUserBackend && $backend->getBackendName() === 'Database') {
				return false;
			}
		}

		return true;
	}
}
