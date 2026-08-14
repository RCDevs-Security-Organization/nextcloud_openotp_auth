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

namespace OCA\OpenOTPAuth;

use OCA\OpenOTPAuth\AppInfo\Application as OpenOTPAuthApp;

class Config
{
	public const APP_ID = OpenOTPAuthApp::APP_ID;

	/** @return array<string, list<string>> */
	public static function getAuthorizedAppConfig(): array
	{
		return [
			self::APP_ID => [
				'/^rcdevsopenotp_allow_user_administer_openotp$/',
				'/^rcdevsopenotp_api_key$/',
				'/^rcdevsopenotp_authentication_method$/',
				'/^rcdevsopenotp_client_id$/',
				'/^rcdevsopenotp_disable_otp_local_users$/',
				'/^rcdevsopenotp_proxy_host$/',
				'/^rcdevsopenotp_proxy_password$/',
				'/^rcdevsopenotp_proxy_port$/',
				'/^rcdevsopenotp_proxy_username$/',
				'/^rcdevsopenotp_server_url1$/',
				'/^rcdevsopenotp_server_url2$/',
			],
		];
	}
}
