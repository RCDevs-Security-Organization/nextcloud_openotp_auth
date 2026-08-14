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

namespace OCA\OpenOTPAuth\Settings\Admin;

use OCA\OpenOTPAuth\Config;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private readonly IURLGenerator $url,
		private readonly IL10N $l10n,
	) {
	}

	public function getIcon(): string {
		return $this->url->imagePath(Config::APP_ID, 'app-dark.svg');
	}

	public function getID(): string {
		return Config::APP_ID;
	}

	public function getName(): string {
		return $this->l10n->t('OpenOTP Authentication');
	}

	public function getPriority(): int {
		return 55;
	}
}
