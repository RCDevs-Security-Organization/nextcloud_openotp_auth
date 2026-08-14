<?php

/**
 *
 * @copyright Copyright (c) 2026, RCDevs (info@rcdevs.com)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\OpenOTPAuth\Migration;

use OCA\OpenOTPAuth\AppInfo\Application;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DisableTwoFactorOnAppDisable implements IRepairStep {
	public function __construct(
		private IRegistry $registry,
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Disable OpenOTP two-factor authentication';
	}

	public function run(IOutput $output): void {
		$this->registry->cleanUp(Application::APP_ID);
		$output->info('Removed OpenOTP two-factor provider associations.');

		if ($this->config->getSystemValue('twofactor_enforced', 'false') === 'true') {
			$this->config->setSystemValue('twofactor_enforced', 'false');
			$this->config->setSystemValue('twofactor_enforced_groups', []);
			$this->config->setSystemValue('twofactor_enforced_excluded_groups', []);
			$output->info('Disabled enforced two-factor authentication.');
		}
	}
}
